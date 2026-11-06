<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · HAYUULIN</title>
    <style>
        *{box-sizing:border-box}body{margin:0;min-height:100vh;background:#f1f5ef;color:#1b3028;font:15px/1.55 "Segoe UI",Arial,sans-serif;display:grid;place-items:center;padding:20px}.card{width:min(440px,100%);background:#fff;border:1px solid #e3ebe2;border-radius:17px;padding:27px;box-shadow:0 12px 40px rgba(27,54,43,.07)}.brand{font-weight:800;font-size:18px;color:#176b52;text-decoration:none}.card h1{font-size:25px;margin:20px 0 4px}.muted{color:#738078;margin:0 0 20px}.field{margin-bottom:14px}.field label{display:block;font-size:12px;font-weight:700;margin-bottom:6px}.field input{width:100%;height:43px;padding:0 11px;border:1px solid #dfe7df;border-radius:8px;font:inherit}.button{width:100%;height:45px;border:0;border-radius:8px;background:#176b52;color:#fff;font:inherit;font-weight:700;cursor:pointer}.error{background:#fff0ed;color:#983c30;padding:10px;border-radius:8px;font-size:12px;margin-bottom:14px}.foot{margin-top:16px;text-align:center;font-size:13px;color:#718078}.foot a{color:#176b52;font-weight:700}.remember{display:flex;gap:8px;align-items:center;font-size:12px;color:#59675f;margin:6px 0 16px}.remember input{accent-color:#176b52}.language-switcher{margin-top:16px}.language-switcher select{height:36px;padding:0 9px;border:1px solid #dfe7df;border-radius:7px;background:white;color:#315b45}.visually-hidden{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
    </style>
</head>
<body>
    <main class="card">
        <a class="brand" href="{{ route('home') }}">HAYUULIN</a>
        @include('partials.language-selector')
        @yield('content')
    </main>
</body>
</html>
