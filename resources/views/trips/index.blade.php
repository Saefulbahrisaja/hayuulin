@extends('layouts.member')

@section('title', __('Perjalanan tersimpan'))

@section('content')
    <div class="heading"><div><h1>{{ __('Perjalanan tersimpan') }}</h1><p>{{ __('Itinerary milik :name.', ['name' => auth()->user()->name]) }}</p></div><a class="button" href="{{ route('home') }}#rencana">{{ __('Buat itinerary baru') }}</a></div>
    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    <section class="panel">
        <div class="table-wrap">
            <table><thead><tr><th>{{ __('Nama rencana') }}</th><th>{{ __('Durasi') }}</th><th>{{ __('Wisatawan') }}</th><th>{{ __('Destinasi') }}</th><th>{{ __('Total estimasi') }}</th><th>{{ __('Disimpan') }}</th><th>{{ __('Aksi') }}</th></tr></thead>
                <tbody>
                @forelse($trips as $trip)
                    <tr><td>{{ $trip->title }}</td><td>{{ __(':count hari', ['count' => $trip->days]) }}</td><td>{{ __(':count orang', ['count' => $trip->travelers]) }}</td><td>{{ __(':count tempat', ['count' => $trip->stops_count]) }}</td><td>Rp{{ number_format($trip->total_cost, 0, ',', '.') }}</td><td>{{ $trip->created_at->format('d M Y') }}</td><td><a class="small-btn" href="{{ route('trips.show', $trip) }}">{{ __('Lihat / cetak') }}</a></td></tr>
                @empty<tr><td colspan="7" class="muted">{{ __('Belum ada itinerary. Buat rekomendasi dan simpan rencananya.') }}</td></tr>@endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $trips->links() }}</div>
    </section>
@endsection
