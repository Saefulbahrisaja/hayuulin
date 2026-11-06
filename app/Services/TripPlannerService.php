<?php

namespace App\Services;

use App\Models\Accommodation;
use App\Models\Destination;
use App\Models\TransportOption;
use Illuminate\Support\Collection;

class TripPlannerService
{
    private const ROAD_DISTANCE_FACTOR = 1.3;

    private const VEHICLE_KM_RATE = 3500;

    private const MEAL_COST_PER_STOP = 35000;

    private const PARKING_PER_STOP = 10000;

    private const ROOM_COST_PER_NIGHT = 180000;

    public function plan(
        Collection $destinations,
        array $origin,
        int $budgetPerPerson,
        int $days,
        int $travelers,
        bool $includeAccommodation,
        array $filters = [],
        ?TransportOption $transport = null,
        ?Collection $accommodations = null,
        ?Collection $tariffs = null,
        ?callable $costPredictor = null
    ): array {
        $accommodations ??= collect();
        $tariffValues = ($tariffs ?? collect())->pluck('amount', 'key');
        $transport ??= new TransportOption([
            'name' => 'Mobil pribadi',
            'type' => 'car',
            'cost_per_km' => 3500,
            'capacity' => 4,
            'average_speed_kmh' => 35,
        ]);
        $destinations = $destinations->filter(function (Destination $destination) use ($filters, $origin) {
            if (! empty($filters['q'])) {
                $search = mb_strtolower($filters['q']);
                if (
                    ! str_contains(mb_strtolower($destination->name), $search)
                    && ! str_contains(mb_strtolower($destination->address), $search)
                    && ! str_contains(mb_strtolower($destination->region), $search)
                ) {
                    return false;
                }
            }
            if (($filters['min_rating'] ?? 0) > (float) $destination->rating) {
                return false;
            }
            if (! empty($filters['region']) && mb_stripos($destination->region, $filters['region']) === false) {
                return false;
            }
            if (! empty($filters['max_distance'])) {
                $distance = $this->distance($origin, [
                    'latitude' => (float) $destination->latitude,
                    'longitude' => (float) $destination->longitude,
                ]);
                if ($distance > (int) $filters['max_distance']) {
                    return false;
                }
            }

            return true;
        });

        $maxStops = min(8, $days * 2);
        $ranked = $destinations
            ->map(fn (Destination $destination) => $this->destinationData(
                $destination,
                $origin,
                $filters['preference'] ?? 'balanced',
                $filters['recommendation_scores'][$destination->id] ?? []
            ))
            ->sort(function (array $a, array $b) use ($filters) {
                if (! empty($filters['recommendation_scores'])) {
                    $recommendationOrder = $b['recommendation_score'] <=> $a['recommendation_score'];
                    if ($recommendationOrder !== 0) {
                        return $recommendationOrder;
                    }
                }
                if (($filters['preference'] ?? 'balanced') === 'rating') {
                    return ($b['rating'] <=> $a['rating']) ?: ($a['admission_fee'] <=> $b['admission_fee']);
                }
                if (($filters['preference'] ?? 'balanced') === 'budget') {
                    return ($a['admission_fee'] <=> $b['admission_fee']) ?: ($b['rating'] <=> $a['rating']);
                }
                if (($filters['preference'] ?? 'balanced') === 'distance') {
                    return ($a['origin_distance_km'] <=> $b['origin_distance_km']) ?: ($b['rating'] <=> $a['rating']);
                }

                return ($b['score'] <=> $a['score']) ?: ($a['admission_fee'] <=> $b['admission_fee']);
            })
            ->take($maxStops * 2)
            ->values();

        $selected = [];
        $estimate = $this->estimate([], $origin, $days, $travelers, $includeAccommodation, $transport, $accommodations, $tariffValues, $costPredictor, $filters['travel_date'] ?? now()->toDateString());

        foreach ($ranked as $candidate) {
            if (count($selected) >= $maxStops) {
                break;
            }

            $trial = $this->optimizeRoute([...$selected, $candidate], $origin);
            $trial = $this->schedule($trial, $origin, $days, $transport->average_speed_kmh);
            if ($trial === null) {
                continue;
            }

            $trialEstimate = $this->estimate($trial, $origin, $days, $travelers, $includeAccommodation, $transport, $accommodations, $tariffValues, $costPredictor, $filters['travel_date'] ?? now()->toDateString());

            if (($trialEstimate['cost_prediction']['per_person'] ?? $trialEstimate['costs']['per_person']) <= $budgetPerPerson) {
                $selected = $trial;
                $estimate = $trialEstimate;
            }
        }

        foreach ($selected as $index => &$stop) {
            $stop['sequence'] = $index + 1;
        }
        unset($stop);

        $previousByDay = [];
        foreach ($selected as &$stop) {
            $day = $stop['day'];
            $previous = $previousByDay[$day] ?? $origin;
            $legDistance = $this->distance($previous, $stop) * self::ROAD_DISTANCE_FACTOR;
            $stop['leg_distance_km'] = round($legDistance, 1);
            $stop['leg_minutes'] = (int) round($legDistance / max(1, $transport->average_speed_kmh) * 60);
            $previousByDay[$day] = $stop;
        }
        unset($stop);

        return [
            'stops' => $selected,
            'costs' => $estimate['costs'],
            'distance_km' => $estimate['distance_km'],
            'travel_minutes' => $estimate['travel_minutes'],
            'visit_minutes' => $estimate['visit_minutes'],
            'total_minutes' => $estimate['travel_minutes'] + $estimate['visit_minutes'],
            'daily_minutes' => $estimate['daily_minutes'],
            'daily_travel_minutes' => $estimate['daily_travel_minutes'],
            'transport' => $estimate['transport'],
            'accommodation' => $estimate['accommodation'],
            'accommodation_options' => $estimate['accommodation_options'],
            'cost_prediction' => $estimate['cost_prediction'],
            'budget_per_person' => $budgetPerPerson,
            'within_budget' => (bool) $selected
                && ($estimate['cost_prediction']['per_person'] ?? $estimate['costs']['per_person']) <= $budgetPerPerson,
        ];
    }

