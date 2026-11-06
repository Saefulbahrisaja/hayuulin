@extends('layouts.auth')

@section('title', __('Masuk'))

@section('content')
    <h1>{{ __('Selamat datang kembali') }}</h1>
    <p class="muted">{{ __('Masuk untuk menyimpan dan mengelola rencana perjalanan.') }}</p>
    @if ($errors->any())<div class="error" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    <form method="POST" action="{{ route('login.store') }}">
        @csrf
        <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" autofocus></div>
        <div class="field"><label for="password">{{ __('Kata sandi') }}</label><input id="password" name="password" type="password" required autocomplete="current-password"></div>
        <label class="remember"><input type="checkbox" name="remember" value="1"> {{ __('Ingat saya di perangkat ini') }}</label>
        <button class="button" type="submit">{{ __('Masuk') }}</button>
    </form>
    <div class="foot">{{ __('Belum punya akun?') }} <a href="{{ route('register') }}">{{ __('Daftar') }}</a></div>
@endsection
