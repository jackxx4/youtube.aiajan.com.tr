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
        .side { width:245px; background:var(--wine); color:#f8eee2; padding:30px 22px; display:flex; flex-direction:column; gap:26px; flex-shrink:0; }
        .side-head { display:flex; align-items:center; justify-content:space-between; }
        .brand { font:30px 'DM Serif Display',serif; line-height:1.05; }
        .brand small { display:block; font:11px 'DM Sans'; letter-spacing:2px; text-transform:uppercase; color:#d6bca8; margin-top:7px; }
        .nav { display:grid; gap:7px; }
        .nav a { padding:12px 14px; border-radius:9px; color:#e7d8ca; display:flex; align-items:center; gap:10px; font-weight:500; transition:all 0.15s ease; }
        .nav a:hover, .nav a.active { background:#ffffff18; color:#fff; }
        .nav a.active { background:#ffffff28; font-weight:600; }
        .nav-icon { width:18px; text-align:center; flex-shrink:0; font-size:16px; }
        .sidefoot { margin-top:auto; color:#cdb8a8; font-size:12px; }
        .main { flex:1; min-width:0; }
        .top { min-height:76px; background:var(--cream); border-bottom:1px solid var(--line); display:flex; align-items:center; justify-content:space-between; padding:0 5vw; }
        .top-brand-group { display:flex; align-items:center; gap:14px; }
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

        /* Mobil Menü ve Kontroller (Varsayılan Masaüstü: Gizli) */
        .mobile-toggle { display:none; background:transparent; border:none; color:var(--ink); font-size:24px; cursor:pointer; padding:6px 10px; border-radius:8px; align-items:center; justify-content:center; }
        .mobile-toggle:hover { background:var(--sand); }
        .mobile-close { display:none; background:#ffffff18; border:none; color:#fff; width:34px; height:34px; border-radius:50%; font-size:16px; cursor:pointer; align-items:center; justify-content:center; }
        .mobile-close:hover { background:#ffffff30; }
        .mobile-top-brand { display:none; font:22px 'DM Serif Display',serif; color:var(--ink); }
        .mobile-backdrop { display:none; }
        .mobile-user-card { display:none; }
        .bottom-nav { display:none; }

        @media(max-width:768px) {
            .shell { display:block; min-height:100vh; }

            /* Yan menü -> Mobil Çekmece (Off-canvas Drawer) */
            .side {
                position:fixed;
                top:0;
                left:0;
                bottom:0;
                width:290px;
                max-width:85vw;
                height:100vh;
                z-index:1200;
                transform:translateX(-100%);
                transition:transform 0.28s cubic-bezier(0.4, 0, 0.2, 1);
                padding:24px 20px;
                overflow-y:auto;
                -webkit-overflow-scrolling:touch;
                gap:20px;
            }
            .side.is-open {
                transform:translateX(0);
                box-shadow:6px 0 35px rgba(0,0,0,0.45);
            }
            .mobile-close { display:inline-flex; }
            .mobile-user-card {
                display:block;
                background:#ffffff12;
                border-radius:12px;
                padding:12px 14px;
            }

            /* Karartma Perdesi (Backdrop) */
            .mobile-backdrop {
                display:block;
                position:fixed;
                inset:0;
                background:rgba(30, 20, 15, 0.6);
                backdrop-filter:blur(3px);
                -webkit-backdrop-filter:blur(3px);
                z-index:1100;
                opacity:0;
                pointer-events:none;
                transition:opacity 0.25s ease;
            }
            .mobile-backdrop.is-open {
                opacity:1;
                pointer-events:auto;
            }

            /* Mobil Üst Çubuk */
            .top {
                position:sticky;
                top:0;
                z-index:900;
                min-height:60px;
                padding:0 16px;
            }
            .mobile-toggle { display:inline-flex; }
            .mobile-top-brand { display:inline-block; }
            .desktop-top-text { display:none !important; }

            /* Alt Çubuk ve İçerik Boşluğu */
            .content { padding:22px 16px 88px; }
            .h1 { font-size:28px; }

            /* Mobil Alt Gezinme Çubuğu (Bottom Navigation) */
            .bottom-nav {
                display:flex;
                position:fixed;
                bottom:0;
                left:0;
                right:0;
                height:62px;
                background:var(--cream);
                border-top:1px solid var(--line);
                align-items:center;
                justify-content:space-around;
                z-index:1000;
                padding:0 4px;
                box-shadow:0 -2px 12px rgba(0,0,0,0.06);
            }
            .bottom-nav-item {
                flex:1;
                display:flex;
                flex-direction:column;
                align-items:center;
                justify-content:center;
                padding:6px 2px;
                border-radius:8px;
                color:var(--muted);
                text-decoration:none;
                font-size:11px;
                font-weight:500;
                gap:3px;
                border:none;
                background:transparent;
                cursor:pointer;
                transition:color 0.15s, background 0.15s;
                -webkit-tap-highlight-color:transparent;
            }
            .bottom-nav-item.active, .bottom-nav-item:hover {
                color:var(--wine);
                font-weight:700;
            }
            .bottom-nav-item .bottom-icon {
                font-size:19px;
                line-height:1;
            }
        }
    </style>
</head>
<body>

<!-- Mobil Karartma Perdesi -->
<div class="mobile-backdrop" id="mobileBackdrop" aria-hidden="true"></div>

<div class="shell">
    <aside class="side" id="sideMenu" role="navigation" aria-label="Ana Gezinme Menüsü" aria-hidden="true">
        <div class="side-head">
            <a class="brand" href="{{ route('home') }}">YouTube Keşif<small>aiajan.com.tr topluluğu</small></a>
            <button type="button" class="mobile-close" id="mobileMenuClose" aria-label="Menüyü Kapat">✕</button>
        </div>

        @if(auth()->check())
            <div class="mobile-user-card">
                <div class="person">
                    <div class="avatar" style="width:40px;height:40px;display:flex;align-items:center;justify-content:center;background:#ffffff22;color:#fff;font-weight:700;font-size:15px;border-radius:50%;">
                        {{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div style="overflow:hidden;">
                        <div style="font-weight:600;font-size:13px;color:#fff;white-space:nowrap;text-overflow:ellipsis;overflow:hidden;">{{ auth()->user()->name }}</div>
                        <div style="font-size:11px;color:#d6bca8;white-space:nowrap;text-overflow:ellipsis;overflow:hidden;">{{ auth()->user()->activeChannelLink?->title ?? 'Kanal bağlı değil' }}</div>
                    </div>
                </div>
            </div>
        @endif

        <nav class="nav">
            @php
                $cur = request()->routeIs(...['dashboard']) ? 'dashboard' :
                    (request()->routeIs('discovery.users*') ? (request()->route('pool') === 'supporter' ? 'supporter' : 'discovery') :
                    (request()->routeIs('discovery.videos*') ? 'videos' :
                    (request()->routeIs('profile*') ? 'profile' :
                    (request()->routeIs('admin*') ? 'admin' : ''))));
            @endphp
            <a href="{{ route('dashboard') }}" class="{{ $cur==='dashboard' ? 'active' : '' }}"><span class="nav-icon">🏠</span><span>Genel Bakış</span></a>
            <a href="{{ route('discovery.users', 'normal') }}" class="{{ $cur==='discovery' ? 'active' : '' }}"><span class="nav-icon">👥</span><span>Kullanıcı Keşfi</span></a>
            <a href="{{ route('discovery.users', 'supporter') }}" class="{{ $cur==='supporter' ? 'active' : '' }}"><span class="nav-icon">⭐</span><span>Destekçiler</span></a>
            <a href="{{ route('discovery.videos') }}" class="{{ $cur==='videos' ? 'active' : '' }}"><span class="nav-icon">🎬</span><span>Video Keşfi</span></a>
            <a href="{{ route('videos.showcase') }}" class="{{ request()->routeIs('videos.showcase*') ? 'active' : '' }}"><span class="nav-icon">✨</span><span>Video Vitrinim</span></a>
            <a href="{{ route('profile.me') }}" class="{{ $cur==='profile' ? 'active' : '' }}"><span class="nav-icon">👤</span><span>Profilim</span></a>
            @if(auth()->check() && auth()->user()->role === 'admin')
                <a href="{{ route('admin.dashboard') }}" class="{{ $cur==='admin' ? 'active' : '' }}"><span class="nav-icon">🔧</span><span>Yönetim</span></a>
            @endif
        </nav>

        <div class="sidefoot">
            @if(auth()->check())
                <div style="margin-bottom:12px;">
                    <form method="post" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn secondary" type="submit" style="width:100%;padding:9px;font-size:13px;background:#ffffff1a;color:#fff;border:1px solid #ffffff26;">🚪 Çıkış yap</button>
                    </form>
                </div>
            @endif
            Daha iyi keşif, gerçek topluluk.<br>
            © {{ date('Y') }} <a href="https://aiajan.com.tr" target="_blank" style="color:#d6bca8;text-decoration:underline;">aiajan</a>
            <span style="font-size:11px;opacity:0.85;padding:1px 6px;border-radius:4px;background:#ffffff18;margin-left:4px;">v{{ config('version.current', '1.4.0') }}</span>
            <div style="margin-top:10px;font-size:11px;display:flex;gap:6px;flex-wrap:wrap;color:#bcaaa4;line-height:1.4;">
                <a href="{{ route('privacy') }}" style="color:inherit;text-decoration:underline;">Gizlilik</a> ·
                <a href="{{ route('cookies') }}" style="color:inherit;text-decoration:underline;">Çerez</a> ·
                <a href="{{ route('kvkk') }}" style="color:inherit;text-decoration:underline;">KVKK</a> ·
                <a href="{{ route('contact') }}" style="color:inherit;text-decoration:underline;">İletişim</a>
            </div>
        </div>
    </aside>

    <main class="main" style="display:flex;flex-direction:column;min-height:100vh;">
        <header class="top">
            <div class="top-brand-group">
                <button type="button" class="mobile-toggle" id="mobileMenuOpen" aria-label="Menüyü Aç" aria-expanded="false">
                    ☰
                </button>
                <a href="{{ route('home') }}" class="mobile-top-brand">YouTube Keşif</a>
                <span class="desktop-top-text"><a href="https://aiajan.com.tr" target="_blank" style="color:var(--muted);text-decoration:none;font-weight:600;">aiajan.com.tr</a> · Bağımsız içerik üretici topluluğu</span>
            </div>
            <div>
                @if(auth()->check())
                    <form method="post" action="{{ route('logout') }}" style="display:inline;">
                        @csrf
                        <button class="btn secondary" type="submit" style="padding:8px 14px;font-size:13px;">Çıkış yap</button>
                    </form>
                @else
                    <a class="btn" href="{{ route('login') }}" style="padding:8px 14px;font-size:13px;">Giriş yap</a>
                @endif
            </div>
        </header>

        <section class="content" style="flex:1;">
            @if(session('status'))
                <div class="alert success">{{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="alert">{{ $errors->first() }}</div>
            @endif
            @yield('content')
        </section>

        <!-- Platform Zengin Footer (Masaüstü ve Mobil Uyumlu) -->
        <footer style="margin-top:auto;padding:32px 30px 24px;border-top:1px solid var(--line);font-size:13px;color:var(--muted);">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;">
                <div>
                    <strong style="color:var(--wine);font-size:15px;font-family:'DM Serif Display',serif;">YouTube Keşif</strong>
                    <span style="margin-left:6px;">· Bağımsız İçerik Üretici Ekosistemi</span>
                </div>
                <div style="display:flex;gap:14px;flex-wrap:wrap;align-items:center;">
                    <a href="{{ route('privacy') }}" style="color:inherit;text-decoration:underline;">Gizlilik</a>
                    <a href="{{ route('cookies') }}" style="color:inherit;text-decoration:underline;">Çerez Politikası</a>
                    <a href="{{ route('kvkk') }}" style="color:inherit;text-decoration:underline;">KVKK</a>
                    <a href="{{ route('contact') }}" style="color:inherit;text-decoration:underline;">İletişim & Destek</a>
                    <a href="https://aiajan.com.tr" target="_blank" style="color:var(--rust);text-decoration:underline;font-weight:600;">aiajan.com.tr ↗</a>
                </div>
            </div>
            <div style="margin-top:10px;font-size:11.5px;opacity:0.8;">
                © {{ date('Y') }} YouTube Keşif. Platform takipleri YouTube aboneliği değildir. Tüm hakları saklıdır.
            </div>
        </footer>
    </main>
</div>

<!-- Mobil Alt Gezinme Çubuğu (Bottom Navigation Bar) -->
<nav class="bottom-nav" role="navigation" aria-label="Mobil Hızlı Menü">
    <a href="{{ route('dashboard') }}" class="bottom-nav-item {{ $cur==='dashboard' ? 'active' : '' }}">
        <span class="bottom-icon">🏠</span>
        <span>Panel</span>
    </a>
    <a href="{{ route('discovery.users', 'normal') }}" class="bottom-nav-item {{ $cur==='discovery' ? 'active' : '' }}">
        <span class="bottom-icon">👥</span>
        <span>Keşfet</span>
    </a>
    <a href="{{ route('discovery.videos') }}" class="bottom-nav-item {{ $cur==='videos' ? 'active' : '' }}">
        <span class="bottom-icon">🎬</span>
        <span>Videolar</span>
    </a>
    <a href="{{ route('profile.me') }}" class="bottom-nav-item {{ $cur==='profile' ? 'active' : '' }}">
        <span class="bottom-icon">👤</span>
        <span>Profil</span>
    </a>
    <button type="button" class="bottom-nav-item" id="bottomMenuToggle" aria-label="Tüm Menüyü Aç">
        <span class="bottom-icon">☰</span>
        <span>Menü</span>
    </button>
</nav>

<!-- Mobil Menü JavaScript Kontrolcüsü -->
<script>
    (function() {
        const sideMenu = document.getElementById('sideMenu');
        const backdrop = document.getElementById('mobileBackdrop');
        const openBtn = document.getElementById('mobileMenuOpen');
        const closeBtn = document.getElementById('mobileMenuClose');
        const bottomBtn = document.getElementById('bottomMenuToggle');

        function openMenu() {
            if (!sideMenu || !backdrop) return;
            sideMenu.classList.add('is-open');
            backdrop.classList.add('is-open');
            sideMenu.setAttribute('aria-hidden', 'false');
            if (openBtn) openBtn.setAttribute('aria-expanded', 'true');
            if (bottomBtn) bottomBtn.setAttribute('aria-expanded', 'true');
            document.body.style.overflow = 'hidden';
        }

        function closeMenu() {
            if (!sideMenu || !backdrop) return;
            sideMenu.classList.remove('is-open');
            backdrop.classList.remove('is-open');
            sideMenu.setAttribute('aria-hidden', 'true');
            if (openBtn) openBtn.setAttribute('aria-expanded', 'false');
            if (bottomBtn) bottomBtn.setAttribute('aria-expanded', 'false');
            document.body.style.overflow = '';
        }

        if (openBtn) openBtn.addEventListener('click', openMenu);
        if (bottomBtn) bottomBtn.addEventListener('click', openMenu);
        if (closeBtn) closeBtn.addEventListener('click', closeMenu);
        if (backdrop) backdrop.addEventListener('click', closeMenu);

        // ESC tuşuyla kapatma
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sideMenu && sideMenu.classList.contains('is-open')) {
                closeMenu();
            }
        });

        // Menüdeki bir linke tıklandığında menüyü kapat
        const links = sideMenu ? sideMenu.querySelectorAll('nav.nav a') : [];
        links.forEach(function(link) {
            link.addEventListener('click', closeMenu);
        });
    })();
</script>

<!-- Canlı Versiyon Takip & Bildirim Motoru -->
<script type="module">
    import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.12.0/firebase-app.js';
    import { getFirestore, doc, onSnapshot } from 'https://www.gstatic.com/firebasejs/10.12.0/firebase-firestore.js';

    const CURRENT_VERSION = "{{ config('version.current', '1.4.0') }}";

    try {
        const firebaseConfig = {
            apiKey: "{{ config('services.firebase.api_key') }}",
            authDomain: "{{ config('services.firebase.project_id', 'aiajan-portfolio') }}.firebaseapp.com",
            projectId: "{{ config('services.firebase.project_id', 'aiajan-portfolio') }}"
        };
        const app = initializeApp(firebaseConfig);
        const db = getFirestore(app);
        const verRef = doc(db, 'meta', 'youtube_version');
        let firstRun = true;

        onSnapshot(verRef, (snap) => {
            if (!snap.exists()) return;
            const remoteVer = snap.data()?.current;
            if (firstRun) { firstRun = false; return; }
            if (remoteVer && remoteVer !== CURRENT_VERSION) {
                showUpdateToast(remoteVer);
            }
        });
    } catch (e) {
        setInterval(async () => {
            try {
                const res = await fetch("{{ route('api.version') }}");
                if (res.ok) {
                    const data = await res.json();
                    if (data.version && data.version !== CURRENT_VERSION) {
                        showUpdateToast(data.version);
                    }
                }
            } catch (_) {}
        }, 60000);
    }

    function showUpdateToast(newVer) {
        let t = document.getElementById('update-toast');
        if (t) t.remove();
        t = document.createElement('div');
        t.id = 'update-toast';
        t.innerHTML = `
            <span>🚀 Yeni sürüm <strong>v${newVer}</strong> yayında!</span>
            <button onclick="location.reload()" style="margin-left:14px;padding:6px 12px;border:none;border-radius:6px;background:var(--wine);color:#fff;font-weight:600;cursor:pointer;">Yenile</button>
            <button onclick="this.parentElement.remove()" style="margin-left:6px;padding:4px 8px;border:none;background:transparent;color:var(--muted);cursor:pointer;font-size:16px;">✕</button>
        `;
        const isMobile = window.innerWidth <= 768;
        t.style.cssText = `position:fixed;bottom:${isMobile ? '74px' : '24px'};right:${isMobile ? '16px' : '24px'};left:${isMobile ? '16px' : 'auto'};background:var(--cream);border:1px solid var(--line);color:var(--ink);padding:14px 18px;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,0.15);display:flex;align-items:center;justify-content:space-between;font-size:14px;z-index:99999;`;
        document.body.appendChild(t);
    }
</script>

<!-- Çerez Onay Çubuğu (Cookie Consent Banner) -->
<div id="cookieBanner" style="display:none;position:fixed;bottom:24px;left:20px;right:20px;max-width:580px;margin:0 auto;background:#201a17;color:#fbf7f2;border-radius:14px;padding:16px 20px;box-shadow:0 12px 36px rgba(0,0,0,0.45);z-index:99999;border:1px solid #3c312c;">
    <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
        <span style="font-size:26px;line-height:1;">🍪</span>
        <div style="flex:1;min-width:220px;font-size:12.5px;line-height:1.5;">
            Deneyiminizi optimize etmek ve platform güvenliğini sağlamak için asgari çerezler kullanıyoruz. Detaylar için <a href="{{ route('cookies') }}" style="color:#d6bca8;text-decoration:underline;">Çerez Politikası</a> ve <a href="{{ route('kvkk') }}" style="color:#d6bca8;text-decoration:underline;">KVKK</a> metinlerimizi inceleyebilirsiniz.
        </div>
        <div style="display:flex;gap:8px;align-items:center;">
            <button type="button" id="cookieAcceptBtn" class="btn" style="padding:7px 16px;font-size:12px;background:var(--rust);color:#fff;border:none;cursor:pointer;">Kabul Et</button>
            <button type="button" id="cookieDismissBtn" class="btn secondary" style="padding:7px 12px;font-size:12px;background:transparent;color:#d6bca8;border:1px solid #ffffff26;cursor:pointer;">Kapat</button>
        </div>
    </div>
</div>

<script>
(function(){
    try {
        const choice = localStorage.getItem('cookie_consent_choice');
        const banner = document.getElementById('cookieBanner');
        if(!choice && banner) {
            banner.style.display = 'block';
        }
        document.getElementById('cookieAcceptBtn')?.addEventListener('click', function(){
            localStorage.setItem('cookie_consent_choice', 'accepted');
            if(banner) banner.style.display = 'none';
        });
        document.getElementById('cookieDismissBtn')?.addEventListener('click', function(){
            localStorage.setItem('cookie_consent_choice', 'dismissed');
            if(banner) banner.style.display = 'none';
        });
    } catch(e) {}
})();
</script>
</body>
</html>
