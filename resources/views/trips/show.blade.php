@extends('layouts.member')

@section('title', $trip->title)

@section('content')
    <style>
        @media print{.top,.no-print{display:none!important}.container{width:100%;padding:0}.panel,.card{break-inside:avoid;box-shadow:none}}
    </style>
    <div class="heading">
        <div><h1>{{ $trip->title }}</h1><p>{{ $trip->origin_name }} · {{ __(':count hari', ['count' => $trip->days]) }} · {{ __(':count wisatawan', ['count' => $trip->travelers]) }} · {{ $trip->start_date?->format('d M Y') ?? __('tanggal belum ditentukan') }}</p></div>
        <div class="actions no-print"><button class="button" onclick="window.print()">{{ __('Cetak / Simpan PDF') }}</button><a class="small-btn" href="{{ route('trips.index') }}">{{ __('Kembali') }}</a></div>
    </div>
    <section class="panel"><h2>{{ __('Ringkasan anggaran') }}</h2>
        <div class="summary-grid">
            <div class="card summary-card"><small>{{ __('Jarak perjalanan') }}</small><strong>{{ number_format($trip->total_distance_km, 1, ',', '.') }} km</strong></div>
            <div class="card summary-card"><small>{{ __('Waktu di jalan & wisata') }}</small><strong>{{ number_format($trip->total_duration_minutes / 60, 1, ',', '.') }} {{ __('jam') }}</strong></div>
            <div class="card summary-card"><small>{{ __('Transportasi') }}</small><strong>{{ $trip->transport_name }}</strong></div>
            @if($trip->accommodation_name)<div class="card summary-card"><small>{{ __('Penginapan') }} · {{ __(':count malam', ['count' => max(0, $trip->days - 1)]) }}</small><strong>{{ $trip->accommodation_name }}</strong></div>@endif
            @foreach (['transport' => 'Transportasi', 'tickets' => 'Tiket masuk', 'meals' => 'Konsumsi', 'accommodation' => 'Penginapan', 'parking' => 'Parkir'] as $key => $label)
                <div class="card summary-card"><small>@if($key === 'accommodation' && $trip->accommodation_name)Biaya penginapan · {{ max(0, $trip->days - 1) }} malam @else{{ $label }}@endif</small><strong>Rp{{ number_format($trip->cost_breakdown[$key] ?? 0, 0, ',', '.') }}</strong></div>
            @endforeach
            <div class="card summary-card summary-card-total"><small>{{ __('Total per rombongan') }}</small><strong>Rp{{ number_format($trip->total_cost, 0, ',', '.') }}</strong></div>
        </div>
        <p class="note budget-note">{{ __('Anggaran per orang') }} <strong>Rp{{ number_format($trip->budget_per_person, 0, ',', '.') }}</strong> · {{ __('total dihitung untuk :count wisatawan.', ['count' => $trip->travelers]) }}</p>
        @if($trip->accommodation_name)
            <p class="note budget-note">{{ __('Lama menginap dihitung terpisah dari jam perjalanan dan waktu berwisata: :days hari = :nights malam.', ['days' => $trip->days, 'nights' => max(0, $trip->days - 1)]) }}</p>
        @endif
    </section>
    <section class="panel no-print">
        <h2>{{ __('Biaya aktual setelah perjalanan') }}</h2>
        <p class="note">{{ __('Opsional. Masukkan jumlah biaya yang benar-benar dikeluarkan. Catatan ini membantu memberikan perkiraan biaya yang lebih baik untuk perjalanan berikutnya.') }}</p>
        @if($trip->actual_cost)<p>{{ __('Biaya aktual tersimpan') }}: <strong>Rp{{ number_format($trip->actual_cost, 0, ',', '.') }}</strong></p>@endif
        <form method="POST" action="{{ route('trips.actual-cost.update', $trip) }}" class="actual-cost-form">
            @csrf
            <input class="actual-cost-input" type="number" name="actual_cost" min="1" max="1000000000" required placeholder="{{ __('Total biaya aktual (Rp)') }}" value="{{ old('actual_cost', $trip->actual_cost) }}">
            <button class="button" type="submit">{{ __('Simpan biaya aktual') }}</button>
        </form>
    </section>
    <section class="panel"><h2>{{ __('Rencana perjalanan') }}</h2><p class="note">{{ __('Lama berwisata hanya menghitung waktu di destinasi. Jika ada penginapan, jumlah malam ditampilkan terpisah pada ringkasan.') }}</p>
        @forelse($trip->stops->groupBy('day') as $day => $stops)
            <h3>{{ __('Hari :day', ['day' => $day]) }}</h3>
            <div class="table-wrap"><table><thead><tr><th>{{ __('Urutan') }}</th><th>{{ __('Destinasi') }}</th><th>{{ __('Wilayah') }}</th><th>{{ __('Penilaian') }}</th><th>{{ __('Jarak / waktu tempuh') }}</th><th>{{ __('Lama berwisata') }}</th><th>{{ __('Tiket / orang') }}</th></tr></thead><tbody>
                @foreach($stops as $stop)
                    <tr><td>{{ $stop->sequence }}</td><td>{{ $stop->destination_name }}</td><td>{{ $stop->region }}</td><td>★ {{ number_format($stop->rating, 1, ',', '.') }}</td><td>{{ $stop->leg_distance_km }} km / {{ __(':count menit', ['count' => $stop->leg_minutes]) }}</td><td>{{ __(':count menit', ['count' => $stop->visit_duration_minutes]) }}</td><td>Rp{{ number_format($stop->admission_fee, 0, ',', '.') }}</td></tr>
                @endforeach
            </tbody></table></div>
        @empty<p class="muted">{{ __('Belum ada destinasi tersimpan.') }}</p>@endforelse
    </section>
    <form class="no-print" method="POST" action="{{ route('trips.destroy', $trip) }}" onsubmit="return confirm(@js(__('Hapus itinerary ini?')))">@csrf @method('DELETE')<button class="small-btn danger" type="submit">{{ __('Hapus itinerary') }}</button></form>
@endsection
