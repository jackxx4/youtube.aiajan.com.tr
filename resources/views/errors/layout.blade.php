<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · YouTube Keşif</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Serif+Display&display=swap" rel="stylesheet">
    <style>
        :root {
            --paper: #f5f0e7;
            --cream: #fffdf8;
            --ink: #30241e;
            --muted: #82766c;
            --wine: #56362f;
            --rust: #ad6548;
            --line: #e7ddd0;
            --sand: #e9dfd1;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: var(--paper);
            color: var(--ink);
            font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }
        .error-card {
            background: var(--cream);
            border: 1px solid var(--line);
            border-radius: 20px;
            max-width: 580px;
            width: 100%;
            padding: 48px 36px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(48, 36, 30, 0.06);
        }
        .error-badge {
            display: inline-block;
            font: 700 13px 'DM Sans', sans-serif;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--rust);
            background: rgba(173, 101, 72, 0.1);
            padding: 6px 14px;
            border-radius: 20px;
            margin-bottom: 18px;
        }
        .error-code {
            font: 72px 'DM Serif Display', Georgia, serif;
            color: var(--wine);
            line-height: 1;
            margin-bottom: 12px;
        }
        .error-title {
            font: 26px 'DM Serif Display', Georgia, serif;
            color: var(--wine);
            margin-bottom: 14px;
            line-height: 1.3;
        }
        .error-desc {
            color: var(--muted);
            font-size: 15px;
            line-height: 1.65;
            margin-bottom: 32px;
        }
        .btn-group {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: transform 0.15s ease, opacity 0.15s ease;
            border: none;
        }
        .btn:hover { transform: translateY(-1px); }
        .btn-primary {
            background: var(--wine);
            color: #fff;
        }
        .btn-secondary {
            background: var(--sand);
            color: var(--ink);
        }
        .error-foot {
            margin-top: 32px;
            font-size: 12px;
            color: var(--muted);
        }
        .error-foot a {
            color: var(--rust);
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-badge">@yield('badge', 'Sistem Bildirimi')</div>
        <div class="error-code">@yield('code', '500')</div>
        <h1 class="error-title">@yield('headline', 'Bir Aksaklık Oluştu')</h1>
        <p class="error-desc">@yield('message', 'İşleminiz şu anda gerçekleştirilemiyor. Güvenlik ve gizlilik standartlarımız gereği teknik detaylar sınırlandırılmıştır.')</p>
        <div class="btn-group">
            <a href="{{ url('/') }}" class="btn btn-primary">🏠 Ana Sayfaya Dön</a>
            <a href="{{ url('/iletisim') }}" class="btn btn-secondary">✉️ İletişim & Destek</a>
        </div>
        <div class="error-foot">
            YouTube Keşif · <a href="https://aiajan.com.tr" target="_blank">aiajan.com.tr</a> ekosistemi
        </div>
    </div>
</body>
</html>

