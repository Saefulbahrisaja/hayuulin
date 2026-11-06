<?php

namespace App\Services;

use App\Models\Accommodation;
use App\Models\Destination;
use App\Models\TransportOption;
use App\Models\TravelTariff;
use App\Models\User;
use Illuminate\Validation\Rule;

class TripRecommendationService
{
    public function __construct(
        private readonly TripPlannerService $planner,
        private readonly TourismMlService $ml
    ) {}

    public function recommend(array $form, ?User $user = null): array
    {
        $form['include_accommodation'] = filter_var($form['include_accommodation'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $form['is_holiday'] = filter_var($form['is_holiday'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $form['travel_date'] = $form['travel_date'] ?? now()->toDateString();

        $destinations = Destination::with('category')
            ->where('is_verified', true)
            ->whereNotNull('admission_fee')
            ->when(
                ! empty($form['category_id']),
                fn ($query) => $query->where('category_id', $form['category_id'])
            )
            ->get();
        $origin = [
            'name' => $form['origin_name'],
            'latitude' => (float) $form['origin_latitude'],
            'longitude' => (float) $form['origin_longitude'],
        ];
        $recommendationScores = $this->ml->scoreDestinations(
            $destinations,
            $user,
            [
                'category_id' => $form['category_id'] ?? null,
                'budget' => $form['budget'],
                'max_distance' => $form['max_distance'] ?? null,
                'origin_latitude' => $origin['latitude'],
                'origin_longitude' => $origin['longitude'],
            ]
        );
        $costModel = $this->ml->costPredictor();
        $costPredictor = $costModel['count'] >= 12 ? $costModel['predict'] : null;
        $transport = ! empty($form['transport_option_id'])
            ? TransportOption::where('is_active', true)->findOrFail($form['transport_option_id'])
            : TransportOption::where('is_active', true)->where('type', 'car')->first();

        $plan = $this->planner->plan(
            $destinations,
            $origin,
            (int) $form['budget'],
            (int) $form['days'],
            (int) $form['travelers'],
            $form['include_accommodation'],
            [
                'region' => $form['region'] ?? null,
                'min_rating' => (float) ($form['min_rating'] ?? 0),
                'max_distance' => isset($form['max_distance']) ? (int) $form['max_distance'] : null,
                'preference' => $form['preference'],
                'q' => $form['q'] ?? null,
                'travel_date' => $form['travel_date'],
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
            $predictionDate = now()->parse($form['travel_date'])->addDays($stop['day'] - 1)->toDateString();
            $stop['visitor_prediction'] = $this->ml->predictVisitors(
                (int) $stop['id'],
                $predictionDate,
                $stop['day'] === 1 && $form['is_holiday']
            );
            $stop['visitor_prediction_date'] = $predictionDate;
            if ($stop['visitor_prediction']['count'] !== null) {
                $stop['recommendation_reasons'][] = 'Prediksi pengunjung sekitar '.number_format($stop['visitor_prediction']['count']).' orang';
            }
        }
        unset($stop);

        return [
            'plan' => $plan,
            'form' => $form,
            'origin' => $origin,
            'recommendation_scores' => $recommendationScores,
        ];
    }

    public function plannerValidationRules(): array
    {
        return [
            'origin_name' => ['required', 'string', 'max:120'],
            'origin_latitude' => ['required', 'numeric', 'between:-90,90'],
            'origin_longitude' => ['required', 'numeric', 'between:-180,180'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'budget' => ['required', 'integer', 'min:50000', 'max:100000000'],
            'days' => ['required', 'integer', 'between:1,7'],
            'travel_date' => ['nullable', 'date', 'after_or_equal:today'],
            'is_holiday' => ['sometimes', 'boolean'],
            'travelers' => ['required', 'integer', 'between:1,50'],
            'region' => ['nullable', 'string', 'max:120'],
            'min_rating' => ['nullable', 'numeric', 'between:0,5'],
            'max_distance' => ['nullable', 'integer', 'between:1,500'],
            'preference' => ['required', 'in:balanced,rating,budget,distance'],
            'transport_option_id' => ['nullable', 'integer', Rule::exists('transport_options', 'id')->where('is_active', true)],
            'q' => ['nullable', 'string', 'max:120'],
            'include_accommodation' => ['sometimes', 'boolean'],
        ];
    }
}
