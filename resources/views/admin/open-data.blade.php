@extends('layouts.admin')

@section('title', 'Impor data OpenStreetMap')

@section('content')
    <style>
        .osm-submit{display:inline-flex;align-items:center;justify-content:center;gap:9px;min-height:40px}
        .osm-submit:disabled{opacity:.78;cursor:wait}
        .osm-spinner{width:16px;height:16px;border:2px solid currentColor;border-right-color:transparent;border-radius:50%;animation:osm-spin .7s linear infinite}
        .osm-spinner[hidden]{display:none}
        .osm-submit-status{min-height:1.4em;margin:8px 0 0;color:#52675b;font-size:13px}
        .osm-selection-error{color:#983c30}
        @keyframes osm-spin{to{transform:rotate(360deg)}}
        @media(prefers-reduced-motion:reduce){.osm-spinner{animation-duration:1.8s}}
    </style>

    <div class="heading">
        <div>
            <h1>Impor data OpenStreetMap</h1>
            <p>Cari tempat wisata dan penginapan di wilayah mana pun, lalu pilih data untuk dimasukkan ke katalog.</p>
        </div>
    </div>

    <section class="panel">
        <h2>Data terbuka, bukan tarif terkonfirmasi</h2>
        <p class="note">Masukkan nama kabupaten atau kota. Jika hanya memasukkan nama wilayah, pencarian juga mencoba nama dengan awalan “Kabupaten” dan “Kota”. Maksimal 120 tempat ditampilkan dan pratinjau disimpan selama 20 menit. OpenStreetMap dapat tidak lengkap atau keliru. Data impor belum terverifikasi; harga tiket, harga kamar, dan rating tidak diimpor sebagai fakta. Periksa informasi sebelum menandainya sebagai terverifikasi.</p>
        <p class="note">© <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap contributors</a>, tersedia di bawah lisensi ODbL. Data turunan wajib mempertahankan atribusi dan memenuhi ketentuan lisensi.</p>
        <form method="POST" action="{{ route('admin.open-data.preview') }}" data-osm-progress data-loading-message="Sedang mencari tempat di wilayah yang dipilih. Proses ini mungkin perlu beberapa saat.">
            @csrf
            <input type="hidden" name="refresh" value="1">
            <div class="field">
                <label for="region">Nama wilayah</label>
                <input id="region" name="region" value="{{ old('region', session('osm_search_region', $searchRegion)) }}" maxlength="120" placeholder="Contoh: Sukabumi, Kota Bandung, Kabupaten Garut" required>
                <span class="note">Gunakan nama kabupaten/kota yang dikenal di OpenStreetMap. Untuk wilayah dengan nama umum, tulis “Kota …” atau “Kabupaten …”.</span>
            </div>
            <button class="button osm-submit" type="submit" data-loading-text="Sedang mencari...">
                <span class="osm-spinner" aria-hidden="true" hidden></span>
                <span class="osm-submit-label">Ambil pratinjau terbaru</span>
            </button>
            <p class="osm-submit-status" role="status" aria-live="polite"></p>
        </form>
    </section>

    <section class="panel">
        <h2>Jika pencarian peta tidak tersedia</h2>
        <p class="muted">Data tetap bisa ditambahkan sendiri tanpa OpenStreetMap. Isi nama wilayah, alamat, dan koordinat berdasarkan informasi yang sudah Anda verifikasi.</p>
        <div class="actions">
            <a class="button" href="{{ route('admin.catalog.create', 'destinations') }}">Tambah destinasi manual</a>
            <a class="small-btn" href="{{ route('admin.catalog.create', 'accommodations') }}">Tambah penginapan manual</a>
        </div>
    </section>

    @if(count($places))
        <section class="panel">
            <h2>Hasil pratinjau di {{ $searchRegion }} ({{ count($places) }})</h2>
            <form method="POST" action="{{ route('admin.open-data.import') }}" data-osm-progress data-osm-import data-loading-message="Sedang menyimpan tempat terpilih ke katalog.">
                @csrf
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Pilih</th><th>Tempat / OSM</th><th>Jenis</th><th>Wilayah & koordinat</th><th>Kategori destinasi</th></tr>
                        </thead>
                        <tbody>
                            @foreach($places as $place)
                                @php $alreadyImported = in_array($place['source_id'], $importedIds, true); @endphp
                                <tr>
                                    <td>
                                        <input type="checkbox" name="source_ids[]" value="{{ $place['source_id'] }}" @disabled($alreadyImported)>
                                        @if($alreadyImported)<div class="note">Sudah diimpor</div>@endif
                                    </td>
                                    <td>
                                        <strong>{{ $place['name'] }}</strong><br>
                                        <a href="{{ $place['source_url'] }}" target="_blank" rel="noopener noreferrer">Lihat objek OSM ↗</a><br>
                                        <span class="note">{{ $place['address'] }}</span>
                                    </td>
                                    <td>{{ str_replace('_', ' ', ucfirst($place['tourism_type'])) }}{{ $place['is_accommodation'] ? ' · Penginapan' : ' · Destinasi' }}</td>
                                    <td>{{ $place['region'] }}<br><span class="note">{{ number_format($place['latitude'], 6, ',', '.') }}, {{ number_format($place['longitude'], 6, ',', '.') }}</span></td>
                                    <td>
                                        @unless($place['is_accommodation'])
                                            <select name="category_ids[{{ $place['source_id'] }}]">
                                                <option value="">Otomatis ({{ $place['category_slug'] }})</option>
                                                @foreach($categories as $category)
                                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                                @endforeach
                                            </select>
                                        @else
                                            —
                                        @endunless
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="note">Lokasi yang sama dapat muncul sebagai beberapa objek OSM. Periksa objek dan hindari duplikasi manual sebelum mengimpor.</p>
                <button class="button osm-submit" type="submit" data-loading-text="Sedang menyimpan...">
                    <span class="osm-spinner" aria-hidden="true" hidden></span>
                    <span class="osm-submit-label">Impor pilihan sebagai belum terverifikasi</span>
                </button>
                <p class="osm-submit-status" role="status" aria-live="polite"></p>
            </form>
        </section>
    @else
        <section class="panel">
            @if(session('osm_preview_completed'))
                <h2>Belum ada tempat yang ditemukan</h2>
                <p class="muted">Pencarian berhasil dijalankan, tetapi belum ada tempat yang dapat ditampilkan untuk wilayah ini. Periksa ejaan atau coba nama wilayah yang lebih lengkap, misalnya “Kota Bandung”.</p>
            @else
                <p class="muted">Belum ada hasil pratinjau. Ambil pratinjau untuk mencari tempat yang tersedia.</p>
            @endif
        </section>
    @endif
    <script>
        document.querySelectorAll('[data-osm-progress]').forEach((form) => {
            form.addEventListener('submit', (event) => {
                const status = form.querySelector('.osm-submit-status');
                if (form.dataset.osmImport !== undefined
                    && !form.querySelector('input[name="source_ids[]"]:checked')) {
                    event.preventDefault();
                    status.textContent = 'Pilih setidaknya satu tempat sebelum mengimpor.';
                    status.classList.add('osm-selection-error');
                    form.querySelector('input[name="source_ids[]"]:not(:disabled)')?.focus();
                    return;
                }

                if (form.dataset.submitting === 'true') {
                    event.preventDefault();
                    return;
                }

                form.dataset.submitting = 'true';
                form.setAttribute('aria-busy', 'true');
                status.classList.remove('osm-selection-error');
                status.textContent = form.dataset.loadingMessage;

                const button = form.querySelector('.osm-submit');
                button.disabled = true;
                button.setAttribute('aria-busy', 'true');
                button.querySelector('.osm-spinner').hidden = false;
                button.querySelector('.osm-submit-label').textContent = button.dataset.loadingText;
            });
        });
    </script>
@endsection