    private function destinationData(Destination $destination, array $origin, string $preference, array $recommendation = []): array
    {
        $data = [
            'id' => $destination->id,
            'name' => $destination->name,
            'description' => $destination->description,
            'address' => $destination->address,
            'region' => $destination->region,
            'latitude' => (float) $destination->latitude,
            'longitude' => (float) $destination->longitude,
            'rating' => (float) $destination->rating,
            'category' => $destination->category?->name ?? 'Wisata',
            'category_color' => $destination->category?->color ?? '#557b68',
            'admission_fee' => (int) $destination->admission_fee,
            'visit_duration_minutes' => (int) $destination->visit_duration_minutes,
            'recommendation_score' => (float) ($recommendation['score'] ?? 50),
            'recommendation_reasons' => $recommendation['reasons'] ?? [],
            'cluster' => (int) ($recommendation['cluster'] ?? 1),
            'visitor_prediction' => $recommendation['visitor_prediction'] ?? null,
        ];

        $data['origin_distance_km'] = $this->distance($origin, $data);
        $data['score'] = match ($preference) {
            'rating' => $data['rating'] * 100,
            'budget' => ($data['rating'] * 10) - ($data['admission_fee'] / 1000),
            'distance' => -$data['origin_distance_km'],
            default => ($data['rating'] * 100) - ($data['admission_fee'] / 5000) - ($data['origin_distance_km'] * 0.1),
        };

        return $data;
    }

