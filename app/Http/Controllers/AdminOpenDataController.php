<?php

namespace App\Http\Controllers;

use App\Models\Accommodation;
use App\Models\Category;
use App\Models\Destination;
use App\Services\OpenStreetMapImportService;
use App\Services\OverpassUnavailableException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminOpenDataController extends Controller
{
    public function index(Request $request, OpenStreetMapImportService $importer)
    {
        $places = $importer->cachedPreview($request->user()->id);
        $searchRegion = $importer->cachedRegion($request->user()->id) ?? 'Sukabumi';
        $categories = Category::query()->orderBy('name')->get();
        $importedIds = array_merge(
            Destination::where('source_provider', 'openstreetmap')->pluck('source_id')->all(),
            Accommodation::where('source_provider', 'openstreetmap')->pluck('source_id')->all()
        );

        return view('admin.open-data', compact('places', 'categories', 'importedIds', 'searchRegion'));
    }

    public function preview(Request $request, OpenStreetMapImportService $importer)
    {
        $validated = $request->validate([
            'region' => ['required', 'string', 'max:120', 'regex:/^[\pL\pM\pN .,\x{2019}()&-]+$/u'],
            'refresh' => ['sometimes', 'boolean'],
        ], [
            'region.regex' => 'Gunakan nama wilayah dengan huruf, angka, spasi, titik, koma, tanda hubung, atau tanda kurung.',
        ]);

        try {
            $places = $importer->preview(
                $request->user()->id,
                $validated['region'],
                $request->boolean('refresh')
            );
        } catch (OverpassUnavailableException $exception) {
            report($exception);
            $cachedRegion = $importer->cachedRegion($request->user()->id);
            $hasCachedPreview = $importer->cachedPreview($request->user()->id) !== [];
            $message = 'Layanan peta sedang sibuk dan belum bisa mengambil data terbaru. Tidak ada data yang diubah. ';
            $message .= $hasCachedPreview
                ? 'Pratinjau sebelumnya'.($cachedRegion ? ' untuk '.$cachedRegion : '').' tetap dapat digunakan di bawah.'
                : 'Silakan coba lagi beberapa menit lagi.';

            return redirect()->route('admin.open-data.index')
                ->with('status_type', 'warning')
                ->with('osm_search_region', $validated['region'])
                ->with('status', $message);
        }

        if ($places === []) {
            return redirect()->route('admin.open-data.index')
                ->with('status_type', 'info')
                ->with('osm_preview_completed', true)
                ->with('osm_search_region', $validated['region'])
                ->with('status', 'Pencarian selesai, tetapi belum ada tempat wisata atau penginapan yang ditemukan untuk wilayah '.$validated['region'].'. Periksa ejaan wilayah atau coba nama kabupaten/kota seperti “Kota Bandung”. Tidak ada data yang diimpor.');
        }

        return redirect()->route('admin.open-data.index')
            ->with('status_type', 'success')
            ->with('osm_preview_completed', true)
            ->with('osm_search_region', $validated['region'])
            ->with('status', count($places).' tempat ditemukan di '.$validated['region'].'. Periksa datanya, lalu pilih tempat yang ingin dimasukkan.');
    }

    public function import(Request $request, OpenStreetMapImportService $importer)
    {
        $validated = $request->validate([
            'source_ids' => ['required', 'array', 'min:1', 'max:120'],
            'source_ids.*' => ['required', 'string', 'max:40'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        $places = collect($importer->cachedPreview($request->user()->id))
            ->keyBy('source_id');
        $selected = $places->only($validated['source_ids']);
        if ($selected->isEmpty() || $selected->count() !== count(array_unique($validated['source_ids']))) {
            throw ValidationException::withMessages([
                'source_ids' => 'Data terpilih sudah kedaluwarsa atau tidak berasal dari hasil pratinjau ini. Muat pratinjau terbaru.',
            ]);
        }

        $imported = 0;
        $skipped = 0;
        DB::transaction(function () use ($selected, $validated, &$imported, &$skipped) {
            foreach ($selected as $place) {
                $existing = Accommodation::where('source_provider', 'openstreetmap')->where('source_id', $place['source_id'])->exists()
                    || Destination::where('source_provider', 'openstreetmap')->where('source_id', $place['source_id'])->exists();
                if ($existing) {
                    $skipped++;

                    continue;
                }

                if ($place['is_accommodation']) {
                    Accommodation::create([
                        'name' => $place['name'],
                        'type' => $this->accommodationType($place['tourism_type']),
                        'region' => $place['region'],
                        'address' => $place['address'],
                        'latitude' => $place['latitude'],
                        'longitude' => $place['longitude'],
                        'price_per_night' => 0,
                        'capacity' => 2,
                        'rating' => 0,
                        'amenities' => null,
                        'is_active' => true,
                        'source_provider' => 'openstreetmap',
                        'source_id' => $place['source_id'],
                        'source_url' => $place['source_url'],
                        'is_verified' => false,
                    ]);
                } else {
                    $categoryId = $validated['category_ids'][$place['source_id']] ?? null;
                    if (! $categoryId) {
                        $categoryId = Category::where('slug', $place['category_slug'])->value('id');
                    }
                    if (! $categoryId) {
                        throw ValidationException::withMessages([
                            'category_ids' => 'Tambahkan kategori alam, pantai, atau budaya sebelum mengimpor destinasi.',
                        ]);
                    }

                    Destination::create([
                        'category_id' => $categoryId,
                        'name' => $place['name'],
                        'slug' => $this->uniqueSlug($place['name'], $place['source_id']),
                        'description' => 'Lokasi bersumber dari OpenStreetMap. Lengkapi deskripsi dan verifikasi informasi sebelum ditampilkan.',
                        'address' => $place['address'],
                        'region' => $place['region'],
                        'latitude' => $place['latitude'],
                        'longitude' => $place['longitude'],
                        'rating' => 0,
                        'review_count' => 0,
                        'admission_fee' => null,
                        'visit_duration_minutes' => 120,
                        'source_provider' => 'openstreetmap',
                        'source_id' => $place['source_id'],
                        'source_url' => $place['source_url'],
                        'is_verified' => false,
                    ]);
                }
                $imported++;
            }
        });

        return redirect()->route('admin.open-data.index')->with(
            'status',
            $imported.' data diimpor sebagai belum terverifikasi; '.$skipped.' duplikat dilewati.'
        );
    }

    private function uniqueSlug(string $name, string $sourceId): string
    {
        $base = \Illuminate\Support\Str::slug($name) ?: 'destinasi';
        $suffix = substr(hash('sha256', $sourceId), 0, 8);

        return substr($base, 0, 165).'-'.$suffix;
    }

    private function accommodationType(string $tourismType): string
    {
        return match ($tourismType) {
            'hotel' => 'hotel',
            'hostel' => 'hostel',
            'chalet', 'resort' => 'villa',
            default => 'homestay',
        };
    }
}
