<?php

namespace App\Services;

use App\Models\Destination;
use Illuminate\Support\Str;

class SmartTourismAssistant
{
    public function answer(string $question): array
    {
        $normalized = Str::lower($question);
        $destinations = Destination::with('category')
            ->where('is_verified', true)
            ->whereNotNull('admission_fee')
            ->get();

        $matches = $destinations->filter(function (Destination $destination) use ($normalized) {
            $terms = array_filter(preg_split('/\s+/u', $normalized), fn ($term) => mb_strlen($term) >= 4);
            foreach ($terms as $term) {
                if (
                    Str::contains(Str::lower($destination->name), $term)
                    || Str::contains(Str::lower($destination->region), $term)
                    || Str::contains(Str::lower($destination->category->name), $term)
                ) {
                    return true;
                }
            }

            return false;
        })->take(4);

        if (preg_match('/biaya|harga|anggaran|budget|tiket/u', $normalized)) {
            return [
                'answer' => 'Untuk menghitung biaya yang sesuai jumlah orang, transportasi, tanggal, dan durasi, gunakan formulir perencana. Tarif yang belum dikonfirmasi tidak diasumsikan gratis; hasil biaya adalah estimasi indikatif.',
                'destinations' => $matches->pluck('name')->all(),
            ];
        }

        if ($matches->isNotEmpty()) {
            return [
                'answer' => 'Saya menemukan destinasi terverifikasi yang mungkin relevan. Informasi berikut berasal dari katalog lokal dan tetap perlu dicek kembali sebelum berangkat.',
                'destinations' => $matches->map(fn (Destination $destination) => [
                    'name' => $destination->name,
                    'category' => $destination->category->name,
                    'region' => $destination->region,
                    'fee' => $destination->admission_fee,
                    'rating' => $destination->rating > 0 ? $destination->rating : null,
                ])->all(),
            ];
        }

        $popular = $destinations->sortByDesc('rating')->take(3)->map(fn (Destination $destination) => [
            'name' => $destination->name,
            'category' => $destination->category->name,
            'region' => $destination->region,
            'fee' => $destination->admission_fee,
            'rating' => $destination->rating > 0 ? $destination->rating : null,
        ])->values()->all();

        return [
            'answer' => 'Asisten ini menggunakan pencocokan bahasa sederhana pada katalog lokal, bukan model generatif atau data real-time. Coba tanyakan nama destinasi, kategori, wilayah, atau gunakan perencana untuk mencocokkan anggaran dan jarak.',
            'destinations' => $popular,
        ];
    }
}
