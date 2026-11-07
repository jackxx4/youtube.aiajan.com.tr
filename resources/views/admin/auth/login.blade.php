@extends('layouts.app')
@section('title', 'Yönetici Girişi')
@section('content')
<div style="max-width:440px;margin:30px auto;">
    <div class="card" style="padding:32px 28px;box-shadow:0 12px 36px rgba(0,0,0,0.06);border-top:4px solid var(--wine);">
        <div style="text-align:center;margin-bottom:24px;">
            <div style="width:54px;height:54px;border-radius:50%;background:var(--sand);color:var(--wine);display:inline-flex;align-items:center;justify-content:center;font-size:26px;margin-bottom:12px;">
                🛡️
            </div>
            <div class="eyebrow" style="color:var(--rust);">Yönetici Kontrol Merkezi</div>
            <h1 style="font:28px 'DM Serif Display',serif;margin:4px 0 6px;color:var(--wine);">Yönetici Girişi</h1>
            <p class="muted" style="font-size:13px;line-height:1.5;margin:0;">
                Bu alan yalnızca yetkili platform yöneticilerine ayrılmıştır. Standart kullanıcı hesapları erişemez.
            </p>
        </div>

        @if(session('status'))
            <div class="alert success" style="margin-bottom:18px;font-size:13px;">{{ session('status') }}</div>
        @endif

        @if($errors->any())
            <div class="alert" style="margin-bottom:18px;font-size:13px;">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('admin.login.submit') }}" style="display:flex;flex-direction:column;gap:14px;">
            @csrf
            <div>
                <label for="admin_email" style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;color:var(--ink);">
                    Yönetici E-Posta veya Kullanıcı Adı
                </label>
                <input type="text"
                    id="admin_email"
                    name="email"
                    class="field"
                    placeholder="ornek@aiajan.com.tr"
                    value="{{ old('email', 'jackalxxxx4@gmail.com') }}"
                    required
                    autofocus
                    style="margin:0;">
            </div>

            <div>
                <label for="admin_password" style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;color:var(--ink);">
                    Yönetici Parolası
                </label>
                <input type="password"
                    id="admin_password"
                    name="password"
                    class="field"
                    placeholder="••••••••••••"
                    required
                    style="margin:0;">
            </div>

            <div style="margin-top:6px;">
                <button type="submit" class="btn" style="width:100%;padding:13px;font-size:14px;background:var(--wine);font-weight:700;">
                    Yönetim Merkezine Giriş Yap →
                </button>
            </div>
        </form>

        <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--line);text-align:center;">
            <a href="{{ route('home') }}" class="muted" style="font-size:12px;text-decoration:underline;">
                ← Topluluk Ana Sayfasına Dön
            </a>
        </div>
    </div>
</div>
@endsection

