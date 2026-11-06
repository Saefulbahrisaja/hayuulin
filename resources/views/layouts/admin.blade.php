<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') · HAYUULIN</title>
    <style>
        :root{font-family:"Segoe UI",Arial,sans-serif;color:#1e302a;background:#f4f6f3;font-size:14px}*{box-sizing:border-box}body{margin:0}a{color:#176b52;text-decoration:none}button,input,select,textarea{font:inherit}.top{background:#fff;border-bottom:1px solid #e1e8e2}.top-inner,.container{width:min(1160px,calc(100% - 36px));margin:auto}.top-inner{min-height:66px;display:flex;align-items:center;justify-content:space-between;gap:18px}.brand{font-weight:800;font-size:17px;color:#18342b}.links{display:flex;align-items:center;gap:17px;flex-wrap:wrap;font-size:13px}.links form{display:inline}.links button,.button{border:0;border-radius:8px;padding:9px 13px;background:#176b52;color:#fff;font-weight:650;cursor:pointer}.links .plain{background:transparent;color:#176b52;padding:0}.language-switcher select{height:34px;padding:0 8px;border:1px solid #dfe7df;border-radius:7px;background:white;color:#315b45;font-size:12px}.visually-hidden{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}.container{padding:27px 0 55px}.heading{display:flex;justify-content:space-between;align-items:end;gap:20px;margin-bottom:20px}.heading h1{margin:0;font-size:26px;letter-spacing:-.7px}.heading p{margin:5px 0 0;color:#6b7972}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:13px;margin:18px 0 26px}.card,.panel{background:#fff;border:1px solid #e4e9e4;border-radius:13px;padding:17px}.card small{color:#718078}.card strong{display:block;font-size:26px;margin-top:8px}.panel{margin:15px 0}.panel h2{margin:0 0 13px;font-size:17px}.table-wrap{overflow:auto}table{width:100%;border-collapse:collapse;min-width:650px}th,td{text-align:left;padding:11px 9px;border-bottom:1px solid #edf0ed;vertical-align:middle}th{font-size:11px;color:#6f7a73;text-transform:uppercase;letter-spacing:.5px}td{font-size:13px}.actions{display:flex;align-items:center;gap:8px;flex-wrap:wrap}.actions form{margin:0}.small-btn{padding:7px 9px;border:1px solid #dfe7df;border-radius:7px;background:white;color:#315b45;font-size:12px;cursor:pointer}.danger{color:#a73526;border-color:#f0d7d1}.badge{display:inline-block;padding:4px 8px;border-radius:99px;background:#e8f4ec;color:#226347;font-size:11px}.muted{color:#738078}.flash{padding:11px 13px;border-radius:9px;background:#e8f4ec;color:#226347;margin:0 0 16px}.flash-info{background:#edf4f8;color:#315a70;border:1px solid #d9e7ef}.flash-warning{background:#fff6e5;color:#795618;border:1px solid #f0dfb8}.error{padding:11px 13px;border-radius:9px;background:#fff0ed;color:#983c30;margin:0 0 16px}.field-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:13px}.field{margin-bottom:13px}.field label{display:block;margin-bottom:6px;font-weight:650;font-size:12px}.field input,.field select,.field textarea{width:100%;padding:10px;border:1px solid #dce5dc;border-radius:8px;background:#fff}.field textarea{min-height:95px}.field input[type=checkbox]{width:auto}.pagination{margin-top:16px}.note{font-size:12px;color:#6f7972}.auth-wrap{min-height:100vh;display:grid;place-items:center;padding:20px}.auth-card{width:min(430px,100%);background:#fff;border:1px solid #e3e9e3;border-radius:15px;padding:27px}.auth-card h1{font-size:23px;margin:0 0 5px}.auth-card>p{color:#718078;margin:0 0 20px}.right{display:flex;justify-content:flex-end;gap:9px;margin-top:16px}@media(max-width:680px){.top-inner{padding:12px 0;align-items:flex-start;flex-direction:column}.links{gap:12px}.heading{align-items:flex-start;flex-direction:column}.field-grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
<header class="top">
    <div class="top-inner">
        <a class="brand" href="{{ route('admin.dashboard') }}">HAYUULIN <span class="badge">ADMIN</span></a>
        <nav class="links" aria-label="Navigasi admin">
            @include('partials.language-selector')
            <a href="{{ route('admin.dashboard') }}">{{ __('Dashboard & pengguna') }}</a>
            <a href="{{ route('admin.open-data.index') }}">{{ __('Impor data OSM') }}</a>
            <a href="{{ route('admin.machine-learning.index') }}">{{ __('Data & perkiraan') }}</a>
            <a href="{{ route('admin.catalog.index', 'categories') }}">{{ __('Kategori') }}</a>
            <a href="{{ route('admin.catalog.index', 'destinations') }}">{{ __('Wisata') }}</a>
            <a href="{{ route('admin.catalog.index', 'transport-options') }}">{{ __('Transportasi') }}</a>
            <a href="{{ route('admin.catalog.index', 'transport-routes') }}">{{ __('Trayek') }}</a>
            <a href="{{ route('admin.catalog.index', 'accommodations') }}">{{ __('Penginapan') }}</a>
            <a href="{{ route('admin.catalog.index', 'tariffs') }}">{{ __('Tarif') }}</a>
            <a href="{{ route('home') }}">{{ __('Lihat situs') }}</a>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="plain" type="submit">{{ __('Keluar') }}</button></form>
        </nav>
    </div>
</header>
<main class="container">
    @if (session('status'))<div class="flash {{ session('status_type') === 'info' ? 'flash-info' : (session('status_type') === 'warning' ? 'flash-warning' : '') }}" role="status">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="error" role="alert">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    @yield('content')
</main>
</body>
</html>
