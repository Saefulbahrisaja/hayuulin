<?php

namespace App\Http\Controllers;

use App\Models\Accommodation;
use App\Models\Category;
use App\Models\Destination;
use App\Models\TransportOption;
use App\Models\TransportRoute;
use App\Models\TravelTariff;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AdminCatalogController extends Controller
{
    private const CATALOGS = [
        'categories' => [
            'title' => 'Kategori wisata', 'model' => Category::class, 'search' => ['name', 'slug'],
            'fields' => [
                'name' => ['label' => 'Nama', 'type' => 'text', 'rules' => 'required|string|max:80'],
                'slug' => ['label' => 'Slug', 'type' => 'text', 'rules' => 'required|string|max:100|alpha_dash', 'unique' => true],
                'color' => ['label' => 'Warna marker', 'type' => 'color', 'rules' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/'],
                'icon' => ['label' => 'Ikon', 'type' => 'text', 'rules' => 'required|string|max:40'],
            ],
        ],
        'destinations' => [
            'title' => 'Destinasi wisata', 'model' => Destination::class, 'search' => ['name', 'region', 'address'],
            'fields' => [
                'category_id' => ['label' => 'Kategori', 'type' => 'select', 'source' => 'categories', 'rules' => 'required|exists:categories,id'],
                'name' => ['label' => 'Nama destinasi', 'type' => 'text', 'rules' => 'required|string|max:160'],
                'slug' => ['label' => 'Slug', 'type' => 'text', 'rules' => 'required|string|max:180|alpha_dash', 'unique' => true],
                'description' => ['label' => 'Deskripsi', 'type' => 'textarea', 'rules' => 'required|string|max:5000'],
                'address' => ['label' => 'Alamat', 'type' => 'text', 'rules' => 'required|string|max:255'],
                'region' => ['label' => 'Wilayah', 'type' => 'text', 'rules' => 'required|string|max:120'],
                'latitude' => ['label' => 'Latitude', 'type' => 'number', 'step' => '0.0000001', 'rules' => 'required|numeric|between:-90,90'],
                'longitude' => ['label' => 'Longitude', 'type' => 'number', 'step' => '0.0000001', 'rules' => 'required|numeric|between:-180,180'],
                'rating' => ['label' => 'Rating (0–5)', 'type' => 'number', 'step' => '0.1', 'rules' => 'required|numeric|between:0,5'],
                'review_count' => ['label' => 'Jumlah ulasan', 'type' => 'number', 'rules' => 'required|integer|min:0'],
                'admission_fee' => ['label' => 'Tiket masuk (Rp; kosong bila belum diketahui)', 'type' => 'number', 'rules' => 'nullable|integer|min:0|max:10000000'],
                'visit_duration_minutes' => ['label' => 'Durasi kunjungan (menit)', 'type' => 'number', 'rules' => 'required|integer|between:15,1440'],
                'is_verified' => ['label' => 'Informasi destinasi dan koordinat telah diverifikasi', 'type' => 'checkbox', 'rules' => 'sometimes|boolean'],
            ],
        ],
        'transport-options' => [
            'title' => 'Pilihan transportasi', 'model' => TransportOption::class, 'search' => ['name', 'type'],
            'fields' => [
                'name' => ['label' => 'Nama transportasi', 'type' => 'text', 'rules' => 'required|string|max:120'],
                'type' => ['label' => 'Jenis', 'type' => 'select', 'options' => ['car' => 'Mobil', 'motorcycle' => 'Sepeda motor', 'public' => 'Angkutan umum', 'rental' => 'Kendaraan sewa'], 'rules' => 'required|in:car,motorcycle,public,rental'],
                'cost_per_km' => ['label' => 'Tarif per km (Rp)', 'type' => 'number', 'rules' => 'required|integer|min:0|max:1000000'],
                'capacity' => ['label' => 'Kapasitas orang', 'type' => 'number', 'rules' => 'required|integer|between:1,100'],
                'average_speed_kmh' => ['label' => 'Kecepatan rata-rata (km/jam)', 'type' => 'number', 'rules' => 'required|integer|between:5,150'],
                'description' => ['label' => 'Keterangan', 'type' => 'textarea', 'rules' => 'required|string|max:3000'],
                'is_active' => ['label' => 'Aktif', 'type' => 'checkbox', 'rules' => 'sometimes|boolean'],
            ],
        ],
        'transport-routes' => [
            'title' => 'Trayek dan rute', 'model' => TransportRoute::class, 'search' => ['name', 'origin_region', 'destination_region'],
            'fields' => [
                'transport_option_id' => ['label' => 'Transportasi', 'type' => 'select', 'source' => 'transport-options', 'rules' => 'required|exists:transport_options,id'],
                'name' => ['label' => 'Nama trayek', 'type' => 'text', 'rules' => 'required|string|max:160'],
                'origin_region' => ['label' => 'Wilayah asal', 'type' => 'text', 'rules' => 'required|string|max:120'],
                'destination_region' => ['label' => 'Wilayah tujuan', 'type' => 'text', 'rules' => 'required|string|max:120'],
                'fare' => ['label' => 'Tarif per orang (Rp)', 'type' => 'number', 'rules' => 'required|integer|min:0|max:10000000'],
                'duration_minutes' => ['label' => 'Estimasi perjalanan (menit)', 'type' => 'number', 'rules' => 'required|integer|min:0|max:2880'],
                'instructions' => ['label' => 'Petunjuk dan titik pemberhentian', 'type' => 'textarea', 'rules' => 'required|string|max:3000'],
                'is_active' => ['label' => 'Aktif', 'type' => 'checkbox', 'rules' => 'sometimes|boolean'],
            ],
        ],
        'accommodations' => [
            'title' => 'Hotel dan penginapan', 'model' => Accommodation::class, 'search' => ['name', 'region', 'address'],
            'fields' => [
                'name' => ['label' => 'Nama penginapan', 'type' => 'text', 'rules' => 'required|string|max:160'],
                'type' => ['label' => 'Jenis', 'type' => 'select', 'options' => ['hotel' => 'Hotel', 'homestay' => 'Homestay', 'villa' => 'Villa', 'guesthouse' => 'Guesthouse'], 'rules' => 'required|in:hotel,homestay,villa,guesthouse'],
                'region' => ['label' => 'Wilayah', 'type' => 'text', 'rules' => 'required|string|max:120'],
                'address' => ['label' => 'Alamat', 'type' => 'text', 'rules' => 'required|string|max:255'],
                'latitude' => ['label' => 'Latitude (opsional)', 'type' => 'number', 'step' => '0.0000001', 'rules' => 'nullable|numeric|between:-90,90'],
                'longitude' => ['label' => 'Longitude (opsional)', 'type' => 'number', 'step' => '0.0000001', 'rules' => 'nullable|numeric|between:-180,180'],
                'price_per_night' => ['label' => 'Harga per malam (Rp)', 'type' => 'number', 'rules' => 'required|integer|min:0|max:100000000'],
                'capacity' => ['label' => 'Kapasitas per kamar/unit', 'type' => 'number', 'rules' => 'required|integer|between:1,100'],
                'rating' => ['label' => 'Rating (0–5)', 'type' => 'number', 'step' => '0.1', 'rules' => 'required|numeric|between:0,5'],
                'amenities' => ['label' => 'Fasilitas', 'type' => 'text', 'rules' => 'nullable|string|max:500'],
                'is_active' => ['label' => 'Aktif', 'type' => 'checkbox', 'rules' => 'sometimes|boolean'],
                'is_verified' => ['label' => 'Lokasi dan informasi penginapan telah diverifikasi', 'type' => 'checkbox', 'rules' => 'sometimes|boolean'],
                'price_verified' => ['label' => 'Harga per malam telah dikonfirmasi ke pengelola', 'type' => 'checkbox', 'rules' => 'sometimes|boolean'],
            ],
        ],
        'tariffs' => [
            'title' => 'Tarif dan biaya tambahan', 'model' => TravelTariff::class, 'search' => ['key', 'label', 'unit'],
            'fields' => [
                'key' => ['label' => 'Kunci tarif (unik)', 'type' => 'text', 'rules' => 'required|string|max:100|alpha_dash', 'unique' => true],
                'label' => ['label' => 'Nama tarif', 'type' => 'text', 'rules' => 'required|string|max:140'],
                'amount' => ['label' => 'Nilai (Rp)', 'type' => 'number', 'rules' => 'required|integer|min:0|max:10000000'],
                'unit' => ['label' => 'Satuan', 'type' => 'text', 'rules' => 'required|string|max:40'],
            ],
        ],
    ];

    public function index(Request $request, string $resource)
    {
        $catalog = $this->catalog($resource);
        $model = $catalog['model'];
        $query = $model::query();
        if ($model === Destination::class) {
            $query->with('category');
        } elseif ($model === TransportRoute::class) {
            $query->with('transportOption');
        }

        if ($search = trim((string) $request->query('q'))) {
            $columns = $catalog['search'];
            $query->where(function (Builder $builder) use ($columns, $search) {
                foreach ($columns as $column) {
                    $builder->orWhere($column, 'like', '%'.$search.'%');
                }
            });
        }

        $records = $query->latest()->paginate(20)->withQueryString();

        return view('admin.catalog.index', compact('catalog', 'records', 'resource'));
    }

    public function create(string $resource)
    {
        $catalog = $this->catalog($resource);

        return view('admin.catalog.form', ['catalog' => $catalog, 'resource' => $resource, 'record' => null, 'options' => $this->options($catalog)]);
    }

    public function store(Request $request, string $resource)
    {
        $catalog = $this->catalog($resource);
        $data = $this->validated($request, $catalog);
        foreach ($catalog['fields'] as $name => $field) {
            if ($field['type'] === 'checkbox') {
                $data[$name] = $request->boolean($name);
            }
        }
        $priceVerified = (bool) ($data['price_verified'] ?? false);
        unset($data['price_verified']);
        $this->validateAccommodationPrice($catalog, $priceVerified, $data);
        $record = $catalog['model']::create($data);
        $this->syncAccommodationPriceVerification($record, $priceVerified);

        return redirect()->route('admin.catalog.index', $resource)->with('status', $catalog['title'].' berhasil ditambahkan.');
    }

    public function edit(string $resource, int $record)
    {
        $catalog = $this->catalog($resource);
        $record = $catalog['model']::findOrFail($record);

        return view('admin.catalog.form', ['catalog' => $catalog, 'resource' => $resource, 'record' => $record, 'options' => $this->options($catalog)]);
    }

    public function update(Request $request, string $resource, int $record)
    {
        $catalog = $this->catalog($resource);
        $record = $catalog['model']::findOrFail($record);
        $data = $this->validated($request, $catalog, $record);
        foreach ($catalog['fields'] as $name => $field) {
            if ($field['type'] === 'checkbox') {
                $data[$name] = $request->boolean($name);
            }
        }
        $priceVerified = (bool) ($data['price_verified'] ?? false);
        unset($data['price_verified']);
        $this->validateAccommodationPrice($catalog, $priceVerified, $data);
        $record->update($data);
        $this->syncAccommodationPriceVerification($record, $priceVerified);

        return redirect()->route('admin.catalog.index', $resource)->with('status', $catalog['title'].' berhasil diperbarui.');
    }

    public function destroy(string $resource, int $record)
    {
        $catalog = $this->catalog($resource);
        $catalog['model']::findOrFail($record)->delete();

        return back()->with('status', $catalog['title'].' berhasil dihapus.');
    }

    private function catalog(string $resource): array
    {
        abort_unless(isset(self::CATALOGS[$resource]), 404);

        return self::CATALOGS[$resource];
    }

    private function options(array $catalog): array
    {
        $options = [];
        foreach ($catalog['fields'] as $field) {
            if (($field['type'] ?? null) !== 'select') {
                continue;
            }
            if (isset($field['source'])) {
                $source = self::CATALOGS[$field['source']]['model'];
                $options[$field['source']] = $source::query()->orderBy('name')->pluck('name', 'id')->all();
            } else {
                $options[$field['options'] ? 'options' : ''] = $field['options'];
            }
        }

        return $options;
    }

    private function validated(Request $request, array $catalog, ?Model $record = null): array
    {
        $rules = [];
        foreach ($catalog['fields'] as $name => $field) {
            $rule = $field['rules'];
            if (! empty($field['unique'])) {
                $table = (new $catalog['model'])->getTable();
                $rule .= '|unique:'.$table.','.$name.($record ? ','.$record->getKey() : '');
            }
            $rules[$name] = $rule;
        }

        return $request->validate($rules);
    }

    private function validateAccommodationPrice(array $catalog, bool $priceVerified, array $data): void
    {
        if ($catalog['model'] === Accommodation::class && $priceVerified && (int) ($data['price_per_night'] ?? 0) <= 0) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'price_per_night' => 'Masukkan harga positif sebelum menandai harga sebagai terkonfirmasi.',
            ]);
        }
    }

    private function syncAccommodationPriceVerification(Model $record, bool $priceVerified): void
    {
        if (! $record instanceof Accommodation) {
            return;
        }

        $record->price_verified_at = $priceVerified ? now() : null;
        $record->save();
    }
}
