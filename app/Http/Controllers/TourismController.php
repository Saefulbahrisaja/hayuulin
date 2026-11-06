<?php

namespace App\Http\Controllers;

use App\Models\Accommodation;
use App\Models\Category;
use App\Models\Destination;
use App\Models\TransportOption;
use App\Models\TransportRoute;
use App\Models\TravelTariff;
use App\Services\TripRecommendationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TourismController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'category_id' => ['nullable', Rule::exists('categories', 'id')],
            'region' => ['nullable', 'string', 'max:120'],
            'min_rating' => ['nullable', 'numeric', 'between:0,5'],
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        $draft = $request->session()->get('trip_draft', []);
        if (($draft['version'] ?? null) !== 1) {
            $request->session()->forget('trip_draft');
            $draft = [];
        } elseif (! empty($draft['plan'])) {
            $draft['plan']['cost_prediction'] ??= [
                'per_person' => null,
                'status' => 'Model prediksi biaya belum tersedia untuk rencana yang tersimpan di sesi lama.',
            ];
            foreach ($draft['plan']['stops'] as &$stop) {
                $stop['recommendation_score'] ??= 50;
                $stop['recommendation_reasons'] ??= [];
                $stop['cluster'] ??= 1;
                $stop['visitor_prediction'] ??= ['count' => null, 'training_rows' => 0];
                $stop['visitor_prediction_date'] ??= now()->toDateString();
            }
            unset($stop);
        }

        return $this->page(
            $request,
            $draft['plan'] ?? null,
            $draft['form'] ?? [],
            $draft['origin'] ?? null,
            $draft['recommendation_scores'] ?? []
        );
    }

    public function recommend(Request $request, TripRecommendationService $recommendations)
    {
        $validated = $request->validate($recommendations->plannerValidationRules());
        $draft = $recommendations->recommend($validated, $request->user());
        if ($draft['plan']['stops']) {
            $request->session()->put('trip_draft', ['version' => 1] + $draft);
        } else {
            $request->session()->forget('trip_draft');
        }

        return $this->page(
            $request,
            $draft['plan'],
            $draft['form'],
            $draft['origin'],
            $draft['recommendation_scores']
        );
    }

    private function page(
        Request $request,
        ?array $plan = null,
        array $form = [],
        ?array $origin = null,
        array $recommendationScores = []
    ) {
        $categories = Category::query()->orderBy('name')->get();
        $destinationsQuery = Destination::with('category')
            ->withCount([
                'reviews as positive_review_count' => fn ($query) => $query->where('sentiment', 'positif'),
                'reviews as neutral_review_count' => fn ($query) => $query->where('sentiment', 'netral'),
                'reviews as negative_review_count' => fn ($query) => $query->where('sentiment', 'negatif'),
            ])
            ->where('is_verified', true)
            ->orderByDesc('rating');
        $filterCategory = $form['category_id'] ?? $request->query('category_id');
        $filterRegion = $form['region'] ?? $request->query('region');
        $filterRating = $form['min_rating'] ?? $request->query('min_rating');
        $search = trim((string) ($form['q'] ?? $request->query('q', '')));
        if ($filterCategory) {
            $destinationsQuery->where('category_id', $filterCategory);
        }
        if ($filterRegion) {
            $destinationsQuery->where('region', 'like', '%'.$filterRegion.'%');
        }
        if ($filterRating !== null && $filterRating !== '') {
            $destinationsQuery->where('rating', '>=', $filterRating);
        }
        if ($search !== '') {
            $destinationsQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('address', 'like', '%'.$search.'%')
                    ->orWhere('region', 'like', '%'.$search.'%');
            });
        }
        $destinations = $destinationsQuery->get();
        $regions = Destination::query()->where('is_verified', true)->distinct()->orderBy('region')->pluck('region');
        $transportOptions = TransportOption::where('is_active', true)->orderBy('name')->get();
        $accommodations = Accommodation::where('is_active', true)
            ->where('is_verified', true)
            ->orderByDesc('price_verified_at')
            ->orderBy('price_per_night')
            ->limit(8)
            ->get();
        $transportRoutes = TransportRoute::with('transportOption')
            ->where('is_active', true)
            ->orderBy('origin_region')
            ->get();

        $mapDestinations = $destinations->map(fn (Destination $destination) => [
            'id' => $destination->id,
            'name' => $destination->name,
            'address' => $destination->address,
            'region' => $destination->region,
            'latitude' => $destination->latitude,
            'longitude' => $destination->longitude,
            'rating' => $destination->rating,
            'category_id' => $destination->category_id,
            'category' => $destination->category->name,
            'color' => $destination->category->color,
            'cluster' => $recommendationScores[$destination->id]['cluster'] ?? null,
        ])->values();

        $mapRoute = [];
        if ($plan && ! empty($plan['stops']) && $origin) {
            $mapRoute = collect($plan['stops'])->groupBy('day')->map(
                fn ($stops) => array_merge(
                    [['latitude' => $origin['latitude'], 'longitude' => $origin['longitude']]],
                    $stops->map(fn (array $stop) => [
                        'latitude' => $stop['latitude'],
                        'longitude' => $stop['longitude'],
                    ])->all(),
                    [['latitude' => $origin['latitude'], 'longitude' => $origin['longitude']]]
                )
            )->values()->all();
        }

        return view('home', compact(
            'categories',
            'destinations',
            'regions',
            'transportOptions',
            'accommodations',
            'transportRoutes',
            'form',
            'mapDestinations',
            'mapRoute',
            'origin',
            'plan'
        ));
    }
}
