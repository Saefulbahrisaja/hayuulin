<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Accommodation;
use App\Models\Category;
use App\Models\Destination;
use App\Models\TransportOption;
use App\Models\TransportRoute;
use App\Models\Trip;
use App\Models\User;
use App\Services\NaiveBayesSentimentClassifier;
use App\Services\SmartTourismAssistant;
use App\Services\TripRecommendationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MobileApiController extends Controller
{
    public function categories()
    {
        return response()->json([
            'data' => Category::query()->withCount([
                'destinations' => fn ($query) => $query->where('is_verified', true),
            ])->orderBy('name')->get()->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'color' => $category->color,
                'icon' => $category->icon,
                'destination_count' => $category->destinations_count,
            ]),
        ]);
    }

    public function destinations(Request $request)
    {
        $filters = $request->validate([
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'region' => ['nullable', 'string', 'max:120'],
            'q' => ['nullable', 'string', 'max:120'],
            'min_rating' => ['nullable', 'numeric', 'between:0,5'],
            'per_page' => ['nullable', 'integer', 'between:1,50'],
        ]);

        $destinations = Destination::query()
            ->with('category')
            ->withCount([
                'reviews as positive_review_count' => fn ($query) => $query->where('sentiment', 'positif'),
                'reviews as neutral_review_count' => fn ($query) => $query->where('sentiment', 'netral'),
                'reviews as negative_review_count' => fn ($query) => $query->where('sentiment', 'negatif'),
            ])
            ->where('is_verified', true)
            ->when(isset($filters['category_id']), fn ($query) => $query->where('category_id', $filters['category_id']))
            ->when(isset($filters['region']), fn ($query) => $query->where('region', 'like', '%'.$filters['region'].'%'))
            ->when(isset($filters['q']), function ($query) use ($filters) {
                $query->where(function ($search) use ($filters) {
                    $search->where('name', 'like', '%'.$filters['q'].'%')
                        ->orWhere('address', 'like', '%'.$filters['q'].'%')
                        ->orWhere('region', 'like', '%'.$filters['q'].'%');
                });
            })
            ->when(isset($filters['min_rating']), fn ($query) => $query->where('rating', '>=', $filters['min_rating']))
            ->orderByDesc('rating')
            ->paginate($filters['per_page'] ?? 20);

        return response()->json([
            'data' => $destinations->getCollection()->map(fn (Destination $destination) => $this->destinationData($destination)),
            'meta' => $this->paginationData($destinations),
        ]);
    }

    public function showDestination(int $destinationId)
    {
        $destination = Destination::with('category')
            ->withCount([
                'reviews as positive_review_count' => fn ($query) => $query->where('sentiment', 'positif'),
                'reviews as neutral_review_count' => fn ($query) => $query->where('sentiment', 'netral'),
                'reviews as negative_review_count' => fn ($query) => $query->where('sentiment', 'negatif'),
            ])
            ->where('is_verified', true)
            ->findOrFail($destinationId);

        return response()->json(['data' => $this->destinationData($destination)]);
    }

    public function accommodations(Request $request)
    {
        $filters = $request->validate([
            'region' => ['nullable', 'string', 'max:120'],
            'type' => ['nullable', 'string', 'max:30'],
            'max_price' => ['nullable', 'integer', 'min:0'],
            'per_page' => ['nullable', 'integer', 'between:1,50'],
        ]);
        $accommodations = Accommodation::query()
            ->where('is_active', true)
            ->where('is_verified', true)
            ->when(isset($filters['region']), fn ($query) => $query->where('region', 'like', '%'.$filters['region'].'%'))
            ->when(isset($filters['type']), fn ($query) => $query->where('type', $filters['type']))
            ->when(isset($filters['max_price']), function ($query) use ($filters) {
                $query->whereNotNull('price_verified_at')
                    ->where('price_per_night', '<=', $filters['max_price']);
            })
            ->orderBy('region')
            ->orderBy('name')
            ->paginate($filters['per_page'] ?? 20);

        return response()->json([
            'data' => $accommodations->getCollection()->map(fn (Accommodation $stay) => [
                'id' => $stay->id,
                'name' => $stay->name,
                'type' => $stay->type,
                'region' => $stay->region,
                'address' => $stay->address,
                'latitude' => $stay->latitude,
                'longitude' => $stay->longitude,
                'price_per_night' => $stay->price_verified_at ? $stay->price_per_night : null,
                'price_verified' => (bool) $stay->price_verified_at,
                'capacity' => $stay->capacity,
                'rating' => $stay->rating,
                'amenities' => $stay->amenities,
            ]),
            'meta' => $this->paginationData($accommodations),
        ]);
    }

    public function transportOptions()
    {
        return response()->json([
            'data' => TransportOption::query()->where('is_active', true)->orderBy('name')->get()->map(fn (TransportOption $option) => [
                'id' => $option->id,
                'name' => $option->name,
                'type' => $option->type,
                'cost_per_km' => $option->cost_per_km,
                'capacity' => $option->capacity,
                'average_speed_kmh' => $option->average_speed_kmh,
                'description' => $option->description,
            ]),
        ]);
    }

    public function transportRoutes(Request $request)
    {
        $filters = $request->validate([
            'origin_region' => ['nullable', 'string', 'max:120'],
            'destination_region' => ['nullable', 'string', 'max:120'],
            'transport_option_id' => ['nullable', 'integer', 'exists:transport_options,id'],
            'per_page' => ['nullable', 'integer', 'between:1,50'],
        ]);
        $routes = TransportRoute::query()
            ->with('transportOption')
            ->where('is_active', true)
            ->whereHas('transportOption', fn ($query) => $query->where('is_active', true))
            ->when(isset($filters['origin_region']), fn ($query) => $query->where('origin_region', 'like', '%'.$filters['origin_region'].'%'))
            ->when(isset($filters['destination_region']), fn ($query) => $query->where('destination_region', 'like', '%'.$filters['destination_region'].'%'))
            ->when(isset($filters['transport_option_id']), fn ($query) => $query->where('transport_option_id', $filters['transport_option_id']))
            ->orderBy('origin_region')
            ->paginate($filters['per_page'] ?? 20);

        return response()->json([
            'data' => $routes->getCollection()->map(fn (TransportRoute $route) => [
                'id' => $route->id,
                'name' => $route->name,
                'transport' => [
                    'id' => $route->transportOption->id,
                    'name' => $route->transportOption->name,
                    'type' => $route->transportOption->type,
                ],
                'origin_region' => $route->origin_region,
                'destination_region' => $route->destination_region,
                'fare' => $route->fare,
                'duration_minutes' => $route->duration_minutes,
                'instructions' => $route->instructions,
            ]),
            'meta' => $this->paginationData($routes),
        ]);
    }

    public function recommend(Request $request, TripRecommendationService $recommendations)
    {
        $form = $request->validate($recommendations->plannerValidationRules());
        $result = $recommendations->recommend($form, $request->user());

        return response()->json(['data' => $result], 200);
    }

    public function storeTrip(Request $request, TripRecommendationService $recommendations)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            ...$recommendations->plannerValidationRules(),
        ]);
        $title = $validated['title'];
        unset($validated['title']);
        $result = $recommendations->recommend($validated, $request->user());
        $plan = $result['plan'];

        if (! $plan['stops']) {
            return response()->json([
                'message' => 'Belum ada destinasi yang cocok dengan pilihan perjalanan dan anggaran ini.',
            ], 422);
        }

        $trip = DB::transaction(function () use ($request, $title, $result, $plan) {
            /** @var User $user */
            $user = $request->user();
            $trip = $user->trips()->create([
                'title' => $title,
                'origin_name' => $result['origin']['name'],
                'origin_latitude' => $result['origin']['latitude'],
                'origin_longitude' => $result['origin']['longitude'],
                'days' => $result['form']['days'],
                'travelers' => $result['form']['travelers'],
                'budget_per_person' => $result['form']['budget'],
                'total_cost' => $plan['costs']['group_total'],
                'start_date' => $result['form']['travel_date'],
                'total_distance_km' => $plan['distance_km'],
                'total_duration_minutes' => $plan['total_minutes'],
                'transport_name' => $plan['transport']['name'],
                'accommodation_name' => $plan['accommodation']['name'] ?? null,
                'cost_breakdown' => $plan['costs'],
                'preferences' => [
                    'category_id' => $result['form']['category_id'] ?? null,
                    'region' => $result['form']['region'] ?? null,
                    'preference' => $result['form']['preference'],
                    'transport_option' => $plan['transport']['name'],
                    'transport_type' => $plan['transport']['type'] ?? '',
                    'accommodation' => $plan['accommodation']['name'] ?? null,
                ],
            ]);

            foreach ($plan['stops'] as $stop) {
                $trip->stops()->create([
                    'destination_id' => $stop['id'],
                    'day' => $stop['day'],
                    'sequence' => $stop['sequence'],
                    'destination_name' => $stop['name'],
                    'region' => $stop['region'],
                    'latitude' => $stop['latitude'],
                    'longitude' => $stop['longitude'],
                    'admission_fee' => $stop['admission_fee'],
                    'visit_duration_minutes' => $stop['visit_duration_minutes'],
                    'rating' => $stop['rating'],
                    'leg_distance_km' => $stop['leg_distance_km'],
                    'leg_minutes' => $stop['leg_minutes'],
                ]);
            }

            return $trip;
        });

        return response()->json([
            'data' => $this->tripData($trip->load('stops')),
        ], 201);
    }

    public function trips(Request $request)
    {
        $trips = $request->user()->trips()->with('stops')->latest()->paginate(20);

        return response()->json([
            'data' => $trips->getCollection()->map(fn (Trip $trip) => $this->tripData($trip)),
            'meta' => $this->paginationData($trips),
        ]);
    }

    public function showTrip(Request $request, int $tripId)
    {
        $trip = $request->user()->trips()->with('stops')->findOrFail($tripId);

        return response()->json(['data' => $this->tripData($trip)]);
    }

    public function updateActualCost(Request $request, int $tripId)
    {
        $validated = $request->validate([
            'actual_cost' => ['required', 'integer', 'min:1', 'max:1000000000'],
        ]);
        $trip = $request->user()->trips()->findOrFail($tripId);
        $trip->actual_cost = $validated['actual_cost'];
        $trip->save();

        return response()->json([
            'data' => ['id' => $trip->id, 'actual_cost' => $trip->actual_cost],
            'message' => 'Biaya aktual berhasil disimpan.',
        ]);
    }

    public function destroyTrip(Request $request, int $tripId)
    {
        $request->user()->trips()->findOrFail($tripId)->delete();

        return response()->json([], 204);
    }

    public function storeReview(Request $request, int $destinationId, NaiveBayesSentimentClassifier $classifier)
    {
        $destination = Destination::where('is_verified', true)->findOrFail($destinationId);
        $validated = $request->validate([
            'body' => ['required', 'string', 'min:10', 'max:2000'],
        ]);
        $sentiment = $classifier->classify($validated['body']);
        $review = $destination->reviews()->updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'body' => $validated['body'],
                'sentiment' => $sentiment['sentiment'],
                'sentiment_confidence' => $sentiment['confidence'],
            ]
        );

        return response()->json([
            'data' => [
                'id' => $review->id,
                'destination_id' => $destination->id,
                'body' => $review->body,
                'sentiment' => $review->sentiment,
                'sentiment_confidence' => $review->sentiment_confidence,
            ],
            'message' => 'Ulasan berhasil disimpan.',
        ]);
    }

    public function assistant(Request $request, SmartTourismAssistant $assistant)
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        return response()->json(['data' => $assistant->answer($validated['question'])]);
    }

    private function destinationData(Destination $destination): array
    {
        return [
            'id' => $destination->id,
            'name' => $destination->name,
            'slug' => $destination->slug,
            'description' => $destination->description,
            'address' => $destination->address,
            'region' => $destination->region,
            'coordinates' => [
                'latitude' => $destination->latitude,
                'longitude' => $destination->longitude,
            ],
            'category' => [
                'id' => $destination->category->id,
                'name' => $destination->category->name,
                'color' => $destination->category->color,
                'icon' => $destination->category->icon,
            ],
            'rating' => $destination->rating,
            'review_count' => $destination->review_count,
            'reviews_summary' => [
                'positive' => $destination->positive_review_count,
                'neutral' => $destination->neutral_review_count,
                'negative' => $destination->negative_review_count,
            ],
            'admission_fee' => $destination->admission_fee,
            'visit_duration_minutes' => $destination->visit_duration_minutes,
        ];
    }

    private function tripData(Trip $trip): array
    {
        return [
            'id' => $trip->id,
            'title' => $trip->title,
            'origin' => [
                'name' => $trip->origin_name,
                'latitude' => $trip->origin_latitude,
                'longitude' => $trip->origin_longitude,
            ],
            'days' => $trip->days,
            'travelers' => $trip->travelers,
            'budget_per_person' => $trip->budget_per_person,
            'total_cost' => $trip->total_cost,
            'actual_cost' => $trip->actual_cost,
            'start_date' => $trip->start_date?->toDateString(),
            'total_distance_km' => $trip->total_distance_km,
            'total_duration_minutes' => $trip->total_duration_minutes,
            'transport_name' => $trip->transport_name,
            'accommodation_name' => $trip->accommodation_name,
            'cost_breakdown' => $trip->cost_breakdown,
            'preferences' => $trip->preferences,
            'stops' => $trip->stops->map(fn ($stop) => [
                'id' => $stop->id,
                'destination_id' => $stop->destination_id,
                'day' => $stop->day,
                'sequence' => $stop->sequence,
                'destination_name' => $stop->destination_name,
                'region' => $stop->region,
                'coordinates' => [
                    'latitude' => (float) $stop->latitude,
                    'longitude' => (float) $stop->longitude,
                ],
                'admission_fee' => $stop->admission_fee,
                'visit_duration_minutes' => $stop->visit_duration_minutes,
                'rating' => (float) $stop->rating,
                'leg_distance_km' => (float) $stop->leg_distance_km,
                'leg_minutes' => $stop->leg_minutes,
            ])->values(),
            'created_at' => $trip->created_at?->toISOString(),
        ];
    }

    private function paginationData($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}