    private function optimizeRoute(array $nodes, array $origin): array
    {
        if (count($nodes) < 2) {
            return $nodes;
        }

        $count = count($nodes);
        $originIndex = $count;
        $points = [...$nodes, $origin];
        $distances = [];

        for ($i = 0; $i <= $count; $i++) {
            for ($j = 0; $j <= $count; $j++) {
                $distances[$i][$j] = $i === $j
                    ? 0.0
                    : max(0.1, $this->distance($points[$i], $points[$j]) * self::ROAD_DISTANCE_FACTOR);
            }
        }

        $pheromones = array_fill(0, $count + 1, array_fill(0, $count + 1, 0.1));
        $state = crc32(implode('-', array_column($nodes, 'id')).$origin['latitude'].$origin['longitude']) & 0x7FFFFFFF;
        $state = $state ?: 1;
        $random = function () use (&$state): float {
            $state ^= ($state << 13) & 0x7FFFFFFF;
            $state ^= $state >> 17;
            $state ^= ($state << 5) & 0x7FFFFFFF;
            $state &= 0x7FFFFFFF;

            return $state / 2147483648;
        };

        $bestRoute = range(0, $count - 1);
        $bestDistance = $this->routeDistance($bestRoute, $distances, $originIndex);
        $antCount = max(12, $count * 4);

        for ($iteration = 0; $iteration < 35; $iteration++) {
            $iterationBestRoute = $bestRoute;
            $iterationBestDistance = $bestDistance;

            for ($ant = 0; $ant < $antCount; $ant++) {
                $unvisited = range(0, $count - 1);
                $route = [];
                $current = $originIndex;

                while ($unvisited) {
                    $weights = [];
                    $weightTotal = 0.0;
                    foreach ($unvisited as $candidate) {
                        $weight = $pheromones[$current][$candidate]
                            * (1 / $distances[$current][$candidate]) ** 3;
                        $weights[$candidate] = $weight;
                        $weightTotal += $weight;
                    }

                    $threshold = $random() * $weightTotal;
                    $selected = end($unvisited);
                    foreach ($unvisited as $candidate) {
                        $threshold -= $weights[$candidate];
                        if ($threshold <= 0) {
                            $selected = $candidate;
                            break;
                        }
                    }

                    $route[] = $selected;
                    $unvisited = array_values(array_filter(
                        $unvisited,
                        fn (int $candidate) => $candidate !== $selected
                    ));
                    $current = $selected;
                }

                $length = $this->routeDistance($route, $distances, $originIndex);
                if ($length < $iterationBestDistance) {
                    $iterationBestRoute = $route;
                    $iterationBestDistance = $length;
                }
            }

            $bestRoute = $iterationBestRoute;
            $bestDistance = $iterationBestDistance;

            for ($i = 0; $i <= $count; $i++) {
                for ($j = 0; $j <= $count; $j++) {
                    $pheromones[$i][$j] *= 0.55;
                }
            }

            $previous = $originIndex;
            foreach ([...$bestRoute, $originIndex] as $next) {
                $deposit = 1 / max(1, $bestDistance);
                $pheromones[$previous][$next] += $deposit;
                $pheromones[$next][$previous] += $deposit;
                $previous = $next;
            }
        }

        return array_map(fn (int $index) => $nodes[$index], $bestRoute);
    }

    private function routeDistance(array $route, array $distances, int $originIndex): float
    {
        $distance = 0.0;
        $previous = $originIndex;

        foreach ($route as $next) {
            $distance += $distances[$previous][$next];
            $previous = $next;
        }

        return $distance + $distances[$previous][$originIndex];
    }

    private function schedule(array $stops, array $origin, int $days, int $averageSpeed): ?array
    {
        $scheduled = [];
        $dayStops = [];
        $day = 1;
        $maximumDailyMinutes = 8 * 60;

        foreach ($stops as $stop) {
            $candidateDay = [...$dayStops, $stop];
            if ($this->dailyDuration($candidateDay, $origin, $averageSpeed) > $maximumDailyMinutes) {
                if ($day >= $days || $this->dailyDuration([$stop], $origin, $averageSpeed) > $maximumDailyMinutes) {
                    return null;
                }

                $day++;
                $dayStops = [$stop];
            } else {
                $dayStops = $candidateDay;
            }

            $stop['day'] = $day;
            $scheduled[] = $stop;
        }

        return $scheduled;
    }

