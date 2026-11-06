<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Destination;
use App\Models\DestinationVisitMetric;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TourismManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_public_registration_creates_a_non_admin_traveler(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Wisatawan Test',
            'email' => 'tourist-'.uniqid().'@example.test',
            'password' => 'long-test-password',
            'password_confirmation' => 'long-test-password',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticated();
        $this->assertFalse(auth()->user()->is_admin);
    }

    public function test_admin_catalog_is_forbidden_to_regular_users(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_admin_can_create_catalog_records(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $slug = 'uji-'.uniqid();

        $this->post(route('admin.catalog.store', 'categories'), [
            'name' => 'Kategori Uji',
            'slug' => $slug,
            'color' => '#287A66',
            'icon' => 'leaf',
        ])->assertRedirect(route('admin.catalog.index', 'categories'));

        $this->assertDatabaseHas('categories', ['slug' => $slug]);
    }

    public function test_admin_can_update_user_role_without_removing_the_last_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $traveler = User::factory()->create();
        $this->actingAs($admin);

        $this->put(route('admin.users.update', $traveler), [
            'name' => $traveler->name,
            'email' => $traveler->email,
            'is_admin' => '1',
        ])->assertRedirect(route('admin.dashboard'));
        $this->assertTrue($traveler->fresh()->is_admin);

        $this->put(route('admin.users.update', $traveler), [
            'name' => $traveler->name,
            'email' => $traveler->email,
            'is_admin' => '0',
        ])->assertRedirect(route('admin.dashboard'));

        $hasAnotherAdmin = User::where('is_admin', true)->whereKeyNot($admin->id)->exists();
        $response = $this->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'is_admin' => '0',
        ]);
        if (! $hasAnotherAdmin) {
            $response->assertSessionHasErrors('is_admin');
            $this->assertTrue($admin->fresh()->is_admin);
        } else {
            $response->assertRedirect(route('admin.dashboard'));
            $this->assertFalse($admin->fresh()->is_admin);
        }
    }

    public function test_a_traveler_can_save_and_only_view_their_own_trip(): void
    {
        $user = User::factory()->create();
        $category = Category::create([
            'name' => 'Uji Perjalanan',
            'slug' => 'uji-perjalanan-'.uniqid(),
            'color' => '#287A66',
            'icon' => 'leaf',
        ]);
        $destination = Destination::create([
            'category_id' => $category->id,
            'name' => 'Wisata Uji',
            'slug' => 'wisata-uji-'.uniqid(),
            'description' => 'Destinasi untuk pengujian.',
            'address' => 'Sukabumi',
            'region' => 'Kadudampit',
            'latitude' => -6.84,
            'longitude' => 106.92,
            'rating' => 4.5,
            'review_count' => 1,
            'admission_fee' => 10000,
            'visit_duration_minutes' => 120,
        ]);
        $draft = [
            'version' => 1,
            'origin' => ['name' => 'Kota Sukabumi', 'latitude' => -6.92, 'longitude' => 106.92],
            'form' => ['days' => 1, 'travelers' => 2, 'budget' => 500000, 'category_id' => $category->id],
            'plan' => [
                'stops' => [[
                    'id' => $destination->id,
                    'day' => 1,
                    'sequence' => 1,
                    'name' => $destination->name,
                    'region' => $destination->region,
                    'latitude' => -6.84,
                    'longitude' => 106.92,
                    'admission_fee' => 10000,
                    'visit_duration_minutes' => 120,
                    'rating' => 4.5,
                    'leg_distance_km' => 5.2,
                    'leg_minutes' => 12,
                ]],
                'daily_travel_minutes' => [1 => 60],
                'daily_minutes' => [1 => 180],
                'visit_minutes' => 120,
                'total_minutes' => 180,
                'distance_km' => 34.2,
                'costs' => ['transport' => 100000, 'tickets' => 20000, 'meals' => 70000, 'accommodation' => 0, 'parking' => 10000, 'group_total' => 200000],
                'transport' => ['name' => 'Mobil pribadi'],
                'accommodation' => null,
            ],
        ];

        $this->actingAs($user)->withSession(['trip_draft' => $draft])
            ->post(route('trips.draft.update'), ['durations' => [$destination->id => 180]])
            ->assertRedirect(route('home').'#rencana');

        $this->actingAs($user)
            ->post(route('trips.store'), ['title' => 'Liburan uji'])
            ->assertRedirect();
        $trip = Trip::where('user_id', $user->id)->firstOrFail();
        $this->assertSame(1, $trip->stops()->count());
        $this->assertSame(180, $trip->stops()->first()->visit_duration_minutes);
        $this->actingAs($user)->get(route('trips.show', $trip))
            ->assertOk()
            ->assertSee('Wisata Uji')
            ->assertSee('summary-grid')
            ->assertSee('summary-card-total')
            ->assertSee('actual-cost-form');

        $otherUser = User::factory()->create();
        $this->actingAs($otherUser)->get(route('trips.show', $trip))->assertForbidden();
    }

    public function test_custom_visit_durations_cannot_exceed_the_daily_time_limit(): void
    {
        $user = User::factory()->create();
        $draft = [
            'version' => 1,
            'plan' => [
                'stops' => [['id' => 123, 'day' => 1, 'visit_duration_minutes' => 120]],
                'daily_travel_minutes' => [1 => 90],
                'daily_minutes' => [1 => 210],
                'visit_minutes' => 120,
                'total_minutes' => 210,
            ],
        ];

        $this->actingAs($user)->withSession(['trip_draft' => $draft])
            ->post(route('trips.draft.update'), ['durations' => [123 => 480]])
            ->assertSessionHasErrors('durations');
    }

    public function test_admin_dashboard_and_catalog_forms_render(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->get(route('admin.dashboard'))->assertOk()->assertSee('Dashboard administrator');
        $this->get(route('admin.catalog.create', 'transport-routes'))->assertOk()->assertSee('Petunjuk dan titik pemberhentian');
        $this->get(route('admin.catalog.create', 'accommodations'))->assertOk()->assertSee('Harga per malam');
    }

    public function test_admin_can_preview_and_import_openstreetmap_places_as_unverified(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $category = Category::firstOrCreate(
            ['slug' => 'alam'],
            ['name' => 'Alam', 'color' => '#287A66', 'icon' => 'leaf']
        );
        Http::fake([
            '*' => Http::response([
                'elements' => [
                    [
                        'type' => 'node',
                        'id' => 123,
                        'lat' => -6.84,
                        'lon' => 106.92,
                        'tags' => ['name' => 'Curug Uji', 'tourism' => 'attraction'],
                    ],
                    [
                        'type' => 'way',
                        'id' => 456,
                        'center' => ['lat' => -6.85, 'lon' => 106.93],
                        'tags' => ['name' => 'Hotel Uji', 'tourism' => 'hotel'],
                    ],
                    [
                        'type' => 'node',
                        'id' => 789,
                        'lat' => -6.84,
                        'lon' => 106.92,
                        'tags' => ['tourism' => 'attraction'],
                    ],
                ],
            ]),
        ]);
        $this->actingAs($admin);

        $this->post(route('admin.open-data.preview'), ['refresh' => '1', 'region' => 'Sukabumi'])
            ->assertRedirect(route('admin.open-data.index'))
            ->assertSessionHas('status_type', 'success');
        $this->get(route('admin.open-data.index'))
            ->assertOk()
            ->assertSee('Curug Uji')
            ->assertSee('Hotel Uji')
            ->assertSee('OpenStreetMap contributors')
            ->assertSee('data-osm-progress', false)
            ->assertSee('Sedang mencari...', false)
            ->assertSee('Sedang menyimpan...', false)
            ->assertSee('Pilih setidaknya satu tempat sebelum mengimpor.');
        Http::assertSent(fn ($request) => str_contains($request['data'], 'Kabupaten Sukabumi')
            && str_contains($request['data'], 'Kota Sukabumi')
            && str_contains($request['data'], '[timeout:10]'));

        $this->post(route('admin.open-data.import'), [
            'source_ids' => ['node/123', 'way/456'],
        ])->assertRedirect(route('admin.open-data.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('destinations', [
            'name' => 'Curug Uji',
            'source_provider' => 'openstreetmap',
            'source_id' => 'node/123',
            'is_verified' => false,
            'rating' => 0,
            'admission_fee' => null,
            'category_id' => $category->id,
        ]);
        $this->assertDatabaseHas('accommodations', [
            'name' => 'Hotel Uji',
            'source_provider' => 'openstreetmap',
            'source_id' => 'way/456',
            'is_verified' => false,
            'price_per_night' => 0,
            'price_verified_at' => null,
        ]);
        $this->get(route('home'))->assertDontSee('Curug Uji')->assertDontSee('Hotel Uji');
    }

    public function test_empty_openstreetmap_preview_is_explained_as_an_informational_result(): void
    {
        Http::fake([
            '*' => Http::response(['elements' => []]),
        ]);

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)
            ->post(route('admin.open-data.preview'), ['refresh' => '1', 'region' => 'Sukabumi'])
            ->assertRedirect(route('admin.open-data.index'))
            ->assertSessionHas('status_type', 'info')
            ->assertSessionHas('osm_preview_completed', true)
            ->assertSessionHas('status', 'Pencarian selesai, tetapi belum ada tempat wisata atau penginapan yang ditemukan untuk wilayah Sukabumi. Periksa ejaan wilayah atau coba nama kabupaten/kota seperti “Kota Bandung”. Tidak ada data yang diimpor.');

        $this->get(route('admin.open-data.index'))
            ->assertOk()
            ->assertSee('flash-info')
            ->assertSee('belum ada tempat yang dapat ditampilkan')
            ->assertDontSee('0 data tempat ditemukan dari OpenStreetMap');
    }

    public function test_openstreetmap_preview_accepts_another_city_and_keeps_its_region_with_the_results(): void
    {
        Http::fake([
            '*' => Http::response([
                'elements' => [
                    [
                        'type' => 'node',
                        'id' => 654,
                        'lat' => -6.91,
                        'lon' => 107.61,
                        'tags' => ['name' => 'Taman Uji Bandung', 'tourism' => 'attraction'],
                    ],
                ],
            ]),
        ]);

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)
            ->post(route('admin.open-data.preview'), ['refresh' => '1', 'region' => 'Bandung'])
            ->assertRedirect(route('admin.open-data.index'))
            ->assertSessionHas('status_type', 'success');

        Http::assertSent(fn ($request) => str_contains($request['data'], '"Kabupaten Bandung"')
            && str_contains($request['data'], '"Kota Bandung"')
            && str_contains($request['data'], '"Indonesia"'));

        $this->get(route('admin.open-data.index'))
            ->assertOk()
            ->assertSee('Taman Uji Bandung')
            ->assertSee('value="Bandung"', false)
            ->assertSee('Tambah destinasi manual')
            ->assertSee('Tambah penginapan manual');
        $this->assertSame(
            'Bandung',
            app(\App\Services\OpenStreetMapImportService::class)->cachedPreview($admin->id)[0]['region']
        );
    }

    public function test_openstreetmap_region_rejects_query_syntax_instead_of_sending_it_to_overpass(): void
    {
        Http::fake();
        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->from(route('admin.open-data.index'))
            ->post(route('admin.open-data.preview'), [
                'refresh' => '1',
                'region' => 'Bandung");way;out;',
            ])
            ->assertRedirect(route('admin.open-data.index'))
            ->assertSessionHasErrors('region');

        Http::assertNothingSent();
    }

    public function test_openstreetmap_preview_uses_an_alternate_service_when_the_primary_is_busy(): void
    {
        $sequence = Http::sequence()
            ->push('Overpass is busy', 429)
            ->push([
                'elements' => [
                    [
                        'type' => 'node',
                        'id' => 987,
                        'lat' => -6.84,
                        'lon' => 106.92,
                        'tags' => ['name' => 'Curug Cadangan', 'tourism' => 'attraction'],
                    ],
                ],
            ]);
        Http::fake(['*' => $sequence]);

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)
            ->post(route('admin.open-data.preview'), ['refresh' => '1', 'region' => 'Sukabumi'])
            ->assertRedirect(route('admin.open-data.index'))
            ->assertSessionHas('status_type', 'success');

        Http::assertSentCount(2);
        $this->get(route('admin.open-data.index'))
            ->assertOk()
            ->assertSee('Curug Cadangan')
            ->assertSee('1 tempat ditemukan');
    }

    public function test_openstreetmap_preview_shows_a_friendly_warning_when_all_services_are_busy(): void
    {
        $sequence = Http::sequence();
        foreach (config('services.openstreetmap.overpass_urls') as $endpoint) {
            $sequence->push('Overpass is busy', 429);
        }
        Http::fake(['*' => $sequence]);

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)
            ->post(route('admin.open-data.preview'), ['refresh' => '1', 'region' => 'Sukabumi'])
            ->assertRedirect(route('admin.open-data.index'))
            ->assertSessionHas('status_type', 'warning')
            ->assertSessionHas('status', 'Layanan peta sedang sibuk dan belum bisa mengambil data terbaru. Tidak ada data yang diubah. Silakan coba lagi beberapa menit lagi.');

        Http::assertSentCount(count(config('services.openstreetmap.overpass_urls')));
        $this->get(route('admin.open-data.index'))
            ->assertOk()
            ->assertSee('flash-warning')
            ->assertSee('Tidak ada data yang diubah')
            ->assertDontSee('Layanan Overpass menolak permintaan');
    }

    public function test_openstreetmap_preview_never_attempts_more_than_three_services(): void
    {
        config([
            'services.openstreetmap.overpass_urls' => [
                'https://overpass-one.example/api',
                'https://overpass-two.example/api',
                'https://overpass-three.example/api',
                'https://overpass-four.example/api',
                'https://overpass-five.example/api',
            ],
        ]);
        $sequence = Http::sequence()
            ->push('Overpass is busy', 429)
            ->push('Overpass is busy', 429)
            ->push('Overpass is busy', 429);
        Http::fake(['*' => $sequence]);

        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->post(route('admin.open-data.preview'), ['refresh' => '1', 'region' => 'Sukabumi'])
            ->assertRedirect(route('admin.open-data.index'))
            ->assertSessionHas('status_type', 'warning');

        Http::assertSentCount(3);
    }

    public function test_accommodation_price_is_not_marked_verified_without_admin_confirmation(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $attributes = [
            'name' => 'Penginapan Uji',
            'type' => 'hotel',
            'region' => 'Sukabumi',
            'address' => 'Sukabumi',
            'price_per_night' => 250000,
            'capacity' => 2,
            'rating' => 0,
            'is_active' => '1',
            'is_verified' => '1',
        ];

        $this->post(route('admin.catalog.store', 'accommodations'), $attributes)
            ->assertRedirect(route('admin.catalog.index', 'accommodations'));
        $this->assertDatabaseHas('accommodations', [
            'name' => 'Penginapan Uji',
            'is_verified' => true,
            'price_verified_at' => null,
        ]);
        $this->get(route('home'))->assertSee('Penginapan Uji')->assertSee('Harga belum dikonfirmasi');

        $this->post(route('admin.catalog.store', 'accommodations'), [
            ...$attributes,
            'name' => 'Penginapan Harga Terkonfirmasi',
            'price_verified' => '1',
        ])->assertRedirect(route('admin.catalog.index', 'accommodations'));
        $verifiedRate = \App\Models\Accommodation::where('name', 'Penginapan Harga Terkonfirmasi')->firstOrFail();
        $this->assertNotNull($verifiedRate->price_verified_at);
        $this->get(route('admin.catalog.edit', ['accommodations', $verifiedRate->id]))
            ->assertOk()
            ->assertSee('name="price_verified" value="1" checked', false);

        $this->post(route('admin.catalog.store', 'accommodations'), [
            ...$attributes,
            'name' => 'Penginapan Tanpa Tarif',
            'price_per_night' => 0,
            'price_verified' => '1',
        ])->assertSessionHasErrors('price_per_night');
    }

    public function test_authenticated_traveler_reviews_are_analyzed_and_saved(): void
    {
        $category = Category::create([
            'name' => 'Kategori Ulasan '.uniqid(),
            'slug' => 'kategori-ulasan-'.uniqid(),
            'color' => '#287A66',
            'icon' => 'leaf',
        ]);
        $destination = Destination::create([
            'category_id' => $category->id,
            'name' => 'Wisata Ulasan',
            'slug' => 'wisata-ulasan-'.uniqid(),
            'description' => 'Destinasi uji ulasan.',
            'address' => 'Sukabumi',
            'region' => 'Sukabumi',
            'latitude' => -6.92,
            'longitude' => 106.92,
            'rating' => 0,
            'review_count' => 0,
            'admission_fee' => 10000,
            'visit_duration_minutes' => 60,
            'is_verified' => true,
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('destinations.reviews.store', $destination), [
                'body' => 'Tempat sangat indah dan menyenangkan, pengalaman luar biasa.',
            ])
            ->assertRedirect(route('home').'#destinasi')
            ->assertSessionHas('review_status');

        $this->assertDatabaseHas('destination_reviews', [
            'destination_id' => $destination->id,
            'user_id' => $user->id,
            'sentiment' => 'positif',
        ]);
        $this->get(route('home'))->assertSee('Ulasan tersimpan');
    }

    public function test_admin_can_save_verified_destination_visit_metrics(): void
    {
        $category = Category::create([
            'name' => 'Kategori Metrik '.uniqid(),
            'slug' => 'kategori-metrik-'.uniqid(),
            'color' => '#287A66',
            'icon' => 'leaf',
        ]);
        $destination = Destination::create([
            'category_id' => $category->id,
            'name' => 'Wisata Metrik',
            'slug' => 'wisata-metrik-'.uniqid(),
            'description' => 'Destinasi uji metrik.',
            'address' => 'Sukabumi',
            'region' => 'Sukabumi',
            'latitude' => -6.92,
            'longitude' => 106.92,
            'rating' => 0,
            'review_count' => 0,
            'admission_fee' => 10000,
            'visit_duration_minutes' => 60,
            'is_verified' => true,
        ]);
        $admin = User::factory()->create(['is_admin' => true]);
        $date = now()->subDay()->toDateString();

        $this->actingAs($admin)
            ->post(route('admin.machine-learning.visits.store'), [
                'destination_id' => $destination->id,
                'visited_on' => $date,
                'visitor_count' => 125,
                'source' => 'Laporan pengelola',
                'is_holiday' => '1',
            ])
            ->assertRedirect(route('admin.machine-learning.index'));

        $this->assertDatabaseHas('destination_visit_metrics', [
            'destination_id' => $destination->id,
            'visited_on' => $date,
            'visitor_count' => 125,
            'is_holiday' => true,
            'recorded_by' => $admin->id,
        ]);
        $this->actingAs($admin)->get(route('admin.machine-learning.index'))->assertOk()
            ->assertSee('Bagaimana perkiraan dan rekomendasi dibuat')
            ->assertDontSee('Random Forest Regression');
    }

    public function test_recommendation_uses_ml_score_and_clusters_before_aco_planning(): void
    {
        $category = Category::create([
            'name' => 'Kategori Rekomendasi '.uniqid(),
            'slug' => 'kategori-rekomendasi-'.uniqid(),
            'color' => '#287A66',
            'icon' => 'leaf',
        ]);
        $destination = Destination::create([
            'category_id' => $category->id,
            'name' => 'Wisata Kandidat ML',
            'slug' => 'wisata-kandidat-ml-'.uniqid(),
            'description' => 'Kandidat terverifikasi untuk pengujian.',
            'address' => 'Sukabumi',
            'region' => 'Sukabumi',
            'latitude' => -6.92,
            'longitude' => 106.92,
            'rating' => 4.5,
            'review_count' => 0,
            'admission_fee' => 10000,
            'visit_duration_minutes' => 60,
            'is_verified' => true,
        ]);
        $reportingUser = User::factory()->create();
        for ($index = 1; $index <= 12; $index++) {
            $date = now()->subDays($index)->toDateString();
            DestinationVisitMetric::create([
                'destination_id' => $destination->id,
                'visited_on' => $date,
                'visitor_count' => 80 + $index * 5,
                'is_holiday' => $index % 7 === 0,
                'source' => 'Laporan uji',
            ]);
            Trip::create([
                'user_id' => $reportingUser->id,
                'title' => 'Trip latih '.$index,
                'origin_name' => 'Sukabumi',
                'origin_latitude' => -6.92,
                'origin_longitude' => 106.92,
                'days' => 1 + ($index % 3),
                'travelers' => 1 + ($index % 4),
                'budget_per_person' => 500000,
                'total_cost' => 100000 + ($index * 1000),
                'actual_cost' => 105000 + ($index * 1200),
                'start_date' => $date,
                'total_distance_km' => 10 + ($index * 2),
                'total_duration_minutes' => 180,
                'transport_name' => 'Mobil',
                'cost_breakdown' => [],
                'preferences' => ['transport_type' => 'car'],
            ]);
        }

        $this->post(route('trips.recommend'), [
            'origin_name' => 'Kota Sukabumi',
            'origin_latitude' => -6.9193,
            'origin_longitude' => 106.9267,
            'category_id' => $category->id,
            'budget' => 1000000,
            'days' => 1,
            'travelers' => 2,
            'preference' => 'balanced',
            'travel_date' => now()->toDateString(),
        ])->assertOk()
            ->assertSee('Lama berwisata di sini (menit)')
            ->assertSee('malam menginap dihitung terpisah')
            ->assertSee('2 hari = 1 malam')
            ->assertSee('Perkiraan jumlah pengunjung')
            ->assertDontSee('Kunjungan (menit)')
            ->assertDontSee('Random Forest Regression')
            ->assertDontSee('klaster');
    }

    public function test_trip_owner_can_report_actual_cost_for_regression_training(): void
    {
        $user = User::factory()->create();
        $trip = Trip::create([
            'user_id' => $user->id,
            'title' => 'Trip Biaya Aktual',
            'origin_name' => 'Sukabumi',
            'origin_latitude' => -6.92,
            'origin_longitude' => 106.92,
            'days' => 2,
            'travelers' => 2,
            'budget_per_person' => 500000,
            'total_cost' => 200000,
            'total_distance_km' => 30,
            'total_duration_minutes' => 180,
            'transport_name' => 'Mobil',
            'accommodation_name' => 'Penginapan Uji',
            'cost_breakdown' => [],
            'preferences' => [],
        ]);

        $this->actingAs($user)
            ->post(route('trips.actual-cost.update', $trip), ['actual_cost' => 235000])
            ->assertRedirect();
        $this->assertSame(235000, $trip->fresh()->actual_cost);

        $this->actingAs(User::factory()->create())
            ->post(route('trips.actual-cost.update', $trip), ['actual_cost' => 250000])
            ->assertForbidden();

        $this->actingAs($user)->get(route('trips.show', $trip))
            ->assertOk()
            ->assertSee('Penginapan · 1 malam')
            ->assertSee('2 hari = 1 malam')
            ->assertSee('Lama berwisata hanya menghitung waktu di destinasi');
    }
}
