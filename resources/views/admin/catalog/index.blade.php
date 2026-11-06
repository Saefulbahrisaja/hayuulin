@extends('layouts.admin')

@section('title', $catalog['title'])

@section('content')
    @php
        $columns = array_slice(array_keys($catalog['fields']), 0, 5);
        $relations = ['category_id' => 'category.name', 'transport_option_id' => 'transportOption.name'];
    @endphp
    <div class="heading">
        <div><h1>{{ $catalog['title'] }}</h1><p>Kelola data dan informasi yang ditampilkan kepada wisatawan.</p></div>
        <a class="button" href="{{ route('admin.catalog.create', $resource) }}">Tambah data</a>
    </div>
    <section class="panel">
        <form method="GET" action="{{ route('admin.catalog.index', $resource) }}" class="actions" style="margin-bottom:15px">
            <input name="q" value="{{ request('q') }}" placeholder="Cari {{ strtolower($catalog['title']) }}..." style="max-width:320px;padding:9px;border:1px solid #dce5dc;border-radius:8px">
            <button class="small-btn" type="submit">Cari</button>
            @if(request('q'))<a class="small-btn" href="{{ route('admin.catalog.index', $resource) }}">Hapus filter</a>@endif
        </form>
        <div class="table-wrap">
            <table>
                <thead><tr>@foreach ($columns as $column)<th>{{ $catalog['fields'][$column]['label'] }}</th>@endforeach<th>Status</th>@if(in_array($resource, ['destinations', 'accommodations'], true))<th>Sumber / verifikasi</th>@endif<th>Aksi</th></tr></thead>
                <tbody>
                @forelse ($records as $record)
                    <tr>
                        @foreach ($columns as $column)
                            @php $relation = $relations[$column] ?? null; @endphp
                            <td>{{ $relation ? data_get($record, $relation, '-') : \Illuminate\Support\Str::limit((string) $record->getAttribute($column), 70) }}</td>
                        @endforeach
                        <td>{{ $record->getAttribute('is_active') === null ? '—' : ($record->is_active ? 'Aktif' : 'Nonaktif') }}</td>
                        @if(in_array($resource, ['destinations', 'accommodations'], true))
                            <td>
                                @if($record->source_url)<a href="{{ $record->source_url }}" target="_blank" rel="noopener noreferrer">{{ $record->source_provider }} ↗</a>@else{{ $record->source_provider }}@endif
                                <br><span class="badge">{{ $record->is_verified ? 'Informasi terverifikasi' : 'Perlu verifikasi' }}</span>
                                @if($resource === 'accommodations')<br><span class="muted">{{ $record->price_verified_at ? 'Harga terkonfirmasi' : 'Harga belum dikonfirmasi' }}</span>@endif
                            </td>
                        @endif
                        <td><div class="actions">
                            <a class="small-btn" href="{{ route('admin.catalog.edit', [$resource, $record->getKey()]) }}">Ubah</a>
                            <form method="POST" action="{{ route('admin.catalog.destroy', [$resource, $record->getKey()]) }}" onsubmit="return confirm('Hapus data ini?')">@csrf @method('DELETE')<button class="small-btn danger" type="submit">Hapus</button></form>
                        </div></td>
                    </tr>
                @empty<tr><td colspan="{{ count($columns) + 2 + (in_array($resource, ['destinations', 'accommodations'], true) ? 1 : 0) }}" class="muted">Belum ada data.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $records->links() }}</div>
    </section>
@endsection
