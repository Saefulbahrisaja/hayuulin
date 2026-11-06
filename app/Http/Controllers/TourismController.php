<?php

namespace App\Http\Controllers;

use App\Models\Accommodation;
use App\Models\Category;
use App\Models\Destination;
use App\Models\TransportOption;
use App\Models\TransportRoute;
use App\Models\TravelTariff;
use App\Services\TourismMlService;
use App\Services\TripPlannerService;
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

    public function recommend(Request $request, TripPlannerService $planner, TourismMlService $ml)
    {
        $validated = $request->validate([
            'origin_name' => ['required', 'string', 'max:120'],
            'origin_latitude' => ['required', 'numeric', 'between:-90,90'],
            'origin_longitude' => ['required', 'numeric', 'between:-180,180'],
            'category_id' => ['nullable', Rule::exists('categories', 'id')],
            'budget' => ['required', 'integer', 'min:50000', 'max:100000000'],
            'days' => ['required', 'integer', 'between:1,7'],
            'travel_date' => ['nullable', 'date', 'after_or_equal:today'],
            'is_holiday' => ['sometimes', 'boolean'],
            'travelers' => ['required', 'integer', 'between:1,50'],
            'region' => ['nullable', 'string', 'max:120'],
            'min_rating' => ['nullable', 'numeric', 'between:0,5'],
            'max_distance' => ['nullable', 'integer', 'between:1,500'],
            'preference' => ['required', Rule::in(['balanced', 'rating', 'budget', 'distance'])],
            'transport_option_id' => ['nullable', Rule::exists('transport_options', 'id')->where('is_active', true)],
            'q' => ['nullable', 'string', 'max:120'],
            'include_accommodation' => ['nullable', 'boolean'],
        ]);
        $validated['include_accommodation'] = $request->boolean('include_accommodation');
        $validated['is_holiday'] = $request->boolean('is_holiday');
        $validated['travel_date'] = $validated['travel_date'] ?? now()->toDateString();

        $destinations = Destination::with('category')
            ->where('is_verified', true)
            ->whereNotNull('admission_fee')
            ->when(
                ! empty($validated['category_id']),
                fn ($query) => $query->where('category_id', $validated['category_id'])
            )
            ->get();
        $origin = [
            'name' => $validated['origin_name'],
            'latitude' => (float) $validated['origin_latitude'],
            'longitude' => (float) $validated['origin_longitude'],
        ];
        $recommendationScores = $ml->scoreDestinations(
            $destinations,
            $request->user(),
            [
                'category_id' => $validated['category_id'] ?? null,
                'budget' => $validated['budget'],
                'max_distance' => $validated['max_distance'] ?? null,
                'origin_latitude' => $origin['latitude'],
                'origin_longitude' => $origin['longitude'],
            ]
        );
        $costModel = $ml->costPredictor();
        $costPredictor = $costModel['count'] >= 12 ? $costModel['predict'] : null;
        $transport = ! empty($validated['transport_option_id'])
            ? TransportOption::where('is_active', true)->findOrFail($validated['transport_option_id'])
            : TransportOption::where('is_active', true)->where('type', 'car')->first();

        $plan = $planner->plan(
            $destinations,
            $origin,
            (int) $validated['budget'],
            (int) $validated['days'],
            (int) $validated['travelers'],
            $validated['include_accommodation'],
            [
                'region' => $validated['region'] ?? null,
                'min_rating' => (float) ($validated['min_rating'] ?? 0),
                'max_distance' => isset($validated['max_distance']) ? (int) $validated['max_distance'] : null,
                'preference' => $validated['preference'],
                'q' => $validated['q'] ?? null,
                'travel_date' => $validated['travel_date'],
                'recommendation_scores' => $recommendationScores,
            ],
            $transport,
            Accommodation::where('is_active', true)
                ->where('is_verified', true)
                ->whereNotNull('price_verified_at')
                ->where('price_per_night', '>', 0)
                ->get(),
            TravelTariff::all(),
            $costPredictor
        );
        $plan['cost_prediction'] = $plan['cost_prediction']
            ? array_merge($plan['cost_prediction'], [
                'training_rows' => $costModel['count'],
                'status' => 'Model terlatih dari biaya aktual yang dilaporkan wisatawan.',
            ])
            : [
                'group_total' => null,
                'per_person' => null,
                'training_rows' => $costModel['count'],
                'method' => null,
                'status' => 'Belum cukup data biaya aktual (minimal 12 perjalanan). Rincian tarif di bawah adalah estimasi aturan.',
            ];
        foreach ($plan['stops'] as &$stop) {
            $predictionDate = now()->parse($validated['travel_date'])->addDays($stop['day'] - 1)->toDateString();
            $stop['visitor_prediction'] = $ml->predictVisitors(
                (int) $stop['id'],
                $predictionDate,
                $stop['day'] === 1 && $validated['is_holiday']
            );
            $stop['visitor_prediction_date'] = $predictionDate;
            if ($stop['visitor_prediction']['count'] !== null) {
                $stop['recommendation_reasons'][] = 'Prediksi pengunjung sekitar '.number_format($stop['visitor_prediction']['count']).' orang';
            }
        }
        unset($stop);

        $draft = [
            'version' => 1,
            'plan' => $plan,
            'form' => $validated,
            'origin' => $origin,
            'recommendation_scores' => $recommendationScores,
        ];
        if ($plan['stops']) {
            $request->session()->put('trip_draft', $draft);
        } else {
            $request->session()->forget('trip_draft');
        }

        return $this->page($request, $plan, $validated, $origin, $recommendationScores);
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
