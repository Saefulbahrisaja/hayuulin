<?php

namespace App\Services;

use App\Models\Destination;
use App\Models\DestinationVisitMetric;
use App\Models\Trip;
use App\Models\TripStop;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TourismMlService
{
    public function __construct(private readonly RandomForestRegressor $forest) {}

    public function scoreDestinations(
        Collection $destinations,
        ?User $user,
        array $preferences
    ): array {
        $destinationIds = $destinations->modelKeys();
        $userDestinationIds = $user
            ? TripStop::whereHas('trip', fn ($query) => $query->where('user_id', $user->id))
                ->whereNotNull('destination_id')
                ->pluck('destination_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->all()
            : [];
        $categoryAffinity = [];
        if ($userDestinationIds) {
            $categoryAffinity = Destination::whereIn('id', $userDestinationIds)
                ->get(['id', 'category_id'])
                ->groupBy('category_id')
                ->map(fn ($items) => $items->count())
                ->all();
        }

        $interactions = DB::table('trip_stops')
            ->join('trips', 'trip_stops.trip_id', '=', 'trips.id')
            ->whereIn('trip_stops.destination_id', $destinationIds)
            ->select('trips.user_id', 'trip_stops.destination_id')
            ->get()
            ->groupBy('user_id')
            ->map(fn ($rows) => $rows->pluck('destination_id')->map(fn ($id) => (int) $id)->unique()->all());
        $reviewSignals = DB::table('destination_reviews')
            ->whereIn('destination_id', $destinationIds)
            ->select('destination_id', 'sentiment', DB::raw('COUNT(*) as total'))
            ->groupBy('destination_id', 'sentiment')
            ->get()
            ->groupBy('destination_id');
        $popularity = [];
        foreach ($interactions as $visited) {
            foreach ($visited as $id) {
                $popularity[$id] = ($popularity[$id] ?? 0) + 1;
            }
        }
        $maximumPopularity = max(1, max($popularity ?: [0]));

        $similarityScores = array_fill_keys($destinationIds, 0.0);
        if ($userDestinationIds) {
            $userSet = array_fill_keys($userDestinationIds, true);
            foreach ($interactions as $otherUserId => $visited) {
                if ((int) $otherUserId === $user->id) {
                    continue;
                }
                $overlap = count(array_intersect($userDestinationIds, $visited));
                if ($overlap === 0) {
                    continue;
                }
                $similarity = $overlap / sqrt(count($userDestinationIds) * count($visited));
                foreach ($visited as $id) {
                    if (! isset($userSet[$id]) && isset($similarityScores[$id])) {
                        $similarityScores[$id] += $similarity;
                    }
                }
            }
        }
        $maximumSimilarity = max(1.0, max($similarityScores ?: [0.0]));
        $clusters = $this->clusterDestinations($destinations);
        $scores = [];

        foreach ($destinations as $destination) {
            $categoryMatch = ! empty($preferences['category_id'])
                ? ((int) $destination->category_id === (int) $preferences['category_id'] ? 1.0 : 0.0)
                : ($categoryAffinity
                    ? ($categoryAffinity[$destination->category_id] ?? 0) / max($categoryAffinity)
                    : 0.5);
            $ratingScore = $destination->rating > 0
                ? min(1.0, max(0.0, (float) $destination->rating / 5))
                : 0.5;
            $destinationReviews = $reviewSignals[$destination->id] ?? collect();
            $reviewCount = $destinationReviews->sum('total');
            $positiveReviews = $destinationReviews->firstWhere('sentiment', 'positif')->total ?? 0;
            $neutralReviews = $destinationReviews->firstWhere('sentiment', 'netral')->total ?? 0;
            $reviewQuality = $reviewCount > 0
                ? ($positiveReviews + 0.5 * $neutralReviews) / $reviewCount
                : null;
            $qualityScore = $reviewQuality === null ? $ratingScore : (0.7 * $ratingScore + 0.3 * $reviewQuality);
            $popularityScore = log(1 + ($popularity[$destination->id] ?? 0)) / log(1 + $maximumPopularity);
            $collaborativeScore = min(1.0, ($similarityScores[$destination->id] ?? 0) / $maximumSimilarity);
            $costScore = isset($preferences['budget'])
                ? max(0.0, 1 - ((int) $destination->admission_fee / max(1, (int) $preferences['budget'])))
                : 0.5;
            $distance = $this->distance(
                (float) ($preferences['origin_latitude'] ?? -6.9193),
                (float) ($preferences['origin_longitude'] ?? 106.9267),
                (float) $destination->latitude,
                (float) $destination->longitude
            );
            $distanceScore = max(0.0, 1 - min(1, $distance / max(1, (float) ($preferences['max_distance'] ?? 250))));

            $score = 100 * (
                0.35 * $categoryMatch
                + 0.15 * $qualityScore
                + 0.15 * $popularityScore
                + 0.20 * $collaborativeScore
                + 0.10 * $costScore
                + 0.05 * $distanceScore
            );
            $scores[$destination->id] = [
                'score' => round($score, 1),
                'cluster' => $clusters[$destination->id] ?? 1,
                'reasons' => $this->recommendationReasons(
                    $categoryMatch,
                    $qualityScore,
                    $collaborativeScore,
                    $popularityScore
                ),
            ];
        }

        return $scores;
    }

    public function costPredictor(): array
    {
        $trips = Trip::whereNotNull('actual_cost')
            ->where('actual_cost', '>', 0)
            ->orderByDesc('start_date')
            ->limit(1000)
            ->get();
        $samples = $trips->map(fn (Trip $trip) => $this->tripFeatures(
            (float) $trip->total_distance_km,
            (int) $trip->travelers,
            (int) $trip->days,
            (string) ($trip->preferences['transport_type'] ?? ''),
            $trip->start_date?->format('Y-m-d') ?? $trip->created_at->format('Y-m-d')
        ))->all();
        $targets = $trips->pluck('actual_cost')->map(fn ($cost) => (float) $cost)->all();

        return [
            'count' => count($targets),
            'predict' => fn (float $distance, int $travelers, int $days, string $transportType, string $date) => $this->forest->predict(
                $samples,
                $targets,
                $this->tripFeatures($distance, $travelers, $days, $transportType, $date)
            ),
        ];
    }

    public function predictVisitors(int $destinationId, string $date, bool $isHoliday = false): array
    {
        $metrics = DestinationVisitMetric::where('destination_id', $destinationId)
            ->where('visited_on', '>=', now()->subYears(3)->toDateString())
            ->orderByDesc('visited_on')
            ->limit(500)
            ->get();
        $samples = $metrics->map(fn (DestinationVisitMetric $metric) => $this->dateFeatures(
            $metric->visited_on->format('Y-m-d'),
            (bool) $metric->is_holiday
        ))->all();
        $targets = $metrics->pluck('visitor_count')->map(fn ($count) => (float) $count)->all();
        $prediction = $this->forest->predict($samples, $targets, $this->dateFeatures($date, $isHoliday));

        return [
            'count' => $prediction === null ? null : max(0, (int) round($prediction)),
            'training_rows' => count($targets),
            'method' => $prediction === null ? 'Belum cukup data kunjungan terverifikasi' : 'Random Forest Regression',
        ];
    }

    private function tripFeatures(
        float $distance,
        int $travelers,
        int $days,
        string $transportType,
        string $date
    ): array {
        return [
            max(0, $distance),
            max(1, $travelers),
            max(1, $days),
            (float) ($transportType === 'car'),
            (float) ($transportType === 'motorcycle'),
            (float) ($transportType === 'public'),
            (float) ($transportType === 'rental'),
            ...$this->dateFeatures($date, false),
        ];
    }

    private function dateFeatures(string $date, bool $isHoliday): array
    {
        $day = (int) date('N', strtotime($date));
        $month = (int) date('n', strtotime($date));

        return [
            $day,
            (float) ($day >= 6),
            sin(2 * M_PI * $month / 12),
            cos(2 * M_PI * $month / 12),
            (float) $isHoliday,
        ];
    }

    private function clusterDestinations(Collection $destinations): array
    {
        if ($destinations->isEmpty()) {
            return [];
        }
        $rows = $destinations->map(fn (Destination $destination) => [
            'id' => $destination->id,
            'values' => [
                (float) $destination->latitude * 111,
                (float) $destination->longitude * 82,
                (float) $destination->category_id,
                (float) $destination->rating,
                (float) ($destination->admission_fee ?? 0),
            ],
        ])->values()->all();
        $features = array_column($rows, 'values');
        $minimums = [];
        $maximums = [];
        foreach (array_keys($features[0]) as $feature) {
            $column = array_column($features, $feature);
            $minimums[$feature] = min($column);
            $maximums[$feature] = max($column);
        }
        foreach ($rows as &$row) {
            foreach ($row['values'] as $feature => $value) {
                $row['values'][$feature] = $maximums[$feature] === $minimums[$feature]
                    ? 0.0
                    : ($value - $minimums[$feature]) / ($maximums[$feature] - $minimums[$feature]);
            }
        }
        unset($row);
        $k = min(3, count($rows));
        if ($k === 1) {
            return [$rows[0]['id'] => 1];
        }
        $centroids = array_map(fn ($i) => $rows[$i]['values'], range(0, $k - 1));
        $assignments = [];
        for ($iteration = 0; $iteration < 12; $iteration++) {
            $groups = array_fill(0, $k, []);
            foreach ($rows as $row) {
                $distances = array_map(fn (array $centroid) => $this->featureDistance($row['values'], $centroid), $centroids);
                $cluster = array_search(min($distances), $distances, true);
                $groups[$cluster][] = $row;
                $assignments[$row['id']] = $cluster + 1;
            }
            foreach ($groups as $index => $group) {
                if ($group) {
                    $centroids[$index] = array_map(
                        fn ($featureIndex) => array_sum(array_column(array_column($group, 'values'), $featureIndex)) / count($group),
                        range(0, count($centroids[$index]) - 1)
                    );
                }
            }
        }

        return $assignments;
    }

    private function featureDistance(array $a, array $b): float
    {
        return sqrt(array_sum(array_map(fn ($left, $right) => ($left - $right) ** 2, $a, $b)));
    }

    private function recommendationReasons(
        float $category,
        float $rating,
        float $collaborative,
        float $popularity
    ): array {
        $reasons = [];
        if ($category >= 0.75) {
            $reasons[] = 'Sesuai kategori/minat';
        }
        if ($rating >= 0.8) {
            $reasons[] = 'Rating/ulasan positif terdata';
        }
        if ($collaborative >= 0.5) {
            $reasons[] = 'Dipilih wisatawan dengan riwayat serupa';
        }
        if ($popularity >= 0.5) {
            $reasons[] = 'Sering masuk itinerary tersimpan';
        }

        return $reasons ?: ['Cocok dengan kriteria perjalanan'];
    }

    private function distance(float $latitudeA, float $longitudeA, float $latitudeB, float $longitudeB): float
    {
        $latDifference = deg2rad($latitudeB - $latitudeA);
        $lonDifference = deg2rad($longitudeB - $longitudeA);
        $haversine = sin($latDifference / 2) ** 2
            + cos(deg2rad($latitudeA)) * cos(deg2rad($latitudeB)) * sin($lonDifference / 2) ** 2;

        return 6371 * 2 * asin(min(1, sqrt($haversine)));
    }
}
