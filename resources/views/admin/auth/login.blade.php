<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Yönetici Girişi · YouTube Keşif</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Serif+Display&display=swap" rel="stylesheet">
    <style>
        :root {
            --paper: #1c1512;
            --card-bg: #261c18;
            --wine: #56362f;
            --rust: #b85030;
            --accent: #ad6548;
            --line: #3d2c25;
            --text: #f8eee2;
            --muted: #a69183;
            --sand: #362822;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: var(--paper);
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .login-card {
            background: var(--card-bg);
            border: 1px solid var(--line);
            border-radius: 20px;
            max-width: 460px;
            width: 100%;
            padding: 40px 32px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            position: relative;
        }
        .badge-admin {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #b8503022;
            color: #e07050;
            border: 1px solid #b8503055;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 14px;
        }
        .title {
            font: 32px 'DM Serif Display', serif;
            color: #fff;
            margin-bottom: 8px;
            line-height: 1.15;
        }
        .subtitle {
            font-size: 13px;
            color: var(--muted);
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .field-group {
            margin-bottom: 16px;
            text-align: left;
        }
        .field-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 6px;
            color: #ecdacb;
        }
        .input-field {
            width: 100%;
            padding: 13px 16px;
            border-radius: 10px;
            background: #18120f;
            border: 1px solid var(--line);
            color: #fff;
            font: inherit;
            font-size: 14px;
            outline: none;
            transition: border-color .15s ease;
        }
        .input-field:focus {
            border-color: var(--rust);
        }
        .btn-submit {
            width: 100%;
            padding: 14px;
            border-radius: 10px;
            background: var(--rust);
            color: #fff;
            font: 700 15px 'DM Sans', sans-serif;
            border: none;
            cursor: pointer;
            transition: opacity .15s ease, transform .1s ease;
            margin-top: 8px;
        }
        .btn-submit:hover {
            opacity: 0.92;
        }
        .btn-submit:active {
            transform: scale(0.99);
        }
        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13px;
            line-height: 1.5;
            margin-bottom: 18px;
        }
        .alert-error {
            background: #3e1b18;
            border: 1px solid #752920;
            color: #ffb8b0;
        }
        .alert-success {
            background: #16331e;
            border: 1px solid #285934;
            color: #b3e6c0;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: var(--muted);
            font-size: 12px;
            text-decoration: underline;
        }
        .back-link:hover {
            color: #fff;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div style="text-align:center;">
        <span class="badge-admin">🛡️ Bağımsız Yönetim Paneli</span>
        <h1 class="title">Yönetici Girişi</h1>
        <p class="subtitle">
            Bu ekran kullanıcı hesabınızdan ve Google OAuth'tan tamamen bağımsızdır. Platform yöneticisi kimliğinizle doğrudan giriş yapın.
        </p>
    </div>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('admin.login.submit') }}">
        @csrf

        <div class="field-group">
            <label for="admin_login">Yönetici Kullanıcı Adı veya E-Posta</label>
            <input type="text"
                id="admin_login"
                name="login"
                class="input-field"
                placeholder="Kullanıcı adı veya e-posta adresi"
                value="{{ old('login', old('email')) }}"
                required
                autofocus>
        </div>

        <div class="field-group">
            <label for="admin_password">Yönetici Parolası</label>
            <input type="password"
                id="admin_password"
                name="password"
                class="input-field"
                placeholder="••••••••••••"
                required>
        </div>

        <button type="submit" class="btn-submit">
            Yönetim Merkezine Giriş Yap →
        </button>
    </form>

    <div style="text-align:center;">
        <a href="{{ route('home') }}" class="back-link">
            ← Platform Ana Sayfasına Dön
        </a>
    </div>
</div>

</body>
</html>
