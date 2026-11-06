@extends('layouts.auth')

@section('title', __('Daftar'))

@section('content')
    <h1>{{ __('Buat akun wisatawan') }}</h1>
    <p class="muted">{{ __('Simpan itinerary dan akses rencana perjalanan kapan saja.') }}</p>
    @if ($errors->any())<div class="error" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    <form method="POST" action="{{ route('register.store') }}">
        @csrf
        <div class="field"><label for="name">Nama</label><input id="name" name="name" value="{{ old('name') }}" required maxlength="120" autocomplete="name"></div>
        <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email"></div>
        <div class="field"><label for="password">{{ __('Kata sandi (minimal 8 karakter)') }}</label><input id="password" name="password" type="password" required minlength="8" autocomplete="new-password"></div>
        <div class="field"><label for="password_confirmation">{{ __('Ulangi kata sandi') }}</label><input id="password_confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password"></div>
        <button class="button" type="submit">{{ __('Daftar') }}</button>
    </form>
    <div class="foot">{{ __('Sudah punya akun?') }} <a href="{{ route('login') }}">{{ __('Masuk') }}</a></div>
@endsection