    private function dailyDuration(array $stops, array $origin, int $averageSpeed): int
    {
        $distance = 0.0;
        $previous = $origin;

        foreach ($stops as $stop) {
            $distance += $this->distance($previous, $stop) * self::ROAD_DISTANCE_FACTOR;
            $previous = $stop;
        }
        if ($stops) {
            $distance += $this->distance($previous, $origin) * self::ROAD_DISTANCE_FACTOR;
        }

        return (int) round($distance / max(1, $averageSpeed) * 60)
            + array_sum(array_column($stops, 'visit_duration_minutes'));
    }

    private function estimate(
        array $stops,
        array $origin,
        int $days,
        int $travelers,
        bool $includeAccommodation,
        TransportOption $transport,
        Collection $accommodations,
        Collection $tariffs,
        ?callable $costPredictor,
        string $travelDate
    ): array {
        $stopsByDay = [];
        foreach ($stops as $stop) {
            $stopsByDay[$stop['day']][] = $stop;
        }

        $distance = 0.0;
        $travelMinutes = 0;
        $visitMinutes = 0;
        $dailyMinutes = [];
        $dailyTravelMinutes = [];
        foreach ($stopsByDay as $day => $dayStops) {
            $dayDistance = 0.0;
            $previous = $origin;
            foreach ($dayStops as $stop) {
                $dayDistance += $this->distance($previous, $stop) * self::ROAD_DISTANCE_FACTOR;
                $previous = $stop;
            }
            $dayDistance += $this->distance($previous, $origin) * self::ROAD_DISTANCE_FACTOR;
            $dayTravelMinutes = (int) round($dayDistance / max(1, $transport->average_speed_kmh) * 60);
            $dayVisitMinutes = array_sum(array_column($dayStops, 'visit_duration_minutes'));

            $distance += $dayDistance;
            $travelMinutes += $dayTravelMinutes;
            $visitMinutes += $dayVisitMinutes;
            $dailyMinutes[$day] = $dayTravelMinutes + $dayVisitMinutes;
            $dailyTravelMinutes[$day] = $dayTravelMinutes;
        }

        $vehicles = (int) ceil($travelers / max(1, $transport->capacity));
        $transportCost = (int) round($distance * $transport->cost_per_km * $vehicles);
        $tickets = array_sum(array_column($stops, 'admission_fee')) * $travelers;
        $meals = count($stops) * ($tariffs['meal_per_stop'] ?? self::MEAL_COST_PER_STOP) * $travelers;
        $parking = $transport->type === 'public'
            ? 0
            : count($stops) * ($tariffs['parking_per_stop'] ?? self::PARKING_PER_STOP) * $vehicles;
        $nights = $includeAccommodation ? max(0, $days - 1) : 0;
        $accommodationChoices = $this->accommodationChoices($stops, $accommodations);
        $selectedAccommodation = $accommodationChoices->first();
        $roomCapacity = $selectedAccommodation ? max(1, $selectedAccommodation->capacity) : 2;
        $roomCount = (int) ceil($travelers / $roomCapacity);
        $nightlyRate = $selectedAccommodation
            ? $selectedAccommodation->price_per_night
            : ($tariffs['accommodation_per_night'] ?? self::ROOM_COST_PER_NIGHT);
        $accommodationCost = $nights * $nightlyRate * $roomCount;
        $accommodationDistance = null;
        if ($selectedAccommodation?->latitude !== null && $selectedAccommodation?->longitude !== null && $stops) {
            $accommodationDistance = min(array_map(
                fn (array $stop) => $this->distance($selectedAccommodation->toArray(), $stop) * self::ROAD_DISTANCE_FACTOR,
                $stops
            ));
        }
        $accommodationInfo = $nights === 0 ? null : [
            'name' => $selectedAccommodation?->name ?? 'Tarif penginapan standar',
            'region' => $selectedAccommodation?->region ?? 'Mengikuti wilayah itinerary',
            'price_per_night' => (int) $nightlyRate,
            'rooms' => $roomCount,
            'nights' => $nights,
            'total' => $accommodationCost,
            'distance_km' => $accommodationDistance === null ? null : round($accommodationDistance, 1),
        ];
        $groupTotal = $transportCost + $tickets + $meals + $parking + $accommodationCost;
        $predictedCost = $costPredictor
            ? $costPredictor($distance, $travelers, $days, (string) $transport->type, $travelDate)
            : null;
        if ($predictedCost !== null && (! is_numeric($predictedCost) || ! is_finite((float) $predictedCost))) {
            $predictedCost = null;
        }
        $costPrediction = $predictedCost === null ? null : [
            'group_total' => max(0, (int) round($predictedCost)),
            'per_person' => (int) ceil(max(0, $predictedCost) / max(1, $travelers)),
            'training_rows' => 0,
            'method' => 'Random Forest Regression · umpan balik biaya aktual',
        ];

        return [
            'distance_km' => round($distance, 1),
            'travel_minutes' => $travelMinutes,
            'visit_minutes' => $visitMinutes,
            'daily_minutes' => $dailyMinutes,
            'daily_travel_minutes' => $dailyTravelMinutes,
            'transport' => [
                'name' => $transport->name,
                'type' => $transport->type,
                'cost_per_km' => (int) $transport->cost_per_km,
                'vehicles' => $vehicles,
                'travelers_per_vehicle' => (int) $transport->capacity,
                'average_speed_kmh' => (int) $transport->average_speed_kmh,
                'estimated_cost' => $transportCost,
            ],
            'accommodation' => $accommodationInfo,
            'cost_prediction' => $costPrediction,
            'accommodation_options' => $accommodationChoices->take(3)->map(fn (Accommodation $option) => [
                'name' => $option->name,
                'region' => $option->region,
                'type' => $option->type,
                'rating' => (float) $option->rating,
                'price_per_night' => (int) $option->price_per_night,
                'distance_km' => $this->accommodationDistance($option, $stops),
            ])->values()->all(),
            'costs' => [
                'transport' => $transportCost,
                'tickets' => $tickets,
                'meals' => $meals,
                'accommodation' => $accommodationCost,
                'parking' => $parking,
                'group_total' => $groupTotal,
                'per_person' => (int) ceil($groupTotal / $travelers),
            ],
        ];
    }

