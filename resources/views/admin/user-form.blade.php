@extends('layouts.admin')

@section('title', $user ? 'Ubah pengguna' : 'Tambah pengguna')

@section('content')
    <div class="heading"><div><h1>{{ $user ? 'Ubah pengguna' : 'Tambah pengguna' }}</h1><p>Pengguna baru diberi kata sandi sementara oleh admin; minta pengguna menggantinya sebelum berbagi akses.</p></div><a class="small-btn" href="{{ route('admin.dashboard') }}">Kembali</a></div>
    <section class="panel">
        <form method="POST" action="{{ $user ? route('admin.users.update', $user) : route('admin.users.store') }}">
            @csrf
            @if($user) @method('PUT') @endif
            <div class="field-grid">
                <div class="field"><label for="name">Nama</label><input id="name" name="name" value="{{ old('name', $user?->name) }}" maxlength="120" required></div>
                <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email', $user?->email) }}" maxlength="255" required></div>
                <div class="field"><label for="password">{{ $user ? 'Kata sandi baru (opsional)' : 'Kata sandi sementara (min. 12 karakter)' }}</label><input id="password" name="password" type="password" @if(!$user) required @endif minlength="12" autocomplete="new-password"></div>
                <div class="field"><label for="password_confirmation">Ulangi kata sandi</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"></div>
                <div class="field"><label><input type="checkbox" name="is_admin" value="1" @checked(old('is_admin', $user?->is_admin ?? false))> Beri akses administrator</label></div>
            </div>
            <div class="right"><button class="button" type="submit">Simpan pengguna</button></div>
        </form>
    </section>
@endsection
