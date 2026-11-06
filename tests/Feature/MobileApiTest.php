<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Destination;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class MobileApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_mobile_registration_creates_a_tourist_token_that_can_be_revoked(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Wisatawan Mobile',
            'email' => 'mobile-'.uniqid().'@example.test',
            'password' => 'long-mobile-password',
            'password_confirmation' => 'long-mobile-password',
            'device_name' => 'Android test',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.name', 'Wisatawan Mobile')
            ->assertJsonMissingPath('data.user.is_admin');

        $token = $response->json('data.access_token');
        $this->withToken($token)->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.name', 'Wisatawan Mobile');
        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $response->json('data.user.id')]);
        Auth::forgetGuards();
        $this->withToken($token)->getJson('/api/v1/auth/me')->assertUnauthorized();

        $this->postJson('/api/v1/auth/login', [
            'email' => $response->json('data.user.email'),
            'password' => 'long-mobile-password',
            'device_name' => 'Android test',
        ])->assertOk()->assertJsonPath('data.user.id', $response->json('data.user.id'));
    }

    public function test_mobile_authentication_rejects_admin_accounts_and_admin_tokens(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->postJson('/api/v1/auth/login', [
            'email' => $admin->email,
            'password' => 'password',
            'device_name' => 'Admin device',
        ])->assertForbidden();

        $adminToken = $admin->createToken('test-admin')->plainTextToken;
        $this->withToken($adminToken)->getJson('/api/v1/auth/me')->assertForbidden();
    }

    public function test_public_catalog_only_returns_verified_destinations(): void
    {
        $verified = $this->createDestination(true);
        $this->createDestination(false);

        $this->getJson('/api/v1/categories')->assertOk();
        $this->getJson('/api/v1/destinations?region=ApiTest-'.substr($verified->slug, -13))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $verified->id)
            ->assertJsonPath('data.0.category.name', $verified->category->name);
        $this->getJson('/api/v1/destinations/'.$verified->id)->assertOk();
    }

    public function test_mobile_trips_are_recalculated_and_private_to_their_owner(): void
    {
        $destination = $this->createDestination(true);
        $traveler = User::factory()->create();
        $token = $traveler->createToken('test-mobile', ['tourist'])->plainTextToken;
        $form = $this->plannerForm($destination);

        $this->withToken($token)->postJson('/api/v1/trips/recommend', $form)
            ->assertOk()
            ->assertJsonPath('data.plan.stops.0.id', $destination->id);

        $response = $this->withToken($token)->postJson('/api/v1/trips', [
            ...$form,
            'title' => 'Perjalanan mobile',
            'plan' => ['stops' => []],
        ]);
        $response->assertCreated()
            ->assertJsonPath('data.title', 'Perjalanan mobile')
            ->assertJsonPath('data.stops.0.destination_id', $destination->id);

        $tripId = $response->json('data.id');
        $this->withToken($token)->getJson('/api/v1/trips')->assertOk()->assertJsonPath('meta.total', 1);
        $this->withToken($token)->getJson('/api/v1/trips/'.$tripId)->assertOk();

        $otherTraveler = User::factory()->create();
        $otherToken = $otherTraveler->createToken('other-mobile', ['tourist'])->plainTextToken;
        Auth::forgetGuards();
        $this->withToken($otherToken)->getJson('/api/v1/trips/'.$tripId)->assertNotFound();
        Auth::forgetGuards();
        $this->withToken($otherToken)->deleteJson('/api/v1/trips/'.$tripId)->assertNotFound();
    }

    public function test_mobile_routes_require_a_valid_tourist_token_and_validate_trip_inputs(): void
    {
        $this->getJson('/api/v1/trips')->assertUnauthorized();

        $traveler = User::factory()->create();
        $token = $traveler->createToken('test-mobile', ['tourist'])->plainTextToken;
        $this->withToken($token)->postJson('/api/v1/trips/recommend', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['origin_name', 'budget', 'days', 'travelers', 'preference']);
    }

    private function createDestination(bool $verified): Destination
    {
        $category = Category::firstOrCreate(
            ['slug' => 'mobile-api-test'],
            ['name' => 'Wisata API', 'color' => '#287A66', 'icon' => 'leaf']
        );
        $slug = uniqid();

        return Destination::create([
            'category_id' => $category->id,
            'name' => $verified ? 'Wisata Terverifikasi' : 'Wisata Draf',
            'slug' => ($verified ? 'wisata-terverifikasi-' : 'wisata-draf-').$slug,
            'description' => 'Tempat wisata untuk pengujian API.',
            'address' => 'Sukabumi',
            'region' => 'ApiTest-'.substr($slug, -13),
            'latitude' => -6.84,
            'longitude' => 106.92,
            'rating' => 4.5,
            'review_count' => 5,
            'admission_fee' => 5000,
            'visit_duration_minutes' => 120,
            'is_verified' => $verified,
        ]);
    }

    private function plannerForm(Destination $destination): array
    {
        return [
            'origin_name' => 'Kota Sukabumi',
            'origin_latitude' => $destination->latitude,
            'origin_longitude' => $destination->longitude,
            'category_id' => $destination->category_id,
            'budget' => 1000000,
            'days' => 1,
            'travel_date' => now()->toDateString(),
            'travelers' => 2,
            'preference' => 'balanced',
        ];
    }
}
