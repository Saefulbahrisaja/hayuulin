@extends('layouts.admin')

@section('title', 'Data & perkiraan')

@section('content')
    <div class="heading">
        <div>
            <h1>Data & perkiraan</h1>
            <p>Catatan kunjungan dan biaya membantu memberikan perkiraan perjalanan yang lebih baik.</p>
        </div>
    </div>
    <div class="grid">
        <div class="card"><small>Catatan kunjungan</small><strong>{{ $visitorRows }}</strong></div>
        <div class="card"><small>Biaya aktual perjalanan</small><strong>{{ $actualCostRows }}</strong></div>
        <div class="card"><small>Catatan biaya untuk perkiraan</small><strong>{{ $costTrainingRows }} / 12</strong></div>
    </div>
    <section class="panel">
        <h2>Catat jumlah pengunjung aktual</h2>
        <p class="note">Masukkan jumlah pengunjung dari laporan pengelola atau sumber resmi, bukan perkiraan atau jumlah orang yang membuka halaman. Catat satu kali untuk setiap tempat pada setiap tanggal. Perkiraan baru ditampilkan setelah tersedia cukup catatan.</p>
        <form method="POST" action="{{ route('admin.machine-learning.visits.store') }}">
            @csrf
            <div class="field-grid">
                <div class="field">
                    <label for="destination_id">Destinasi terverifikasi</label>
                    <select id="destination_id" name="destination_id" required>
                        <option value="">Pilih destinasi</option>
                        @foreach($destinations as $destination)
                            <option value="{{ $destination->id }}">{{ $destination->name }} · {{ $destination->region }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field"><label for="visited_on">Tanggal kunjungan</label><input id="visited_on" type="date" name="visited_on" max="{{ now()->toDateString() }}" required></div>
                <div class="field"><label for="visitor_count">Jumlah pengunjung aktual</label><input id="visitor_count" type="number" name="visitor_count" min="0" max="10000000" required></div>
                <div class="field"><label for="source">Sumber data (pengelola/laporan)</label><input id="source" name="source" maxlength="160" required></div>
                <div class="field"><label><input type="checkbox" name="is_holiday" value="1"> Hari libur nasional/lokal</label></div>
            </div>
            <button class="button" type="submit">Simpan data kunjungan</button>
        </form>
    </section>
    <section class="panel">
        <h2>Catatan terbaru</h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Tanggal</th><th>Destinasi</th><th>Pengunjung aktual</th><th>Periode</th><th>Sumber</th></tr></thead>
                <tbody>
                    @forelse($metrics as $metric)
                        <tr>
                            <td>{{ $metric->visited_on->format('d M Y') }}</td>
                            <td>{{ $metric->destination->name }}</td>
                            <td>{{ number_format($metric->visitor_count, 0, ',', '.') }}</td>
                            <td>{{ $metric->is_holiday ? 'Hari libur' : ($metric->visited_on->isWeekend() ? 'Akhir pekan' : 'Hari kerja') }}</td>
                            <td>{{ $metric->source }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="muted">Belum ada data kunjungan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <section class="panel">
        <h2>Bagaimana perkiraan dan rekomendasi dibuat</h2>
        <ul>
            <li>Rekomendasi mempertimbangkan minat wisatawan, penilaian yang tersedia, dan rencana perjalanan yang pernah disimpan. Rencana tersimpan tidak selalu berarti tempat tersebut sudah dikunjungi.</li>
            <li>Perkiraan biaya menggunakan laporan biaya perjalanan yang benar-benar dimasukkan. Jika catatannya belum cukup, aplikasi menampilkan perkiraan biasa berdasarkan tarif yang tersedia.</li>
            <li>Perkiraan jumlah pengunjung memakai catatan kunjungan sebelumnya. Hasilnya belum ditampilkan jika catatan untuk tempat tersebut belum cukup.</li>
            <li>Urutan tempat wisata disusun agar jarak perjalanan lebih efisien. Jarak dan waktu tetap berupa perkiraan, bukan petunjuk jalan langsung.</li>
            <li>Ringkasan ulasan dibuat otomatis dan bisa saja kurang tepat; gunakan ulasan sebagai gambaran umum saja.</li>
            <li>Asisten menjawab berdasarkan informasi tempat wisata yang tersimpan di aplikasi.</li>
        </ul>
    </section>
@endsection
