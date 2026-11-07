<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'YouTube Keşif') · YouTube Keşif</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Serif+Display&display=swap" rel="stylesheet">
    <style>
        :root { --paper:#f5f0e7; --cream:#fffdf8; --ink:#30241e; --muted:#82766c; --wine:#56362f; --rust:#ad6548; --line:#e7ddd0; --sand:#e9dfd1; }
        * { box-sizing:border-box; }
        body { margin:0; background:var(--paper); color:var(--ink); font-family:'DM Sans',sans-serif; }
        a { color:inherit; text-decoration:none; }
        .shell { display:flex; min-height:100vh; }
        .side { width:245px; background:var(--wine); color:#f8eee2; padding:30px 22px; display:flex; flex-direction:column; gap:28px; flex-shrink:0; }
        .brand { font:30px 'DM Serif Display',serif; line-height:1.05; }
        .brand small { display:block; font:11px 'DM Sans'; letter-spacing:2px; text-transform:uppercase; color:#d6bca8; margin-top:7px; }
        .nav { display:grid; gap:7px; }
        .nav a { padding:12px 14px; border-radius:9px; color:#e7d8ca; display:flex; align-items:center; gap:10px; }
        .nav a:hover, .nav a.active { background:#ffffff18; color:#fff; }
        .nav a.active { background:#ffffff28; font-weight:600; }
        .nav-icon { width:18px; text-align:center; flex-shrink:0; }
        .sidefoot { margin-top:auto; color:#cdb8a8; font-size:12px; }
        .main { flex:1; min-width:0; }
        .top { min-height:76px; background:var(--cream); border-bottom:1px solid var(--line); display:flex; align-items:center; justify-content:space-between; padding:0 5vw; }
        .top span { color:var(--muted); font-size:13px; }
        .content { max-width:1120px; padding:42px 5vw 70px; margin:auto; }
        .eyebrow { text-transform:uppercase; letter-spacing:2px; font-size:11px; color:var(--rust); font-weight:700; }
        .h1 { font:42px 'DM Serif Display',serif; margin:7px 0 12px; }
        .lead { color:var(--muted); line-height:1.7; max-width:680px; }
        .grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(230px,1fr)); gap:17px; margin-top:25px; }
        .card { background:var(--cream); border:1px solid var(--line); border-radius:15px; padding:22px; }
        .stat { font:34px 'DM Serif Display'; margin:7px 0; }
        .btn { display:inline-flex; align-items:center; justify-content:center; border:0; border-radius:8px; background:var(--wine); color:#fff; padding:12px 17px; font:600 14px 'DM Sans'; cursor:pointer; }
        .btn.secondary { background:var(--sand); color:var(--ink); }
        .btn:disabled { opacity:.4; cursor:not-allowed; }
        .field { width:100%; padding:12px; border:1px solid var(--line); border-radius:8px; background:white; font:inherit; }
        .alert { padding:13px 16px; border-radius:9px; background:#f4e7dc; color:#713d2c; margin:16px 0; }
        .alert.success { background:#dff0e4; color:#2d6a3f; }
        .person { display:flex; gap:13px; align-items:center; }
        .avatar { width:58px; height:58px; object-fit:cover; border-radius:50%; background:var(--sand); }
        .muted { color:var(--muted); font-size:13px; }
        .video { width:100%; aspect-ratio:16/9; border:0; border-radius:10px; background:#201a17; }
        .check { display:flex; gap:8px; align-items:center; }
        .foot { color:var(--muted); font-size:12px; margin-top:28px; }
        @media(max-width:760px) {
            .side { width:62px; padding:18px 8px; gap:16px; }
            .brand { display:flex; align-items:center; justify-content:center; font-size:0; }
            .brand::before { content:'YK'; font:22px 'DM Serif Display'; font-size:22px; }
            .brand small { display:none; }
            .nav a { padding:12px 8px; justify-content:center; border-radius:8px; }
            .nav a span:not(.nav-icon) { display:none; }
            .nav-icon { width:auto; font-size:17px; }
            .sidefoot { display:none; }
            .top { padding:0 18px; }
            .content { padding:28px 18px; }
            .h1 { font-size:30px; }
        }
    </style>
</head>
<body>
<div class="shell">
    <aside class="side">
        <a class="brand" href="{{ route('home') }}">YouTube Keşif<small>creator community</small></a>
        <nav class="nav">
            @php $cur = request()->routeIs(...['dashboard']) ? 'dashboard' : (request()->routeIs('discovery.users*') ? 'discovery' : (request()->routeIs('discovery.videos*') ? 'videos' : (request()->routeIs('profile*') ? 'profile' : (request()->routeIs('admin*') ? 'admin' : '')))); @endphp
            <a href="{{ route('dashboard') }}" class="{{ $cur==='dashboard' ? 'active' : '' }}"><span class="nav-icon">🏠</span><span>Genel Bakış</span></a>
            <a href="{{ route('discovery.users', 'normal') }}" class="{{ $cur==='discovery' ? 'active' : '' }}"><span class="nav-icon">👥</span><span>Kullanıcı Keşfi</span></a>
            <a href="{{ route('discovery.users', 'supporter') }}"><span class="nav-icon">⭐</span><span>Destekçiler</span></a>
            <a href="{{ route('discovery.videos') }}" class="{{ $cur==='videos' ? 'active' : '' }}"><span class="nav-icon">🎬</span><span>Video Keşfi</span></a>
            <a href="{{ route('profile.me') }}" class="{{ $cur==='profile' ? 'active' : '' }}"><span class="nav-icon">👤</span><span>Profilim</span></a>
            @if(auth()->check() && auth()->user()->role === 'admin')
                <a href="{{ route('admin.dashboard') }}" class="{{ $cur==='admin' ? 'active' : '' }}"><span class="nav-icon">🔧</span><span>Yönetim</span></a>
            @endif
        </nav>
        <div class="sidefoot">Daha iyi keşif, gerçek topluluk.<br>© {{ date('Y') }} AIAjan</div>
    </aside>
    <main class="main">
        <header class="top">
            <span>Bağımsız içerik üretici topluluğu</span>
            @if(auth()->check())
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn secondary" type="submit">Çıkış yap</button>
                </form>
            @else
                <a class="btn" href="{{ route('login') }}">Giriş yap</a>
            @endif
        </header>
        <section class="content">
            @if(session('status'))
                <div class="alert success">{{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="alert">{{ $errors->first() }}</div>
            @endif
            @yield('content')
        </section>
    </main>
</div>
</body>
</html>
