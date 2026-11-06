@extends('layouts.admin')

@section('title', ($record ? 'Ubah ' : 'Tambah ').$catalog['title'])

@section('content')
    <div class="heading">
        <div><h1>{{ $record ? 'Ubah' : 'Tambah' }} {{ $catalog['title'] }}</h1><p>Pastikan data koordinat, tarif, dan informasi operasional sudah diverifikasi.</p></div>
        <a class="small-btn" href="{{ route('admin.catalog.index', $resource) }}">Kembali ke daftar</a>
    </div>
    <section class="panel">
        <form method="POST" action="{{ $record ? route('admin.catalog.update', [$resource, $record->getKey()]) : route('admin.catalog.store', $resource) }}">
            @csrf
            @if($record) @method('PUT') @endif
            <div class="field-grid">
                @foreach ($catalog['fields'] as $name => $field)
                    @php
                        $defaultValue = $name === 'price_verified'
                            ? (bool) $record?->price_verified_at
                            : $record?->getAttribute($name);
                        $value = old($name, $defaultValue);
                        $fullWidth = ($field['type'] ?? '') === 'textarea';
                    @endphp
                    <div class="field" @if($fullWidth) style="grid-column:1/-1" @endif>
                        @if ($field['type'] === 'checkbox')
                            <label><input type="checkbox" name="{{ $name }}" value="1" @checked((bool) $value)> {{ $field['label'] }}</label>
                        @else
                            <label for="{{ $name }}">{{ $field['label'] }}</label>
                            @if ($field['type'] === 'select')
                                <select id="{{ $name }}" name="{{ $name }}" required>
                                    <option value="">Pilih...</option>
                                    @foreach ((isset($field['source']) ? ($options[$field['source']] ?? []) : $field['options']) as $optionValue => $optionLabel)
                                        <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
                                    @endforeach
                                </select>
                            @elseif ($field['type'] === 'textarea')
                                <textarea id="{{ $name }}" name="{{ $name }}" required>{{ $value }}</textarea>
                            @else
                                <input id="{{ $name }}" name="{{ $name }}" type="{{ $field['type'] }}" value="{{ $value }}" @if(isset($field['step'])) step="{{ $field['step'] }}" @endif @if(!str_starts_with($field['rules'], 'nullable')) required @endif>
                            @endif
                        @endif
                    </div>
                @endforeach
            </div>
            <div class="right">
                <a class="small-btn" href="{{ route('admin.catalog.index', $resource) }}">Batal</a>
                <button class="button" type="submit">Simpan data</button>
            </div>
        </form>
    </section>
@endsection
