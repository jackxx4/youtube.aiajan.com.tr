<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Yönetim Merkezi') · YouTube Keşif Yönetimi</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=DM+Serif+Display&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-base: #140e0b;
            --bg-surface: #1e1612;
            --bg-surface-elevated: #281e18;
            --bg-surface-hover: #332720;
            --line-subtle: #2f221a;
            --line-border: #443228;
            --text-main: #f5ebe1;
            --text-muted: #9e897a;
            --text-dim: #6e5c50;
            --accent-rust: #c85a32;
            --accent-rust-hover: #dc6a3f;
            --accent-rust-soft: rgba(200, 90, 50, 0.14);
            --accent-wine: #56362f;
            --status-success: #38bdf8;
            --status-ok: #34d399;
            --status-danger: #f87171;
            --status-danger-bg: rgba(248, 113, 113, 0.12);
            --status-warning: #fbbf24;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background-color: var(--bg-base);
            color: var(--text-main);
            font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        a { color: inherit; text-decoration: none; }

        /* Ana İskelet */
        .admin-shell {
            display: flex;
            min-height: 100vh;
        }

        /* Sol Sidebar (Müstakil Yönetim Menüsü) */
        .admin-sidebar {
            width: 270px;
            background: var(--bg-surface);
            border-right: 1px solid var(--line-subtle);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            padding: 24px 18px;
            gap: 20px;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }

        .admin-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 4px 6px 12px;
            border-bottom: 1px solid var(--line-subtle);
        }

        .brand-icon {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, var(--accent-rust) 0%, #7c2d12 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
            box-shadow: 0 4px 12px rgba(200, 90, 50, 0.3);
        }

        .brand-text h1 {
            font: 18px 'DM Serif Display', serif;
            color: #fff;
            letter-spacing: 0.3px;
            line-height: 1.15;
        }

        .brand-text small {
            font-size: 11px;
            font-family: 'JetBrains Mono', monospace;
            color: var(--accent-rust);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }

        /* Yönetici Profil Kartı */
        .admin-profile-badge {
            background: var(--bg-surface-elevated);
            border: 1px solid var(--line-subtle);
            border-radius: 12px;
            padding: 12px 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .admin-avatar {
            width: 38px;
            height: 38px;
            border-radius: 9px;
            background: #2f221a;
            border: 1px solid var(--line-border);
            color: var(--accent-rust);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 15px;
        }

        .admin-details {
            overflow: hidden;
            flex: 1;
        }

        .admin-name {
            font-weight: 600;
            font-size: 13px;
            color: #fff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .admin-role {
            font-size: 11px;
            color: var(--status-ok);
            display: flex;
            align-items: center;
            gap: 5px;
            font-family: 'JetBrains Mono', monospace;
        }

        .admin-role::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--status-ok);
            box-shadow: 0 0 8px var(--status-ok);
        }

        /* Navigasyon Linkleri */
        .nav-section-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: var(--text-dim);
            padding: 6px 8px 4px;
        }

        .admin-nav {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .admin-nav-item {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 10px 12px;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-muted);
            transition: all 0.15s ease;
            border: 1px solid transparent;
        }

        .admin-nav-item:hover {
            background: var(--bg-surface-elevated);
            color: #fff;
            border-color: var(--line-subtle);
        }

        .admin-nav-item.active {
            background: var(--accent-rust-soft);
            color: #ff9b73;
            font-weight: 600;
            border-color: rgba(200, 90, 50, 0.35);
        }

        .admin-nav-item .icon {
            font-size: 16px;
            width: 20px;
            text-align: center;
        }

        .admin-nav-item .count-badge {
            margin-left: auto;
            background: var(--bg-surface-elevated);
            color: var(--text-muted);
            font-size: 11px;
            font-family: 'JetBrains Mono', monospace;
            padding: 2px 7px;
            border-radius: 10px;
            font-weight: 600;
        }

        .admin-nav-item.active .count-badge {
            background: rgba(200, 90, 50, 0.3);
            color: #ffbba1;
        }

        /* Alt Araçlar & Çıkış */
        .admin-sidebar-foot {
            margin-top: auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding-top: 14px;
            border-top: 1px solid var(--line-subtle);
        }

        .btn-foot {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 9px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
            border: 1px solid var(--line-border);
            background: var(--bg-surface-elevated);
            color: var(--text-muted);
        }

        .btn-foot:hover {
            color: #fff;
            background: var(--bg-surface-hover);
        }

        .btn-foot.danger:hover {
            background: var(--status-danger-bg);
            color: var(--status-danger);
            border-color: rgba(248, 113, 113, 0.4);
        }

        /* Ana İçerik Alanı */
        .admin-main {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            background: var(--bg-base);
        }

        /* Üst Header */
        .admin-topbar {
            height: 64px;
            background: var(--bg-surface);
            border-bottom: 1px solid var(--line-subtle);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .system-tag {
            background: rgba(56, 189, 248, 0.1);
            color: var(--status-success);
            border: 1px solid rgba(56, 189, 248, 0.25);
            padding: 3px 9px;
            border-radius: 6px;
            font-size: 11px;
            font-family: 'JetBrains Mono', monospace;
            font-weight: 600;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* Sayfa İçeriği */
        .admin-content {
            padding: 32px 36px 60px;
            max-width: 1360px;
            width: 100%;
            margin: 0 auto;
        }

        /* Kartlar & UI Elemanları */
        .ui-card {
            background: var(--bg-surface);
            border: 1px solid var(--line-subtle);
            border-radius: 14px;
            padding: 24px;
            margin-bottom: 24px;
        }

        .ui-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .ui-card-title {
            font: 20px 'DM Serif Display', serif;
            color: #fff;
            margin-bottom: 2px;
        }

        .ui-card-subtitle {
            font-size: 12px;
            color: var(--text-muted);
        }

        /* İstatistik Izgarası */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: var(--bg-surface);
            border: 1px solid var(--line-subtle);
            border-radius: 12px;
            padding: 18px 20px;
            transition: border-color 0.15s ease;
        }

        .stat-card:hover {
            border-color: var(--line-border);
        }

        .stat-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-dim);
            margin-bottom: 8px;
        }

        .stat-value {
            font: 32px 'DM Serif Display', serif;
            color: #fff;
            line-height: 1.1;
            margin-bottom: 6px;
        }

        .stat-meta {
            font-size: 11px;
            color: var(--text-muted);
        }

        /* Butonlar */
        .btn-admin {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 9px 15px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.15s ease;
            white-space: nowrap;
        }

        .btn-admin-primary {
            background: var(--accent-rust);
            color: #fff;
        }

        .btn-admin-primary:hover {
            background: var(--accent-rust-hover);
        }

        .btn-admin-secondary {
            background: var(--bg-surface-elevated);
            color: var(--text-main);
            border-color: var(--line-border);
        }

        .btn-admin-secondary:hover {
            background: var(--bg-surface-hover);
            color: #fff;
        }

        .btn-admin-danger {
            background: var(--status-danger-bg);
            color: var(--status-danger);
            border-color: rgba(248, 113, 113, 0.3);
        }

        .btn-admin-danger:hover {
            background: var(--status-danger);
            color: #fff;
        }

        /* Tablolar */
        .table-responsive {
            overflow-x: auto;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            text-align: left;
        }

        .admin-table th {
            padding: 12px 14px;
            border-bottom: 1px solid var(--line-border);
            color: var(--text-muted);
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .admin-table td {
            padding: 14px;
            border-bottom: 1px solid var(--line-subtle);
            vertical-align: middle;
        }

        .admin-table tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }

        /* Bildirimler */
        .admin-alert {
            padding: 14px 18px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .admin-alert-success {
            background: rgba(52, 211, 153, 0.12);
            border: 1px solid rgba(52, 211, 153, 0.3);
            color: #a7f3d0;
        }

        .admin-alert-danger {
            background: var(--status-danger-bg);
            border: 1px solid rgba(248, 113, 113, 0.35);
            color: #fecaca;
        }

        /* Form Elemanları */
        .admin-input {
            width: 100%;
            padding: 10px 14px;
            border-radius: 8px;
            background: var(--bg-base);
            border: 1px solid var(--line-border);
            color: #fff;
            font: inherit;
            font-size: 13px;
            outline: none;
            transition: border-color 0.15s ease;
        }

        .admin-input:focus {
            border-color: var(--accent-rust);
        }

        @media (max-width: 900px) {
            .admin-shell { flex-direction: column; }
            .admin-sidebar { width: 100%; height: auto; position: static; }
            .admin-content { padding: 20px 16px; }
        }
    </style>
</head>
<body>

<div class="admin-shell">
    <!-- Sol Sidebar (Müstakil Yönetim Menüsü) -->
    <aside class="admin-sidebar">
        <div class="admin-brand">
            <div class="brand-icon">🛡️</div>
            <div class="brand-text">
                <h1>AİAJAN YÖNETİM</h1>
                <small>GÜVENLİK & DENETİM</small>
            </div>
        </div>

        <!-- Yönetici Kimlik Kartı -->
        <div class="admin-profile-badge">
            <div class="admin-avatar">
                {{ strtoupper(mb_substr(Auth::guard('admin')->user()->name ?? 'E', 0, 1)) }}
            </div>
            <div class="admin-details">
                <div class="admin-name">{{ Auth::guard('admin')->user()->name ?? 'Emrah' }}</div>
                <div class="admin-role">Yönetici Oturumu</div>
            </div>
        </div>

        <!-- Yönetim Menüsü -->
        <div class="nav-section-title">YÖNETİM KONSOLU</div>
        <nav class="admin-nav">
            <a href="{{ route('admin.dashboard') }}" class="admin-nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <span class="icon">📊</span>
                <span>Genel Bakış</span>
            </a>
            <a href="{{ route('admin.users') }}" class="admin-nav-item {{ request()->routeIs('admin.users*') ? 'active' : '' }}">
                <span class="icon">👥</span>
                <span>Kullanıcı & Hesaplar</span>
            </a>
            <a href="{{ route('admin.channels') }}" class="admin-nav-item {{ request()->routeIs('admin.channels*') ? 'active' : '' }}">
                <span class="icon">📺</span>
                <span>Kanal Yönetimi</span>
            </a>
            <a href="{{ route('admin.dashboard') }}#talepler" class="admin-nav-item">
                <span class="icon">💬</span>
                <span>Destek Talepleri</span>
            </a>
        </nav>

        <div class="nav-section-title" style="margin-top: 10px;">GÜVENLİK & TELEMETRİ</div>
        <div style="font-size:11px;font-family:'JetBrains Mono',monospace;color:var(--text-muted);display:flex;flex-direction:column;gap:5px;padding:6px 10px;background:var(--bg-surface-elevated);border-radius:8px;border:1px solid var(--line-subtle);">
            <div>ORTAM: <span style="color:var(--status-ok);">{{ strtoupper(app()->environment()) }}</span></div>
            <div>VERİTABANI: <span style="color:#ffbba1;">{{ strtoupper(DB::connection()->getDriverName()) }}</span></div>
            <div>PHP: <span style="color:#cbd5e1;">{{ PHP_VERSION }}</span></div>
            <div>SÜRÜM: <span style="color:var(--accent-rust);">v{{ config('version.current', '1.6.5') }}</span></div>
        </div>

        <!-- Alt İşlemler -->
        <div class="admin-sidebar-foot">
            <form method="POST" action="{{ route('admin.clear_cache') }}" onsubmit="return confirm('Sistem önbelleklerini temizlemek istediğinize emin misiniz?');">
                @csrf
                <button type="submit" class="btn-foot">
                    <span>🧹 Önbelleği Temizle</span>
                </button>
            </form>

            <a href="{{ route('home') }}" target="_blank" class="btn-foot">
                <span>🌐 Platformu Aç ↗</span>
            </a>

            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="btn-foot danger">
                    <span>🚪 Güvenli Çıkış</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- Sağ Ana Panel -->
    <main class="admin-main">
        <header class="admin-topbar">
            <div class="topbar-left">
                <span class="system-tag">MIT SEVİYESİ SIFIR-GÜVEN KORUMALI</span>
                <span style="font-size:13px;color:var(--text-muted);">Platform Yönetim ve Denetim Merkezi</span>
            </div>

            <div class="topbar-right">
                <form method="POST" action="{{ route('admin.logout') }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn-admin btn-admin-danger" style="padding:6px 12px;font-size:12px;">
                        🚪 Çıkış
                    </button>
                </form>
            </div>
        </header>

        <section class="admin-content">
            @if(session('status'))
                <div class="admin-alert admin-alert-success">
                    <span>✓</span>
                    <div>{{ session('status') }}</div>
                </div>
            @endif

            @if($errors->any())
                <div class="admin-alert admin-alert-danger">
                    <span>⚠️</span>
                    <div>{{ $errors->first() }}</div>
                </div>
            @endif

            @yield('content')
        </section>
    </main>
</div>

</body>
</html>
