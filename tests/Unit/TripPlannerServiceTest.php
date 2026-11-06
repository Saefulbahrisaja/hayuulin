<?php

namespace Tests\Unit;

use App\Models\Accommodation;
use App\Models\Category;
use App\Models\Destination;
use App\Models\TransportOption;
use App\Services\TripPlannerService;
use PHPUnit\Framework\TestCase;

class TripPlannerServiceTest extends TestCase
{
    public function test_it_returns_a_budget_aware_route_with_every_selected_stop(): void
    {
        $destinations = collect([
            $this->destination(1, 'Danau', -6.84, 106.92, 4.8, 25000),
            $this->destination(2, 'Air Terjun', -6.85, 106.93, 4.6, 10000),
            $this->destination(3, 'Pantai', -7.00, 106.55, 4.9, 40000),
        ]);
        $origin = ['name' => 'Kota Sukabumi', 'latitude' => -6.92, 'longitude' => 106.92];

        $plan = (new TripPlannerService)->plan($destinations, $origin, 1000000, 1, 2, false);

        $this->assertCount(2, $plan['stops']);
        $this->assertSame([1, 2], array_column($plan['stops'], 'sequence'));
        $this->assertSame(1, $plan['stops'][0]['day']);
        $this->assertLessThanOrEqual(1000000, $plan['costs']['per_person']);
        $this->assertLessThanOrEqual(8 * 60, $plan['total_minutes']);
        $this->assertLessThanOrEqual(8 * 60, max($plan['daily_minutes']));
        $this->assertGreaterThan(0, $plan['distance_km']);
    }

    public function test_it_returns_no_stops_when_even_the_first_destination_exceeds_the_budget(): void
    {
        $destinations = collect([
            $this->destination(1, 'Pulau', -7.3, 106.4, 4.9, 500000),
        ]);
        $origin = ['name' => 'Kota Sukabumi', 'latitude' => -6.92, 'longitude' => 106.92];

        $plan = (new TripPlannerService)->plan($destinations, $origin, 50000, 1, 1, false);

        $this->assertSame([], $plan['stops']);
        $this->assertFalse($plan['within_budget']);
    }

    public function test_it_splits_a_multiday_route_into_days_that_fit_the_time_limit(): void
    {
        $destinations = collect([
            $this->destination(1, 'Pelabuhan', -6.9875, 106.5510, 4.9, 10000),
            $this->destination(2, 'Curug', -7.3612, 106.6510, 4.8, 10000),
        ]);
        $origin = ['name' => 'Kota Sukabumi', 'latitude' => -6.92, 'longitude' => 106.92];

        $plan = (new TripPlannerService)->plan($destinations, $origin, 1000000, 2, 2, false);

        $this->assertCount(2, $plan['stops']);
        $this->assertCount(2, array_unique(array_column($plan['stops'], 'day')));
        foreach ($plan['daily_minutes'] as $duration) {
            $this->assertLessThanOrEqual(8 * 60, $duration);
        }
    }

    public function test_preference_and_distance_filters_control_recommendations(): void
    {
        $destinations = collect([
            $this->destination(1, 'Dekat', -6.921, 106.921, 4.1, 10000),
            $this->destination(2, 'Jauh', -7.35, 106.4, 4.9, 50000),
        ]);
        $origin = ['name' => 'Kota Sukabumi', 'latitude' => -6.92, 'longitude' => 106.92];

        $plan = (new TripPlannerService)->plan($destinations, $origin, 1000000, 1, 1, false, [
            'preference' => 'rating',
            'min_rating' => 4.5,
            'max_distance' => 5,
        ]);

        $this->assertSame([], $plan['stops']);
    }

    public function test_selected_transport_and_local_tariffs_update_the_group_cost(): void
    {
        $destinations = collect([$this->destination(1, 'Danau', -6.84, 106.92, 4.8, 25000)]);
        $origin = ['name' => 'Kota Sukabumi', 'latitude' => -6.92, 'longitude' => 106.92];
        $transport = new TransportOption([
            'name' => 'Angkutan uji',
            'type' => 'public',
            'cost_per_km' => 1000,
            'capacity' => 1,
            'average_speed_kmh' => 25,
        ]);
        $stay = new Accommodation([
            'name' => 'Homestay Uji',
            'region' => 'Kadudampit',
            'price_per_night' => 100000,
            'capacity' => 2,
            'rating' => 4.5,
        ]);
        $tariffs = collect([
            (object) ['key' => 'meal_per_stop', 'amount' => 20000],
            (object) ['key' => 'parking_per_stop', 'amount' => 10000],
        ]);

        $plan = (new TripPlannerService)->plan($destinations, $origin, 1000000, 2, 2, true, [], $transport, collect([$stay]), $tariffs);

        $this->assertSame('Angkutan uji', $plan['transport']['name']);
        $this->assertSame('Homestay Uji', $plan['accommodation']['name']);
        $this->assertSame(100000, $plan['costs']['accommodation']);
        $this->assertSame(0, $plan['costs']['parking']);
        $this->assertSame(40000, $plan['costs']['meals']);
    }

    private function destination(
        int $id,
        string $name,
        float $latitude,
        float $longitude,
        float $rating,
        int $admissionFee
    ): Destination {
        $destination = new Destination([
            'name' => $name,
            'region' => 'Kadudampit',
            'address' => 'Sukabumi',
            'description' => 'Destinasi uji.',
            'latitude' => $latitude,
            'longitude' => $longitude,
            'rating' => $rating,
            'admission_fee' => $admissionFee,
            'visit_duration_minutes' => 120,
        ]);
        $destination->setAttribute('id', $id);
        $destination->setRelation('category', new Category([
            'name' => 'Wisata',
            'color' => '#557b68',
        ]));

        return $destination;
    }
}
