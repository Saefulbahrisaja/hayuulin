<?php

namespace Database\Seeders;

use App\Models\Accommodation;
use App\Models\Category;
use App\Models\Destination;
use App\Models\TransportOption;
use App\Models\TransportRoute;
use App\Models\TravelTariff;
use Illuminate\Database\Seeder;

class TourismSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Alam', 'slug' => 'alam', 'color' => '#287a66', 'icon' => 'leaf'],
            ['name' => 'Pantai', 'slug' => 'pantai', 'color' => '#2876a8', 'icon' => 'waves'],
            ['name' => 'Budaya', 'slug' => 'budaya', 'color' => '#a66b35', 'icon' => 'landmark'],
        ];

        foreach ($categories as $attributes) {
            Category::updateOrCreate(['slug' => $attributes['slug']], $attributes);
        }

        $categoryIds = Category::query()->pluck('id', 'slug');
        $destinations = [
            [
                'category' => 'alam', 'name' => 'Situ Gunung', 'slug' => 'situ-gunung',
                'description' => 'Danau dan kawasan hutan pegunungan dengan pilihan jalur alam dan jembatan gantung.',
                'address' => 'Kadudampit, Kabupaten Sukabumi', 'region' => 'Kadudampit',
                'latitude' => -6.8401, 'longitude' => 106.9216, 'rating' => 0, 'review_count' => 0,
                'admission_fee' => null, 'visit_duration_minutes' => 240,
                'source_provider' => 'sample', 'is_verified' => false,
            ],
            [
                'category' => 'alam', 'name' => 'Curug Sawer', 'slug' => 'curug-sawer',
                'description' => 'Air terjun di kawasan Taman Nasional Gunung Gede Pangrango, dapat dikunjungi dari area Situ Gunung.',
                'address' => 'Kadudampit, Kabupaten Sukabumi', 'region' => 'Kadudampit',
                'latitude' => -6.8398, 'longitude' => 106.9206, 'rating' => 0, 'review_count' => 0,
                'admission_fee' => null, 'visit_duration_minutes' => 150,
                'source_provider' => 'sample', 'is_verified' => false,
            ],
            [
                'category' => 'pantai', 'name' => 'Pantai Pelabuhanratu', 'slug' => 'pantai-pelabuhanratu',
                'description' => 'Garis pantai dan kawasan pesisir utama di selatan Sukabumi.',
                'address' => 'Palabuhanratu, Kabupaten Sukabumi', 'region' => 'Pelabuhanratu',
                'latitude' => -6.9875, 'longitude' => 106.5510, 'rating' => 0, 'review_count' => 0,
                'admission_fee' => null, 'visit_duration_minutes' => 180,
                'source_provider' => 'sample', 'is_verified' => false,
            ],
            [
                'category' => 'pantai', 'name' => 'Pantai Karang Hawu', 'slug' => 'pantai-karang-hawu',
                'description' => 'Pantai dengan formasi batu karang dan pemandangan laut lepas.',
                'address' => 'Cisolok, Kabupaten Sukabumi', 'region' => 'Cisolok',
                'latitude' => -6.9871, 'longitude' => 106.4935, 'rating' => 0, 'review_count' => 0,
                'admission_fee' => null, 'visit_duration_minutes' => 150,
                'source_provider' => 'sample', 'is_verified' => false,
            ],
            [
                'category' => 'alam', 'name' => 'Geopark Ciletuh', 'slug' => 'geopark-ciletuh',
                'description' => 'Kawasan geopark dengan bentang alam pesisir, perbukitan, dan beberapa air terjun.',
                'address' => 'Ciemas, Kabupaten Sukabumi', 'region' => 'Ciemas',
                'latitude' => -7.1803, 'longitude' => 106.4616, 'rating' => 0, 'review_count' => 0,
                'admission_fee' => null, 'visit_duration_minutes' => 300,
                'source_provider' => 'sample', 'is_verified' => false,
            ],
            [
                'category' => 'alam', 'name' => 'Curug Cikaso', 'slug' => 'curug-cikaso',
                'description' => 'Air terjun bertingkat dengan aliran air yang bermuara ke kawasan pesisir selatan.',
                'address' => 'Surade, Kabupaten Sukabumi', 'region' => 'Surade',
                'latitude' => -7.3612, 'longitude' => 106.6510, 'rating' => 0, 'review_count' => 0,
                'admission_fee' => null, 'visit_duration_minutes' => 150,
                'source_provider' => 'sample', 'is_verified' => false,
            ],
            [
                'category' => 'pantai', 'name' => 'Pantai Ujung Genteng', 'slug' => 'pantai-ujung-genteng',
                'description' => 'Kawasan pantai di pesisir selatan yang dikenal dengan hamparan pasir dan aktivitas bahari.',
                'address' => 'Ciracap, Kabupaten Sukabumi', 'region' => 'Ciracap',
                'latitude' => -7.3445, 'longitude' => 106.3980, 'rating' => 0, 'review_count' => 0,
                'admission_fee' => null, 'visit_duration_minutes' => 210,
                'source_provider' => 'sample', 'is_verified' => false,
            ],
            [
                'category' => 'budaya', 'name' => 'Kampung Adat Kasepuhan Ciptagelar', 'slug' => 'kasepuhan-ciptagelar',
                'description' => 'Kampung adat di kawasan pegunungan dengan tradisi dan tata kehidupan masyarakat yang khas.',
                'address' => 'Cisolok, Kabupaten Sukabumi', 'region' => 'Cisolok',
                'latitude' => -6.8159, 'longitude' => 106.4870, 'rating' => 0, 'review_count' => 0,
                'admission_fee' => null, 'visit_duration_minutes' => 240,
                'source_provider' => 'sample', 'is_verified' => false,
            ],
        ];

        foreach ($destinations as $attributes) {
            $attributes['category_id'] = $categoryIds[$attributes['category']];
            unset($attributes['category']);
            Destination::updateOrCreate(['slug' => $attributes['slug']], $attributes);
        }

        $transportOptions = [
            ['name' => 'Mobil pribadi', 'type' => 'car', 'cost_per_km' => 3500, 'capacity' => 4, 'average_speed_kmh' => 35, 'description' => 'Estimasi kendaraan pribadi termasuk bahan bakar. Tarif dapat disesuaikan.', 'is_active' => true],
            ['name' => 'Sewa mobil', 'type' => 'rental', 'cost_per_km' => 5000, 'capacity' => 6, 'average_speed_kmh' => 35, 'description' => 'Estimasi kendaraan sewa dengan biaya operasional per kilometer.', 'is_active' => true],
            ['name' => 'Sepeda motor', 'type' => 'motorcycle', 'cost_per_km' => 1200, 'capacity' => 2, 'average_speed_kmh' => 30, 'description' => 'Pilihan perjalanan fleksibel untuk satu atau dua orang.', 'is_active' => true],
            ['name' => 'Angkutan umum', 'type' => 'public', 'cost_per_km' => 1800, 'capacity' => 1, 'average_speed_kmh' => 25, 'description' => 'Estimasi tarif angkutan umum per penumpang-kilometer. Periksa trayek sebelum berangkat.', 'is_active' => true],
        ];
        foreach ($transportOptions as $attributes) {
            TransportOption::updateOrCreate(['name' => $attributes['name']], $attributes);
        }

        $optionIds = TransportOption::query()->pluck('id', 'name');
        $routes = [
            ['option' => 'Angkutan umum', 'name' => 'Terminal Sukabumi – Pelabuhanratu', 'origin_region' => 'Kota Sukabumi', 'destination_region' => 'Pelabuhanratu', 'fare' => 30000, 'duration_minutes' => 180, 'instructions' => 'Berangkat dari Terminal Sukabumi menuju Terminal Pelabuhanratu. Konfirmasi jadwal dan tarif pada operator sebelum perjalanan.'],
            ['option' => 'Angkutan umum', 'name' => 'Pelabuhanratu – Cisolok', 'origin_region' => 'Pelabuhanratu', 'destination_region' => 'Cisolok', 'fare' => 15000, 'duration_minutes' => 60, 'instructions' => 'Gunakan angkutan arah Cisolok dari terminal atau titik naik resmi di Pelabuhanratu.'],
            ['option' => 'Angkutan umum', 'name' => 'Kota Sukabumi – Kadudampit', 'origin_region' => 'Kota Sukabumi', 'destination_region' => 'Kadudampit', 'fare' => 12000, 'duration_minutes' => 75, 'instructions' => 'Menuju terminal/pangkalan angkutan arah Kadudampit; lanjutkan dengan kendaraan lokal menuju kawasan Situ Gunung.'],
        ];
        foreach ($routes as $attributes) {
            $attributes['transport_option_id'] = $optionIds[$attributes['option']];
            unset($attributes['option']);
            $attributes['is_active'] = true;
            TransportRoute::updateOrCreate(['name' => $attributes['name']], $attributes);
        }

        $accommodations = [
            ['name' => 'Wisma Situ Gunung', 'type' => 'guesthouse', 'region' => 'Kadudampit', 'address' => 'Kadudampit, Kabupaten Sukabumi', 'latitude' => -6.8390, 'longitude' => 106.9210, 'price_per_night' => 0, 'capacity' => 2, 'rating' => 0, 'amenities' => null, 'is_active' => true, 'source_provider' => 'sample', 'is_verified' => false, 'price_verified_at' => null],
            ['name' => 'Penginapan Pelabuhanratu', 'type' => 'homestay', 'region' => 'Pelabuhanratu', 'address' => 'Palabuhanratu, Kabupaten Sukabumi', 'latitude' => -6.9850, 'longitude' => 106.5530, 'price_per_night' => 0, 'capacity' => 2, 'rating' => 0, 'amenities' => null, 'is_active' => true, 'source_provider' => 'sample', 'is_verified' => false, 'price_verified_at' => null],
            ['name' => 'Villa Pesisir Cisolok', 'type' => 'villa', 'region' => 'Cisolok', 'address' => 'Cisolok, Kabupaten Sukabumi', 'latitude' => -6.9860, 'longitude' => 106.4950, 'price_per_night' => 0, 'capacity' => 4, 'rating' => 0, 'amenities' => null, 'is_active' => true, 'source_provider' => 'sample', 'is_verified' => false, 'price_verified_at' => null],
            ['name' => 'Guesthouse Ciemas', 'type' => 'guesthouse', 'region' => 'Ciemas', 'address' => 'Ciemas, Kabupaten Sukabumi', 'latitude' => -7.1780, 'longitude' => 106.4600, 'price_per_night' => 0, 'capacity' => 2, 'rating' => 0, 'amenities' => null, 'is_active' => true, 'source_provider' => 'sample', 'is_verified' => false, 'price_verified_at' => null],
        ];
        foreach ($accommodations as $attributes) {
            Accommodation::updateOrCreate(['name' => $attributes['name']], $attributes);
        }

        $tariffs = [
            ['key' => 'meal_per_stop', 'label' => 'Konsumsi per destinasi/orang', 'amount' => 35000, 'unit' => 'per orang/destinasi'],
            ['key' => 'parking_per_stop', 'label' => 'Parkir per kendaraan/destinasi', 'amount' => 10000, 'unit' => 'per kendaraan/destinasi'],
            ['key' => 'accommodation_per_night', 'label' => 'Penginapan standar per malam', 'amount' => 180000, 'unit' => 'per kamar/malam'],
        ];
        foreach ($tariffs as $attributes) {
            TravelTariff::updateOrCreate(['key' => $attributes['key']], $attributes);
        }
    }
}
