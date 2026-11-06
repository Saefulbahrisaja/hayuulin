<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>HAYUULIN — {{ __('Rencanakan perjalananmu') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <style>
        :root{--ink:#172a27;--muted:#75817d;--green:#176b52;--green-dark:#124f40;--mint:#eaf4ee;--line:#e7ece8;--paper:#f7f8f5;--white:#fff;--orange:#f0a35a;--shadow:0 12px 40px rgba(27,54,43,.08)}
        *{box-sizing:border-box}
        body{margin:0;background:var(--paper);color:var(--ink);font-family:"DM Sans",sans-serif;font-size:15px;line-height:1.55}
        a{color:inherit;text-decoration:none}
        button,input,select{font:inherit}
        button{cursor:pointer}
        .shell{max-width:1220px;margin:0 auto;padding:0 28px}
        .topbar{height:78px;background:rgba(255,255,255,.94);border-bottom:1px solid var(--line);display:flex;align-items:center}
        .nav{display:flex;align-items:center;justify-content:space-between;width:100%}
        .brand{display:flex;align-items:center;gap:11px;font-family:Manrope,sans-serif;font-weight:800;font-size:18px;letter-spacing:-.5px}
        .brand-mark{width:37px;height:37px;border-radius:13px;background:var(--green);display:grid;place-items:center;color:#fff}
        .brand-mark svg{width:21px;height:21px}
        .nav-links{display:flex;align-items:center;gap:31px;color:#58635f;font-size:14px}
        .nav-links a:hover{color:var(--green)}
        .language-switcher select{height:36px;padding:0 9px;border:1px solid var(--line);border-radius:8px;background:#fff;color:#44514b;font-size:12px}
        .visually-hidden{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
        .nav-cta{padding:10px 17px;border-radius:10px;background:var(--green);color:white;font-weight:700}
        .hero{position:relative;overflow:hidden;padding:67px 0 46px;background:#edf3ed}
        .hero:before,.hero:after{content:"";position:absolute;border-radius:50%;pointer-events:none}
        .hero:before{width:470px;height:470px;right:-110px;top:-210px;border:1px solid rgba(23,107,82,.12);box-shadow:0 0 0 50px rgba(23,107,82,.025),0 0 0 100px rgba(23,107,82,.025)}
        .hero:after{width:210px;height:210px;right:13%;bottom:-165px;background:rgba(240,163,90,.16);filter:blur(1px)}
        .hero-inner{position:relative;z-index:1;display:grid;grid-template-columns:1fr .78fr;gap:54px;align-items:center}
        .eyebrow{display:inline-flex;align-items:center;gap:8px;color:var(--green);font-size:12px;letter-spacing:1.5px;text-transform:uppercase;font-weight:700}
        .eyebrow-dot{width:7px;height:7px;border-radius:99px;background:#e19b52}
        h1,h2,h3,p{margin-top:0}
        h1,h2,h3{font-family:Manrope,sans-serif}
        h1{max-width:620px;margin:17px 0 16px;font-size:clamp(38px,5vw,62px);line-height:1.08;letter-spacing:-3px}
        h1 span{color:var(--green)}
        .hero-copy{max-width:525px;color:#66736c;font-size:16px;line-height:1.8}
        .hero-notes{display:flex;gap:24px;margin-top:27px;color:#59665f;font-size:13px}
        .hero-notes span{display:flex;align-items:center;gap:7px}
        .check{color:var(--green);font-weight:800}
        .planner-card{position:relative;background:var(--white);border:1px solid rgba(23,42,39,.06);border-radius:20px;padding:24px;box-shadow:var(--shadow)}
        .planner-head{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:18px}
        .planner-head h2{margin:0 0 4px;font-size:18px;letter-spacing:-.5px}
        .planner-head p{margin:0;color:var(--muted);font-size:12px}
        .sparkle{width:37px;height:37px;display:grid;place-items:center;border-radius:12px;background:#f3f5e9;color:#a87826;font-size:18px}
        .field{margin-bottom:13px}
        .field label{display:block;margin-bottom:6px;color:#44514b;font-size:12px;font-weight:700}
        .input-wrap{position:relative}
        .input-wrap input,.input-wrap select,.field>input,.field>select{width:100%;height:43px;border:1px solid #e1e8e2;border-radius:9px;background:#fff;padding:0 12px;color:var(--ink);outline:none}
        .input-wrap input:focus,.input-wrap select:focus,.field>input:focus,.field>select:focus{border-color:#72a58d;box-shadow:0 0 0 3px rgba(23,107,82,.08)}
        .geo-button{position:absolute;right:6px;top:6px;height:31px;padding:0 9px;border:0;border-radius:7px;background:#edf5ef;color:var(--green);font-size:11px;font-weight:700}
        .geo-button:hover{background:#dcece1}
        .field-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
        .field-help{display:block;margin-top:5px;color:#89928d;font-size:11px}
        .toggle-line{display:flex;align-items:center;gap:9px;color:#55615b;font-size:12px;margin:1px 0 16px}
        .toggle-line input{accent-color:var(--green);width:15px;height:15px}
        .submit-button{width:100%;height:47px;border:0;border-radius:10px;background:var(--green);color:#fff;font-weight:700;box-shadow:0 7px 16px rgba(23,107,82,.17)}
        .submit-button:hover{background:var(--green-dark)}
        .error-box{margin-bottom:15px;padding:10px 12px;border-radius:9px;background:#fff1ed;color:#9e3d2c;font-size:12px}
        .success-box{margin-bottom:15px;padding:10px 12px;border-radius:9px;background:#e8f4ec;color:#226347;font-size:12px}
        .main-content{padding:43px 0 66px}
        .section-head{display:flex;align-items:end;justify-content:space-between;gap:20px;margin-bottom:19px}
        .section-kicker{display:block;margin-bottom:5px;color:var(--green);font-size:11px;letter-spacing:1.5px;text-transform:uppercase;font-weight:700}
        .section-head h2{margin:0;font-size:26px;letter-spacing:-1px}
        .section-head p{margin:5px 0 0;color:var(--muted);font-size:13px}
        .soft-badge{display:inline-flex;align-items:center;gap:7px;padding:8px 11px;border:1px solid var(--line);border-radius:99px;color:#59665f;background:#fff;font-size:11px}
        .soft-badge i{width:7px;height:7px;background:#3e9a73;border-radius:50%}
        .results-grid{display:grid;grid-template-columns:minmax(0,1.08fr) minmax(390px,.92fr);gap:20px;align-items:start}
        .panel{background:#fff;border:1px solid var(--line);border-radius:16px;overflow:hidden}
        .panel-head{padding:17px 19px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;gap:10px}
        .panel-head h3{margin:0;font-size:15px;letter-spacing:-.3px}
        .panel-head small{color:var(--muted);font-size:11px}
        .map-filter{max-width:145px;height:34px;padding:0 9px;border:1px solid var(--line);border-radius:8px;background:#fff;color:#4f5b54;font-size:11px}
        #map{height:405px;background:#e5ebe3}
        .map-note{padding:10px 15px;color:#78837c;font-size:11px;background:#fff}
        .itinerary-empty{padding:39px 24px;text-align:center;color:var(--muted)}
        .empty-icon{width:53px;height:53px;margin:0 auto 13px;border-radius:17px;background:#eff5ef;display:grid;place-items:center;color:var(--green);font-size:22px}
        .itinerary-empty strong{display:block;margin-bottom:5px;color:var(--ink);font-family:Manrope,sans-serif}
        .itinerary-empty p{max-width:360px;margin:0 auto;font-size:12px}
        .plan-content{padding:17px 19px}
        .plan-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:9px;margin-bottom:16px}
        .stat{padding:11px;border:1px solid #edf0ec;border-radius:10px;background:#fbfcfa}
        .stat span{display:block;color:var(--muted);font-size:10px}
        .stat strong{display:block;margin-top:4px;font-family:Manrope,sans-serif;font-size:14px}
        .stop-list{position:relative}
        .stop-list:before{position:absolute;content:"";left:14px;top:19px;bottom:22px;border-left:1px dashed #b9d1c2}
        .stop{position:relative;display:grid;grid-template-columns:29px minmax(0,1fr);gap:10px;padding:0 0 15px}
        .stop:last-child{padding-bottom:0}
        .stop-number{position:relative;z-index:1;width:29px;height:29px;display:grid;place-items:center;border-radius:50%;background:#e8f3ec;color:var(--green);font-size:11px;font-weight:800}
        .stop-copy{min-width:0;overflow-wrap:anywhere}
        .stop h4{margin:1px 0 4px;font-family:Manrope,sans-serif;font-size:14px}
        .stop p{margin:0 0 4px;color:var(--muted);font-size:11px}
        .stop-meta{grid-column:2/-1;display:grid;grid-template-columns:minmax(0,1fr) 88px;align-items:center;gap:5px 10px;margin-top:1px;padding:9px 10px;border:1px solid #edf0ec;border-radius:9px;background:#fbfcfa;color:#5d6a63;font-size:11px;text-align:left;white-space:normal}
        .stop-meta b{grid-column:1/-1;color:#a16c2e;font-size:11px}
        .stop-meta label{font-weight:700}
        .stop-meta input{width:100%;min-width:0;height:35px;padding:5px 8px;border:1px solid #e1e8e2;border-radius:7px;background:#fff}
        .stop-meta .visit-help,.stop-meta .stop-ticket{grid-column:1/-1}
        .visit-help{color:var(--muted);font-size:10px}
        .stop-ticket{font-weight:600}
        .cost-box{margin-top:17px;padding:15px;border-radius:12px;background:#f2f7f1}
        .cost-title{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px}
        .cost-title strong{font-family:Manrope,sans-serif;font-size:13px}
        .cost-title span{color:var(--green);font-size:10px;font-weight:700}
        .cost-row{display:flex;justify-content:space-between;gap:12px;padding:5px 0;color:#627069;font-size:11px}
        .cost-row b{color:#34413a;font-weight:600}
        .cost-total{display:flex;justify-content:space-between;margin-top:8px;padding-top:10px;border-top:1px solid #dfe9df;font-weight:700;font-size:12px}
        .cost-total b{color:var(--green);font-size:15px}
        .cost-assumption{margin:10px 0 0;color:#7a867d;font-size:10px;line-height:1.5}
        .no-results{padding:13px 15px;border-radius:10px;background:#fff4e8;color:#835a27;font-size:12px}
        .destination-section{margin-top:41px}
        .destination-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px}
        .destination-card{overflow:hidden;border:1px solid var(--line);border-radius:14px;background:#fff;transition:transform .2s,box-shadow .2s}
        .destination-card:hover{transform:translateY(-3px);box-shadow:var(--shadow)}
        .destination-cover{height:105px;display:flex;align-items:center;justify-content:space-between;padding:15px;background:linear-gradient(135deg,#d9e8dc,#eef2df);color:#285d48}
        .destination-card:nth-child(3n+2) .destination-cover{background:linear-gradient(135deg,#d9e8ef,#edf3e6);color:#2b6881}
        .destination-card:nth-child(3n) .destination-cover{background:linear-gradient(135deg,#eee1d4,#f6ebd9);color:#94663e}
        .destination-cover .landmark{font-size:28px;opacity:.82}
        .category-pill{padding:5px 8px;border-radius:99px;background:rgba(255,255,255,.75);font-size:10px;font-weight:700}
        .destination-body{padding:13px}
        .destination-body h3{margin:0 0 5px;font-size:13px}
        .destination-body p{min-height:34px;margin:0;color:var(--muted);font-size:10px;line-height:1.6}
        .destination-details{display:flex;justify-content:space-between;margin-top:12px;padding-top:10px;border-top:1px solid #f0f1ee;color:#68756d;font-size:10px}
        .rating{color:#a46b2d;font-weight:700}
        .footer{padding:22px 0;border-top:1px solid var(--line);color:#7a857e;font-size:11px}
        .footer-inner{display:flex;justify-content:space-between;gap:15px}
        .transport-comparison{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:16px}
        .transport-choice{padding:14px;border:1px solid var(--line);border-radius:12px;background:#fff}
        .transport-choice strong{display:block;font-family:Manrope,sans-serif;font-size:13px}
        .transport-choice span{display:block;margin-top:5px;color:var(--muted);font-size:11px}
        .transport-choice b{display:block;margin-top:9px;color:var(--green);font-size:13px}
        @media print{.topbar,.hero,.footer,.destination-section,.results-grid>.panel:first-child,.section-head,.submit-button,.duration-editor,.map-note{display:none!important}.main-content{padding:0}.panel{border:0}.results-grid{display:block}.plan-content{padding:0}.cost-box{break-inside:avoid}}
        .topbar{position:sticky;top:0;z-index:1000;background:rgba(255,255,255,.92);backdrop-filter:blur(14px)}
        .nav-links{gap:clamp(14px,2.4vw,31px)}
        .nav-links a,.language-switcher select{transition:color .18s ease,background-color .18s ease,border-color .18s ease}
        .nav-links a:focus-visible,.language-switcher select:focus-visible,.submit-button:focus-visible,.geo-button:focus-visible{outline:3px solid rgba(23,107,82,.28);outline-offset:3px}
        .hero{background:radial-gradient(ellipse at 82% 18%,rgba(255,255,255,.78),transparent 32%),linear-gradient(125deg,#edf4ed 0%,#f4f6ed 55%,#e8f1eb 100%)}
        .hero-inner{grid-template-columns:minmax(0,1fr) minmax(350px,.88fr);gap:clamp(28px,5vw,58px);align-items:start}
        .stop-copy{padding-top:18px}
        .eyebrow{padding:8px 11px;border:1px solid rgba(23,107,82,.12);border-radius:999px;background:rgba(255,255,255,.55);letter-spacing:1.2px}
        h1{font-size:clamp(40px,5.3vw,66px);letter-spacing:-3.2px}
        .hero-notes{gap:10px 18px;flex-wrap:wrap}
        .hero-notes span{padding:7px 10px;border:1px solid rgba(23,107,82,.1);border-radius:999px;background:rgba(255,255,255,.52)}
        .planner-card{border-color:rgba(23,107,82,.09);box-shadow:0 20px 55px rgba(27,54,43,.12)}
        .panel,.destination-card,.transport-choice{box-shadow:0 5px 18px rgba(27,54,43,.035)}
        .panel-head{flex-wrap:wrap}
        .destination-card,.transport-choice{transition:transform .2s ease,box-shadow .2s ease}
        .destination-card:hover,.transport-choice:hover{transform:translateY(-3px);box-shadow:var(--shadow)}
        .submit-button{transition:background-color .18s ease,transform .18s ease,box-shadow .18s ease}
        .submit-button:hover{transform:translateY(-1px);box-shadow:0 9px 20px rgba(23,107,82,.2)}
        @media(max-width:820px){.hero-inner{grid-template-columns:minmax(0,1fr);gap:28px}.hero-copy{max-width:650px}.planner-card{width:100%;max-width:650px}.results-grid{grid-template-columns:1fr}.destination-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.transport-comparison{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media(max-width:600px){.shell{padding:0 17px}.topbar{height:auto;min-height:66px;padding:9px 0}.nav{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:8px 12px}.nav-links{grid-column:1/-1;width:100%;justify-content:flex-start;gap:8px;flex-wrap:wrap;overflow:visible;padding:2px 0 7px}.nav-links>a:not(.nav-cta){display:inline-flex;flex:0 0 auto;padding:7px 9px;border:1px solid var(--line);border-radius:8px;background:#fff;font-size:12px}.nav-links form{flex:0 0 auto}.nav-links .nav-cta{flex:0 0 auto;white-space:nowrap;padding:8px 11px;font-size:12px}.nav-links .language-switcher{flex:0 0 auto}.nav-links .language-switcher select{height:34px}.brand{font-size:16px}.hero{padding:38px 0 30px}.hero-inner{gap:23px}h1{font-size:clamp(38px,10vw,48px);letter-spacing:-2.2px}.hero-copy{font-size:14px;line-height:1.7}.hero-notes{gap:8px;font-size:11px}.hero-notes span{padding:6px 8px}.planner-card{padding:17px;border-radius:16px}.field-row{gap:9px}.main-content{padding:31px 0 48px}.section-head{align-items:flex-start;flex-direction:column}.section-head h2{font-size:23px}.soft-badge{align-self:flex-start}.panel-head{align-items:flex-start;padding:15px}.panel-head>div:last-child{width:100%}.map-filter{max-width:none;min-width:0;flex:1}#map{height:335px}.plan-content{padding:14px}.destination-grid{gap:10px}.destination-cover{height:85px;padding:10px}.destination-body{padding:10px}.destination-body h3{font-size:12px}.footer-inner{flex-direction:column}}
        @media(max-width:430px){.field-row{grid-template-columns:minmax(0,1fr)}.hero-notes{gap:7px}.hero-notes span{font-size:10px}.planner-head h2{font-size:17px}.panel-head>div:last-child{gap:6px!important}.map-filter{font-size:10px;padding:0 6px}.destination-grid{grid-template-columns:1fr}.destination-cover{height:100px}.plan-stats{gap:6px}.stat{padding:8px}.stat span{font-size:9px}.stat strong{font-size:12px}.transport-comparison{grid-template-columns:1fr}}
        @media(prefers-reduced-motion:reduce){*,*::before,*::after{scroll-behavior:auto!important;transition:none!important}}
    </style>
</head>
<body>
    <header class="topbar">
        <div class="shell nav">
            <a class="brand" href="{{ route('home') }}">
                <span class="brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M12 21s7-6.1 7-12a7 7 0 1 0-14 0c0 5.9 7 12 7 12Z" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="9" r="2.4" stroke="currentColor" stroke-width="1.8"/></svg>
                </span>
                HAYU ULIN
            </a>
            <nav class="nav-links" aria-label="Navigasi utama">
                <a href="#peta">{{ __('Jelajahi') }}</a>
                <a href="#destinasi">{{ __('Destinasi') }}</a>
                @auth
                    <a href="{{ route('trips.index') }}">{{ __('Perjalanan saya') }}</a>
                    @if(auth()->user()->is_admin)<a href="{{ route('admin.dashboard') }}">{{ __('Admin') }}</a>@endif
                    <form method="POST" action="{{ route('logout') }}" style="display:inline">@csrf<button type="submit" class="nav-cta" style="border:0">{{ __('Keluar') }}</button></form>
                @else
                    <a href="{{ route('login') }}">{{ __('Masuk') }}</a>
                @endauth
                @include('partials.language-selector')
                <a class="nav-cta" href="#rencana">{{ __('Buat rencana') }} <span aria-hidden="true">↗</span></a>
            </nav>
        </div>
    </header>

    <main>
        <section class="hero" id="rencana">
            <div class="shell hero-inner">
                <div class="stop-copy">
                    <span class="eyebrow"><i class="eyebrow-dot"></i> Sukabumi · {{ __('Wisata cerdas') }}</span>
                    <h1>{{ __('Hayu,') }} <span>{{ __('Jelajahi Sukabumi.') }}</span></h1>
                    <p class="hero-copy">{{ __('Temukan tempat yang cocok, susun rute paling efisien, dan perkirakan biaya perjalananmu dalam satu rencana.') }}</p>
                    <div class="hero-notes">
                        <span><b class="check">✓</b> {{ __('Rute lebih efisien') }}</span>
                        <span><b class="check">✓</b> {{ __('Sesuai anggaran') }}</span>
                        <span><b class="check">✓</b> {{ __('Peta interaktif') }}</span>
                    </div>
                </div>

                <form class="planner-card" action="{{ route('trips.recommend') }}" method="POST">
                    @csrf
                    <div class="planner-head">
                        <div>
                            <h2>{{ __('Rencanakan perjalanan') }}</h2>
                            <p>{{ __('Sesuaikan dengan gaya liburanmu') }}</p>
                        </div>
                        <span class="sparkle" aria-hidden="true">✳</span>
                    </div>

                    @if ($errors->any())
                        <div class="error-box" role="alert">
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif
                    @if (session('status'))<div class="success-box" role="status">{{ session('status') }}</div>@endif

                    <div class="field">
                        <label for="origin_name">{{ __('Lokasi keberangkatan') }}</label>
                        <div class="input-wrap">
                            <input id="origin_name" name="origin_name" value="{{ old('origin_name', $form['origin_name'] ?? 'Kota Sukabumi') }}" required maxlength="120" autocomplete="off">
                            <button class="geo-button" type="button" id="locate-button">◎ {{ __('Lokasiku') }}</button>
                        </div>
                        <input type="hidden" id="origin_latitude" name="origin_latitude" value="{{ old('origin_latitude', $form['origin_latitude'] ?? '-6.9193') }}">
                        <input type="hidden" id="origin_longitude" name="origin_longitude" value="{{ old('origin_longitude', $form['origin_longitude'] ?? '106.9267') }}">
                    </div>

                    <div class="field">
                        <label for="category_id">{{ __('Minat wisata') }}</label>
                        <select id="category_id" name="category_id">
                            <option value="">{{ __('Semua kategori') }}</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('category_id', $form['category_id'] ?? '') === (string) $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field-row">
                        <div class="field">
                            <label for="region">{{ __('Wilayah wisata') }}</label>
                            <select id="region" name="region">
                                <option value="">{{ __('Semua wilayah') }}</option>
                                @foreach ($regions as $regionName)
                                    <option value="{{ $regionName }}" @selected(old('region', $form['region'] ?? '') === $regionName)>{{ $regionName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label for="preference">{{ __('Prioritas rekomendasi') }}</label>
                            <select id="preference" name="preference">
                                @foreach (['balanced' => __('Seimbang'), 'rating' => __('Rating tertinggi'), 'budget' => __('Tiket termurah'), 'distance' => __('Terdekat')] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('preference', $form['preference'] ?? 'balanced') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="field-row">
                        <div class="field">
                            <label for="min_rating">{{ __('Rating minimum') }}</label>
                            <select id="min_rating" name="min_rating">
                                <option value="">{{ __('Semua rating') }}</option>
                                @foreach ([3, 3.5, 4, 4.5] as $rating)
                                    <option value="{{ $rating }}" @selected((string) old('min_rating', $form['min_rating'] ?? '') === (string) $rating)>★ {{ number_format($rating, 1, ',', '.') }}+</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label for="max_distance">{{ __('Jarak maksimal') }}</label>
                            <select id="max_distance" name="max_distance">
                                <option value="">{{ __('Tidak dibatasi') }}</option>
                                @foreach ([25, 50, 100, 200] as $distance)
                                    <option value="{{ $distance }}" @selected((string) old('max_distance', $form['max_distance'] ?? '') === (string) $distance)>≤ {{ $distance }} km</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="field-row">
                        <div class="field">
                            <label for="budget">{{ __('Anggaran / orang') }}</label>
                            <input id="budget" name="budget" type="number" min="50000" max="100000000" step="10000" value="{{ old('budget', $form['budget'] ?? 500000) }}" required>
                        </div>
                        <div class="field">
                            <label for="transport_option_id">{{ __('Pilihan transportasi') }}</label>
                            <select id="transport_option_id" name="transport_option_id">
                                @foreach ($transportOptions as $option)
                                    <option value="{{ $option->id }}" @selected((string) old('transport_option_id', $form['transport_option_id'] ?? '') === (string) $option->id)>{{ $option->name }} · {{ $option->capacity }} orang · Rp{{ number_format($option->cost_per_km, 0, ',', '.') }}/km</option>
                                @endforeach
                            </select>
                            <span class="field-help">{{ __('Tarif mengikuti data yang dikelola pengelola aplikasi.') }}</span>
                        </div>
                        <div class="field">
                            <label for="q">{{ __('Kata kunci destinasi') }}</label>
                            <input id="q" name="q" value="{{ old('q', $form['q'] ?? '') }}" maxlength="120" placeholder="{{ __('Contoh: air terjun, pantai, Kadudampit') }}">
                        </div>
                        <div class="field">
                            <label for="days">{{ __('Lama perjalanan') }}</label>
                            <select id="days" name="days">
                                @for ($day = 1; $day <= 7; $day++)
                                    <option value="{{ $day }}" @selected((string) old('days', $form['days'] ?? 1) === (string) $day)>{{ $day }} hari</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                    <div class="field">
                        <label for="travel_date">{{ __('Tanggal mulai perjalanan') }}</label>
                        <input id="travel_date" name="travel_date" type="date" min="{{ now()->toDateString() }}" value="{{ old('travel_date', $form['travel_date'] ?? now()->toDateString()) }}" required>
                    </div>
                    <label class="toggle-line">
                        <input type="checkbox" name="is_holiday" value="1" @checked(old('is_holiday', $form['is_holiday'] ?? false))>
                        {{ __('Tanggal mulai perjalanan adalah hari libur') }}
                    </label>
                    <div class="field">
                        <label for="travelers">{{ __('Jumlah wisatawan') }}</label>
                        <input id="travelers" name="travelers" type="number" min="1" max="50" value="{{ old('travelers', $form['travelers'] ?? 2) }}" required>
                        <span class="field-help">{{ __('Perkiraan kendaraan menggunakan kapasitas 4 orang per mobil.') }}</span>
                    </div>
                    <label class="toggle-line">
                        <input type="checkbox" name="include_accommodation" value="1" @checked(old('include_accommodation', $form['include_accommodation'] ?? true))>
                        {{ __('Hitung biaya penginapan untuk malam menginap') }}
                    </label>
                    <span class="field-help" style="margin:-10px 0 16px 24px">{{ __('Jumlah malam mengikuti lama perjalanan: 2 hari = 1 malam, 3 hari = 2 malam.') }}</span>
                    <button class="submit-button" type="submit">{{ __('Buat rekomendasi perjalanan') }} <span aria-hidden="true">→</span></button>
                </form>
            </div>
        </section>

        <section class="main-content">
            <div class="shell">
                <div class="section-head">
                    <div>
                        <span class="section-kicker">{{ __('Rencana perjalanan') }}</span>
                        <h2>{{ $plan ? __('Rute pilihanmu') : __('Mulai dari peta') }}</h2>
                        <p>{{ $plan ? __('Destinasi disusun berdasarkan preferensi dan anggaran yang kamu pilih.') : __('Lihat sebaran destinasi, lalu buat rencana sesuai kebutuhanmu.') }}</p>
                    </div>
                    <span class="soft-badge"><i></i> {{ __('Data awal Sukabumi') }}</span>
                </div>

                <div class="results-grid">
                    <section class="panel" id="peta" aria-label="{{ __('Peta destinasi wisata Sukabumi') }}">
                        <div class="panel-head">
                            <div>
                                <h3>{{ __('Peta destinasi') }}</h3>
                                <small>{{ __(':count tempat untuk dijelajahi', ['count' => $destinations->count()]) }}</small>
                            </div>
                            <div style="display:flex;gap:7px">
                            <select class="map-filter" id="map-filter" aria-label="{{ __('Filter kategori di peta') }}">
                                <option value="">{{ __('Semua kategori') }}</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                            <select class="map-filter" id="region-filter" aria-label="{{ __('Filter wilayah di peta') }}">
                                <option value="">{{ __('Semua wilayah') }}</option>
                                @foreach ($regions as $regionName)
                                    <option value="{{ $regionName }}">{{ $regionName }}</option>
                                @endforeach
                            </select>
                            </div>
                        </div>
                        <div id="map" role="img" aria-label="{{ __('Peta interaktif destinasi') }}"></div>
                        <div class="map-note">{{ __('Garis rute menunjukkan urutan kunjungan, bukan navigasi jalan. Jarak merupakan estimasi.') }}</div>
                    </section>

                    <section class="panel" aria-label="{{ __('Itinerary & estimasi biaya') }}">
                        <div class="panel-head">
                            <div>
                                <h3>{{ __('Itinerary & estimasi biaya') }}</h3>
                                <small>{{ $plan ? __(':count destinasi direkomendasikan', ['count' => count($plan['stops'])]) : __('Rencana perjalananmu akan tampil di sini') }}</small>
                            </div>
                            @if ($plan && $plan['stops'])
                                <span class="soft-badge"><i></i> {{ __('Dalam anggaran') }}</span>
                            @endif
                        </div>

                        @if ($plan && count($plan['stops']))
                            <div class="plan-content">
                                <div class="plan-stats">
                                    <div class="stat"><span>{{ __('Jarak pulang-pergi') }}</span><strong>{{ number_format($plan['distance_km'], 1, ',', '.') }} km</strong></div>
                                    <div class="stat"><span>{{ __('Waktu di jalan & wisata') }}</span><strong>{{ number_format($plan['total_minutes'] / 60, 1, ',', '.') }} {{ __('jam') }}</strong></div>
                                    <div class="stat"><span>{{ __('Destinasi') }}</span><strong>{{ count($plan['stops']) }} {{ __('tempat') }}</strong></div>
                                </div>
                                <form method="POST" action="{{ route('trips.draft.update') }}">
                                    @csrf
                                    <div class="stop-list">
                                        @foreach ($plan['stops'] as $stop)
                                            <article class="stop">
                                                <span class="stop-number">{{ $stop['sequence'] }}</span>
                                                <div>
                                                    <h4>{{ $stop['name'] }}</h4>
                                                    <p>{{ __('Hari :day', ['day' => $stop['day']]) }} · {{ $stop['region'] }} · {{ __(':distance km dari tempat sebelumnya', ['distance' => number_format($stop['leg_distance_km'], 1, ',', '.')]) }} · {{ __('Kecocokan :score/100', ['score' => number_format($stop['recommendation_score'], 0)]) }}</p>
                                                    @if($stop['recommendation_reasons'])<p>{{ implode(' · ', $stop['recommendation_reasons']) }}</p>@endif
                                                    @if(!empty($stop['visitor_prediction']['count']))<p>{{ __('Perkiraan jumlah pengunjung pada :date: sekitar :count orang.', ['date' => $stop['visitor_prediction_date'], 'count' => number_format($stop['visitor_prediction']['count'], 0, ',', '.')]) }}</p>
                                                    @else<p>{{ __('Perkiraan jumlah pengunjung belum tersedia karena catatan kunjungan sebelumnya belum cukup.') }}</p>@endif
                                                </div>
                                                <div class="stop-meta">
                                                    @if($stop['rating'] > 0)<b>★ {{ number_format($stop['rating'], 1, ',', '.') }}</b>@endif
                                                    <label for="duration-{{ $stop['id'] }}">{{ __('Lama berwisata di sini (menit)') }}</label>
                                                    <input id="duration-{{ $stop['id'] }}" name="durations[{{ $stop['id'] }}]" type="number" min="15" max="480" step="15" value="{{ $stop['visit_duration_minutes'] }}" required>
                                                    <span class="visit-help">{{ __('Khusus waktu menikmati destinasi; malam menginap dihitung terpisah.') }}</span>
                                                    <span class="stop-ticket">{{ __('Tiket') }}: {{ $stop['admission_fee'] > 0 ? 'Rp'.number_format($stop['admission_fee'], 0, ',', '.') : __('Gratis terkonfirmasi') }}</span>
                                                </div>
                                            </article>
                                        @endforeach
                                    </div>
                                    @if (auth()->check())
                                        <button class="submit-button duration-editor" type="submit" style="width:auto;padding:0 12px;height:38px;margin-top:12px">{{ __('Simpan perubahan waktu') }}</button>
                                    @else
                                        <p class="cost-assumption duration-editor">{{ __('Masuk untuk mengubah durasi kunjungan dan menyimpan itinerary.') }}</p>
                                    @endif
                                </form>
                                <div class="cost-box">
                                    <div class="cost-title"><strong>{{ __('Rincian estimasi') }}</strong><span>{{ __('per rombongan') }} · {{ $form['travelers'] ?? 2 }} {{ __('orang') }}</span></div>
                                    <div class="cost-row"><span>{{ __('Transportasi') }}</span><b>Rp{{ number_format($plan['costs']['transport'], 0, ',', '.') }}</b></div>
                                    <div class="cost-row"><span>{{ __('Tiket masuk') }}</span><b>Rp{{ number_format($plan['costs']['tickets'], 0, ',', '.') }}</b></div>
                                    <div class="cost-row"><span>{{ __('Konsumsi') }}</span><b>Rp{{ number_format($plan['costs']['meals'], 0, ',', '.') }}</b></div>
                                    <div class="cost-row"><span>{{ __('Penginapan') }}</span><b>Rp{{ number_format($plan['costs']['accommodation'], 0, ',', '.') }}</b></div>
                                    <div class="cost-row"><span>{{ __('Parkir & tambahan') }}</span><b>Rp{{ number_format($plan['costs']['parking'], 0, ',', '.') }}</b></div>
                                    <div class="cost-total"><span>{{ __('Total / orang') }}</span><b>Rp{{ number_format($plan['costs']['per_person'], 0, ',', '.') }}</b></div>
                                    @if($plan['cost_prediction']['per_person'] !== null)
                                        <div class="cost-row"><span>{{ __('Perkiraan biaya / orang (berdasarkan :count perjalanan sebelumnya)', ['count' => $plan['cost_prediction']['training_rows']]) }}</span><b>Rp{{ number_format($plan['cost_prediction']['per_person'], 0, ',', '.') }}</b></div>
                                    @endif
                                    <p class="cost-assumption">{{ $plan['cost_prediction']['status'] }}</p>
                                    <p class="cost-assumption">{{ __('Simulasi indikatif, bukan harga real-time') }}: {{ $plan['transport']['name'] }} Rp{{ number_format($plan['transport']['cost_per_km'], 0, ',', '.') }}/km, {{ __('kapasitas :count orang/unit.', ['count' => $plan['transport']['travelers_per_vehicle']]) }} {{ __('Biaya konsumsi, parkir, dan tarif standar penginapan memakai nilai konfigurasi pengelola dan perlu dikonfirmasi. Jarak jalan dihitung sebagai perkiraan dari koordinat.') }}</p>
                                </div>
                                @if ($plan['accommodation'])
                                    <div class="cost-box" style="margin-top:10px">
                                        <div class="cost-title"><strong>{{ __('Penginapan pilihan') }}</strong><span>{{ $plan['accommodation']['region'] }}</span></div>
                                        <div class="cost-row"><span>{{ $plan['accommodation']['name'] }} · {{ $plan['accommodation']['rooms'] }} {{ __('kamar') }} × {{ $plan['accommodation']['nights'] }} {{ __('malam') }} @if($plan['accommodation']['distance_km'] !== null)· {{ __('sekitar :distance km dari destinasi terdekat', ['distance' => number_format($plan['accommodation']['distance_km'], 1, ',', '.')]) }} @endif</span><b>Rp{{ number_format($plan['accommodation']['total'], 0, ',', '.') }}</b></div>
                                        <p class="cost-assumption">{{ __('Lama menginap dihitung terpisah dari waktu berwisata. :nights malam untuk perjalanan :days hari.', ['nights' => $form['days'] - 1, 'days' => $form['days']]) }}</p>
                                    </div>
                                @endif
                                @if ($plan['accommodation_options'])
                                    <div class="cost-assumption">{{ __('Alternatif penginapan aktif') }}:
                                        @foreach ($plan['accommodation_options'] as $option)
                                            {{ $option['name'] }} ({{ $option['region'] }}@if($option['distance_km'] !== null), ±{{ number_format($option['distance_km'], 1, ',', '.') }} km @endif @if($option['rating'] > 0), ★{{ number_format($option['rating'], 1, ',', '.') }}@endif, Rp{{ number_format($option['price_per_night'], 0, ',', '.') }}/malam){{ !$loop->last ? '; ' : '.' }}
                                        @endforeach
                                    </div>
                                @endif
                                <div style="display:flex;gap:9px;flex-wrap:wrap;margin-top:15px">
                                    <div id="directions-links" style="display:flex;gap:7px;flex-wrap:wrap"></div>
                                    @if (auth()->check())
                                        <form method="POST" action="{{ route('trips.store') }}" style="display:flex;gap:7px;flex:1;min-width:220px">
                                            @csrf
                                            <input name="title" value="{{ old('title', 'Jelajah Sukabumi') }}" required maxlength="120" aria-label="{{ __('Nama rencana perjalanan') }}" style="min-width:0;flex:1;padding:9px;border:1px solid #dfe7df;border-radius:8px">
                                            <button class="submit-button" style="width:auto;padding:0 12px">{{ __('Simpan itinerary') }}</button>
                                        </form>
                                    @else
                                        <a class="submit-button" href="{{ route('login') }}" style="display:grid;place-items:center;padding:0 13px">{{ __('Masuk untuk menyimpan itinerary') }}</a>
                                    @endif
                                    <button type="button" class="submit-button" style="width:auto;padding:0 13px;background:#446f5b" onclick="window.print()">{{ __('Cetak / Simpan PDF') }}</button>
                                </div>
                            </div>
                        @elseif ($plan)
                            <div class="plan-content"><div class="no-results">{{ __('Belum ada destinasi yang cocok dengan anggaran dan durasi perjalanan ini. Coba tambah anggaran, perpanjang durasi, atau pilih kategori lain.') }}</div></div>
                        @else
                            <div class="itinerary-empty">
                                <div class="empty-icon" aria-hidden="true">⌁</div>
                                <strong>{{ __('Perjalanan yang pas dimulai di sini') }}</strong>
                                <p>{{ __('Tentukan titik awal, minat, dan anggaran. Kami akan menyusun urutan kunjungan agar perjalanan lebih efisien.') }}</p>
                            </div>
                        @endif
                    </section>
                </div>

                <section class="destination-section" id="asisten">
                    <div class="section-head">
                        <div><span class="section-kicker">{{ __('Asisten wisata') }}</span><h2>{{ __('Tanya HAYU ULIN') }}</h2><p>{{ __('Asisten mencocokkan kata dengan katalog lokal; bukan layanan AI eksternal.') }}</p></div>
                    </div>
                    <form class="panel" method="POST" action="{{ route('assistant.ask') }}" style="padding:17px">
                        @csrf
                        <label for="assistant-question">{{ __('Contoh: “pantai di Cisolok” atau “bagaimana menghitung biaya?”') }}</label>
                        <div style="display:flex;gap:8px;margin-top:8px">
                            <input id="assistant-question" name="question" maxlength="500" required value="{{ old('question', session('assistant_question')) }}" style="flex:1;min-width:0;padding:10px;border:1px solid var(--line);border-radius:8px">
                            <button class="submit-button" style="width:auto;padding:0 15px">{{ __('Tanya') }}</button>
                        </div>
                        @error('question')<p class="error-box" role="alert">{{ $message }}</p>@enderror
                        @if(session('assistant_answer'))
                            <div class="success-box" style="margin-top:12px">
                                <p>{{ session('assistant_answer.answer') }}</p>
                                @if(session('assistant_answer.destinations'))
                                    <ul>
                                        @foreach(session('assistant_answer.destinations') as $suggestion)
                                            @if(is_array($suggestion))
                                                <li><strong>{{ $suggestion['name'] }}</strong> · {{ $suggestion['category'] }} · {{ $suggestion['region'] }} · @if($suggestion['rating'])★ {{ number_format($suggestion['rating'], 1, ',', '.') }} · @endif{{ is_null($suggestion['fee']) ? __('tarif belum diketahui') : ($suggestion['fee'] ? 'Rp'.number_format($suggestion['fee'], 0, ',', '.') : __('gratis terkonfirmasi')) }}</li>
                                            @else
                                                <li>{{ $suggestion }}</li>
                                            @endif
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        @endif
                    </form>
                </section>

                <section class="destination-section" id="destinasi">
                    <div class="section-head">
                        <div>
                            <span class="section-kicker">{{ __('Inspirasi perjalanan') }}</span>
                            <h2>{{ __('Destinasi pilihan Sukabumi') }}</h2>
                            <p>{{ __('Gunakan filter pencarian untuk menemukan wisata sesuai minat dan wilayah.') }}</p>
                            @if(session('review_status'))<p class="success-box" role="status">{{ session('review_status') }}</p>@endif
                        </div>
                    </div>
                    <div class="destination-grid">
                        @foreach ($destinations->take(8) as $destination)
                            <article class="destination-card">
                                @php $mlInfo = $recommendationScores[$destination->id] ?? null; @endphp
                                <div class="destination-cover">
                                    <span class="landmark" aria-hidden="true">{{ $destination->category->icon === 'waves' ? '⌁' : ($destination->category->icon === 'landmark' ? '⌂' : '⌘') }}</span>
                                    <span class="category-pill">{{ $destination->category->name }}</span>
                                </div>
                                <div class="destination-body">
                                    <h3>{{ $destination->name }}</h3>
                                    <p>{{ $destination->address }}</p>
                                    <div class="destination-details">
                                        @if($destination->rating > 0)<span class="rating">★ {{ number_format($destination->rating, 1, ',', '.') }} @if($destination->review_count)({{ number_format($destination->review_count, 0, ',', '.') }})@endif</span>@endif
                                        <span>{{ is_null($destination->admission_fee) ? __('Tarif belum dikonfirmasi') : ($destination->admission_fee > 0 ? 'Rp'.number_format($destination->admission_fee, 0, ',', '.') : __('Gratis')) }}</span>
                                    </div>
                                    <p>{{ __('Ringkasan ulasan') }}: {{ $destination->positive_review_count }} {{ __('pengalaman positif') }} · {{ $destination->neutral_review_count }} {{ __('biasa saja') }} · {{ $destination->negative_review_count }} {{ __('kurang menyenangkan') }}</p>
                                    @auth
                                        <form method="POST" action="{{ route('destinations.reviews.store', $destination) }}">
                                            @csrf
                                            <label for="review-{{ $destination->id }}" class="field-help">{{ __('Ceritakan pengalaman Anda berkunjung') }}</label>
                                            <textarea id="review-{{ $destination->id }}" name="body" minlength="10" maxlength="2000" required style="width:100%;margin-top:5px;padding:8px;border:1px solid var(--line);border-radius:8px">{{ old('body') }}</textarea>
                                            <button class="small-btn" type="submit" style="margin-top:6px">{{ __('Kirim ulasan') }}</button>
                                        </form>
                                    @endauth
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>

                <section class="destination-section" id="transportasi">
                    <div class="section-head"><div><span class="section-kicker">{{ __('Mobilitas lokal') }}</span><h2>{{ __('Transportasi & trayek') }}</h2><p>{{ __('Periksa rute serta informasi titik keberangkatan sebelum bepergian.') }}</p></div></div>
                    <div class="transport-comparison">
                        @foreach ($transportOptions as $option)
                            <article class="transport-choice"><strong>{{ $option->name }}</strong><span>{{ __('Kapasitas :count orang · perkiraan :speed km/jam', ['count' => $option->capacity, 'speed' => $option->average_speed_kmh]) }}</span><b>Rp{{ number_format($option->cost_per_km, 0, ',', '.') }}/km · {{ __('perkiraan') }}</b><span>{{ $option->description }}</span></article>
                        @endforeach
                    </div>
                    <div class="destination-grid">
                        @foreach ($transportRoutes->take(8) as $routeInfo)
                            <article class="destination-card"><div class="destination-body">
                                <span class="category-pill">{{ $routeInfo->transportOption->name }}</span>
                                <h3 style="margin-top:10px">{{ $routeInfo->name }}</h3>
                                <p>{{ $routeInfo->instructions }}</p>
                                <div class="destination-details"><span>{{ $routeInfo->duration_minutes ? __(':count menit', ['count' => $routeInfo->duration_minutes]) : __('Periksa durasi ke operator') }}</span><span>Rp{{ number_format($routeInfo->fare, 0, ',', '.') }}/{{ __('orang') }} · {{ __('perkiraan') }}</span></div>
                            </div></article>
                        @endforeach
                    </div>
                </section>

                <section class="destination-section" id="penginapan">
                    <div class="section-head"><div><span class="section-kicker">{{ __('Tempat beristirahat') }}</span><h2>{{ __('Penginapan sesuai wilayah') }}</h2><p>{{ __('Pilihan aktif dapat diurutkan dan diperbarui melalui katalog pengelola.') }}</p></div></div>
                    <div class="destination-grid">
                        @foreach ($accommodations as $stay)
                            <article class="destination-card"><div class="destination-body">
                                <span class="category-pill">{{ ucfirst($stay->type) }}</span>
                                <h3 style="margin-top:10px">{{ $stay->name }}</h3>
                                <p>{{ $stay->address }} · {{ __('kapasitas :count orang', ['count' => $stay->capacity]) }} · {{ $stay->amenities ?: __('Informasi fasilitas belum tersedia') }}</p>
                                <div class="destination-details">@if($stay->rating > 0)<span class="rating">★ {{ number_format($stay->rating, 1, ',', '.') }}</span>@endif<span>{{ $stay->price_verified_at ? 'Rp'.number_format($stay->price_per_night, 0, ',', '.').'/'.__('malam') : __('Harga belum dikonfirmasi') }}</span></div>
                            </div></article>
                        @endforeach
                    </div>
                </section>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="shell footer-inner">
            <span><strong>HAYU ULIN (www.hayuulin.com)</strong> — Hybrid Artificial Intelligence for Your Urban and Local Intelligent Navigation</span>
            <span>{{ __('Data peta © OpenStreetMap contributors (ODbL). Lokasi dan tarif harus diverifikasi sebelum digunakan.') }}</span>
        </div>
    </footer>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        const destinations = @js($mapDestinations);
        const routes = @js($mapRoute);
        const mapLabels = {
            routeStart: @js(__('Titik awal hari :day')),
            directions: @js(__('Petunjuk jalan hari :day')),
            noLocation: @js(__('Lokasi tidak tersedia')),
            locating: @js(__('Mencari...')),
            locationUpdated: @js(__('Lokasi diperbarui')),
            permissionDenied: @js(__('Izin lokasi ditolak')),
            myLocation: @js(__('Lokasi saya'))
        };
        const defaultOrigin = {
            name: @js($origin['name'] ?? ($form['origin_name'] ?? 'Kota Sukabumi')),
            latitude: Number(@js($origin['latitude'] ?? ($form['origin_latitude'] ?? -6.9193))),
            longitude: Number(@js($origin['longitude'] ?? ($form['origin_longitude'] ?? 106.9267)))
        };
        const map = L.map('map', {scrollWheelZoom: false}).setView([-6.98, 106.78], 9);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        const markers = [];
        destinations.forEach((destination) => {
            const marker = L.circleMarker([destination.latitude, destination.longitude], {
                radius: 7,
                color: destination.color,
                fillColor: destination.color,
                fillOpacity: 0.9,
                weight: 2
            });
            const popup = document.createElement('div');
            const title = document.createElement('strong');
            title.textContent = destination.name;
            const address = document.createElement('div');
            address.textContent = `${destination.category} · ${destination.region}`;
            const rating = document.createElement('div');
            rating.textContent = `${Number(destination.rating) > 0 ? `★ ${Number(destination.rating).toFixed(1)} · ` : ''}${destination.address}`;
            popup.append(title, address, rating);
            marker.bindPopup(popup);
            marker.categoryId = String(destination.category_id);
            markers.push(marker);
        });

        const markerGroup = L.featureGroup(markers).addTo(map);
        const applyMapFilters = () => {
            const categoryFilter = document.getElementById('map-filter').value;
            const regionFilter = document.getElementById('region-filter').value;
            markers.forEach((marker) => {
                if ((!categoryFilter || marker.categoryId === categoryFilter) && (!regionFilter || marker.region === regionFilter)) {
                    if (!map.hasLayer(marker)) marker.addTo(map);
                } else if (map.hasLayer(marker)) {
                    map.removeLayer(marker);
                }
            });
        };
        document.getElementById('map-filter').addEventListener('change', applyMapFilters);
        document.getElementById('region-filter').addEventListener('change', applyMapFilters);

        if (routes.length) {
            const lines = routes.map((route, index) => {
                const points = route.map((point) => [point.latitude, point.longitude]);
                L.marker(points[0]).bindPopup(mapLabels.routeStart.replace(':day', index + 1)).addTo(map);
                return L.polyline(points, {color: '#176b52', weight: 4, opacity: 0.8, dashArray: '7 8'}).addTo(map);
            });
            map.fitBounds(L.featureGroup(lines).getBounds(), {padding: [28, 28]});
            const directionsLinks = document.getElementById('directions-links');
            routes.forEach((route, index) => {
                const routeParameters = route
                    .map((point) => `${point.latitude},${point.longitude}`)
                    .join(';');
                const link = document.createElement('a');
                link.className = 'submit-button';
                link.style.cssText = 'display:grid;place-items:center;width:auto;padding:0 13px;background:#446f5b';
                link.href = `https://www.openstreetmap.org/directions?engine=fossgis_osrm_car&route=${encodeURIComponent(routeParameters)}`;
                link.target = '_blank';
                link.rel = 'noopener noreferrer';
                link.textContent = `${mapLabels.directions.replace(':day', index + 1)} ↗`;
                directionsLinks.append(link);
            });
        } else if (markers.length) {
            map.fitBounds(markerGroup.getBounds(), {padding: [22, 22]});
            L.circleMarker([defaultOrigin.latitude, defaultOrigin.longitude], {
                radius: 6, color: '#172a27', fillColor: '#f0a35a', fillOpacity: 1, weight: 2
            }).bindPopup(@js(__('Kota Sukabumi · titik awal'))).addTo(map);
        }

        document.getElementById('locate-button').addEventListener('click', () => {
            const button = document.getElementById('locate-button');
            if (!navigator.geolocation) {
                button.textContent = mapLabels.noLocation;
                return;
            }
            button.textContent = mapLabels.locating;
            navigator.geolocation.getCurrentPosition((position) => {
                document.getElementById('origin_latitude').value = position.coords.latitude;
                document.getElementById('origin_longitude').value = position.coords.longitude;
                document.getElementById('origin_name').value = mapLabels.myLocation;
                button.textContent = mapLabels.locationUpdated;
            }, () => {
                button.textContent = mapLabels.permissionDenied;
            }, {enableHighAccuracy: false, timeout: 10000});
        });
    </script>
</body>
</html>
