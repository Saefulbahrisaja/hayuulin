<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · HAYUULIN</title>
    <style>
        :root{font-family:"Segoe UI",Arial,sans-serif;color:#1e302a;background:#f4f6f3;font-size:14px}*{box-sizing:border-box}body{margin:0}a{color:#176b52;text-decoration:none}button,input,select{font:inherit}button{cursor:pointer}.top{background:#fff;border-bottom:1px solid #e1e8e2}.top-inner,.container{width:min(1080px,calc(100% - 36px));margin:auto}.top-inner{min-height:66px;display:flex;align-items:center;justify-content:space-between;gap:18px}.brand{font-weight:800;font-size:17px;color:#18342b}.links{display:flex;align-items:center;gap:17px;flex-wrap:wrap}.links button{border:0;background:none;color:#176b52}.language-switcher select{height:34px;padding:0 8px;border:1px solid #dfe7df;border-radius:7px;background:white;color:#315b45;font-size:12px}.visually-hidden{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}.container{padding:27px 0 55px}.heading{display:flex;justify-content:space-between;align-items:end;gap:20px;margin-bottom:20px}.heading h1{margin:0;font-size:26px}.heading p{margin:5px 0 0;color:#6b7972}.panel,.card{background:#fff;border:1px solid #e4e9e4;border-radius:13px;padding:17px}.panel{margin:15px 0}.panel h2{margin:0 0 13px;font-size:17px}.summary-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,180px),1fr));gap:11px}.summary-card{min-width:0;padding:13px 15px;background:#fbfcfa}.summary-card small{display:block;margin-bottom:5px;color:#718078;font-size:12px}.summary-card strong{display:block;font-size:18px;line-height:1.35;overflow-wrap:anywhere}.summary-card-total{background:#edf5ef;border-color:#dbe9df}.summary-card-total strong{color:#176b52}.budget-note{margin:14px 0 0;font-size:13px;line-height:1.6}.actual-cost-form{display:flex;align-items:center;gap:9px;flex-wrap:wrap}.actual-cost-input{flex:1 1 240px;min-width:0;padding:10px 12px;border:1px solid #dce5dc;border-radius:8px;background:#fff}.table-wrap{overflow:auto}table{width:100%;border-collapse:collapse;min-width:650px}th,td{text-align:left;padding:11px 9px;border-bottom:1px solid #edf0ed}th{font-size:11px;color:#6f7a73;text-transform:uppercase}td{font-size:13px}.button,.small-btn{display:inline-block;padding:9px 13px;border:0;border-radius:8px;background:#176b52;color:#fff;font-weight:650}.small-btn{padding:7px 9px;background:#f5f8f4;color:#315b45;border:1px solid #dfe7df;font-size:12px}.danger{color:#a73526;border-color:#f0d7d1;background:white}.muted,.note{color:#738078}.flash{padding:11px 13px;border-radius:9px;background:#e8f4ec;color:#226347;margin:0 0 16px}.pagination{margin-top:16px}@media print{.top,.no-print{display:none!important}.container{width:100%;padding:0}.panel,.card{break-inside:avoid}}@media(max-width:650px){.top-inner{align-items:flex-start;flex-direction:column;padding:12px 0}.links{gap:12px;flex-wrap:wrap}.heading{align-items:flex-start;flex-direction:column}.heading .actions{width:100%;flex-wrap:wrap}.heading .actions>*{flex:1;text-align:center}.summary-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}.summary-card{padding:11px}.summary-card strong{font-size:16px}.actual-cost-form>*{width:100%}.actual-cost-input{flex-basis:100%}}@media(max-width:360px){.summary-grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
<header class="top"><div class="top-inner">
    <a class="brand" href="{{ route('home') }}">HAYUULIN</a>
    <nav class="links">
        <a href="{{ route('home') }}">{{ __('Jelajahi') }}</a>
        <a href="{{ route('trips.index') }}">{{ __('Perjalanan saya') }}</a>
        @if(auth()->user()->is_admin)<a href="{{ route('admin.dashboard') }}">{{ __('Admin') }}</a>@endif
        @include('partials.language-selector')
        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">{{ __('Keluar') }}</button></form>
    </nav>
</div></header>
<main class="container">
    @if (session('status'))<div class="flash" role="status">{{ session('status') }}</div>@endif
    @yield('content')
</main>
</body>
</html>