    private function accommodationChoices(array $stops, Collection $accommodations): Collection
    {
        $regions = collect($stops)->pluck('region')->unique()->values();

        return $accommodations
            ->filter(fn (Accommodation $option) => ! $regions->count() || $regions->contains($option->region))
            ->sort(function (Accommodation $a, Accommodation $b) {
                return ($a->price_per_night <=> $b->price_per_night) ?: ($b->rating <=> $a->rating);
            })
            ->values();
    }

    private function accommodationDistance(Accommodation $accommodation, array $stops): ?float
    {
        if ($accommodation->latitude === null || $accommodation->longitude === null || ! $stops) {
            return null;
        }

        return round(min(array_map(
            fn (array $stop) => $this->distance($accommodation->toArray(), $stop) * self::ROAD_DISTANCE_FACTOR,
            $stops
        )), 1);
    }

    private function distance(array $a, array $b): float
    {
        $earthRadiusKm = 6371;
        $latDifference = deg2rad($b['latitude'] - $a['latitude']);
        $lonDifference = deg2rad($b['longitude'] - $a['longitude']);
        $aLatitude = deg2rad($a['latitude']);
        $bLatitude = deg2rad($b['latitude']);
        $haversine = sin($latDifference / 2) ** 2
            + cos($aLatitude) * cos($bLatitude) * sin($lonDifference / 2) ** 2;

        return 2 * $earthRadiusKm * asin(min(1, sqrt($haversine)));
    }
}
