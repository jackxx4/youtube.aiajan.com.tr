@extends('layouts.app')
@section('title', 'Kanal Seçimi')
@section('content')
<div class="eyebrow">Hesap kurulumu</div>
<h1 class="h1">Temsil edeceğin YouTube kanalını bağla</h1>
<p class="lead">Toplulukta içeriklerinizi sergilemek ve diğer üreticilerle bağ kurmak için kanalınızı bağlayın veya güncelleyin.</p>

@if($active && $active->channel)
    <div class="card" style="margin-top:18px;border-left:4px solid var(--wine);background:#fbf7f2;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div style="display:flex;align-items:center;gap:12px;">
            @if($active->channel->thumbnail_url)
                <img src="{{ $active->channel->thumbnail_url }}" alt="{{ $active->channel->title }}" style="width:44px;height:44px;border-radius:50%;object-fit:cover;">
            @else
                <span style="font-size:24px;">📺</span>
            @endif
            <div>
                <div style="font-size:12px;color:var(--rust);font-weight:700;text-transform:uppercase;letter-spacing:1px;">Mevcut Bağlı Kanalınız</div>
                <strong style="color:var(--wine);font-size:16px;">{{ $active->channel->title }}</strong>
                @if($active->channel->custom_url)
                    <span style="font-size:13px;color:var(--muted);margin-left:6px;">({{ $active->channel->custom_url }})</span>
                @endif
            </div>
        </div>
        <span style="font-size:12px;color:var(--muted);background:var(--sand);padding:4px 10px;border-radius:6px;">Yeni bir kanal seçerek güncelleyebilirsiniz</span>
    </div>
@endif

<div id="channel-alert" class="alert" style="display:none;margin-top:18px;"></div>

@if(session('status'))
    <div class="alert success">{{ session('status') }}</div>
@endif
@if($errors->any())
    <div class="alert">{{ $errors->first() }}</div>
@endif

<!-- Dinamik Google Kanal Listesi Konteyneri -->
<div id="dynamic-channels-container" style="{{ $channels->isEmpty() ? 'display:none;' : '' }}margin-top:24px;">
    <div class="card" style="border:2px solid var(--wine);padding:24px;">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:14px;">
            <div>
                <div class="eyebrow" style="color:var(--wine);">Bağlı YouTube Kanallarınız ({{ $channels->count() }})</div>
                <h2 style="font:24px 'DM Serif Display';margin:4px 0 0;">Yönetmek İstediğiniz Kanalı Seçin</h2>
            </div>
            <span class="muted" style="font-size:12px;">Her kanalın havuzda yer alması için 3 video seçilmelidir</span>
        </div>
        
        <div id="channel-cards-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;margin-bottom:10px;">
            @foreach($channels as $ch)
                @php
                    $isActive = $active && $active->youtube_channel_id === $ch->id;
                    $showcaseCount = $ch->showcase_count ?? 0;
                    $isReady = ($showcaseCount === 3);
                @endphp
                <div style="border:2px solid {{ $isActive ? 'var(--rust)' : 'var(--line)' }};border-radius:12px;padding:16px;background:{{ $isActive ? '#fdf8f5' : 'var(--cream)' }};display:flex;flex-direction:column;justify-content:space-between;gap:12px;box-shadow:0 2px 8px rgba(0,0,0,0.03);">
                    <div style="display:flex;align-items:center;gap:12px;">
                        @if($ch->thumbnail_url)
                            <img src="{{ $ch->thumbnail_url }}" alt="{{ $ch->title }}" style="width:48px;height:48px;border-radius:50%;object-fit:cover;border:1px solid var(--line);flex-shrink:0;">
                        @else
                            <div style="width:48px;height:48px;border-radius:50%;background:var(--sand);display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--wine);flex-shrink:0;">{{ strtoupper(mb_substr($ch->title, 0, 1)) }}</div>
                        @endif
                        <div style="overflow:hidden;flex:1;">
                            <div style="font-weight:700;font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="{{ $ch->title }}">{{ $ch->title }}</div>
                            @if($ch->custom_url)
                                <div style="font-size:12px;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $ch->custom_url }}</div>
                            @endif
                        </div>
                    </div>

                    <!-- Durum Etiketleri -->
                    <div style="display:flex;gap:6px;flex-wrap:wrap;font-size:11px;">
                        @if($isActive)
                            <span style="padding:3px 8px;border-radius:6px;background:#e6f4ea;color:#137333;font-weight:700;">● Aktif Yönetilen</span>
                        @endif

                        @if($isReady)
                            <span style="padding:3px 8px;border-radius:6px;background:#fef7e0;color:#b06000;font-weight:600;">✓ 3/3 Video Vitrinde</span>
                        @else
                            <span style="padding:3px 8px;border-radius:6px;background:#fff0ed;color:#c5221f;font-weight:600;">⚠️ {{ $showcaseCount }}/3 Video (Vitrin Bekleniyor)</span>
                        @endif
                    </div>

                    <!-- Aksiyon Butonları -->
                    <div style="display:flex;gap:8px;align-items:center;padding-top:10px;border-top:1px solid var(--line);">
                        @if($isActive)
                            <a href="{{ route('videos.showcase') }}" class="btn" style="flex:1;padding:8px 12px;font-size:12px;text-align:center;">
                                ✨ Vitrini Yönet
                            </a>
                        @else
                            <form method="post" action="{{ route('channels.store') }}" style="flex:1;margin:0;">
                                @csrf
                                <input type="hidden" name="channel_id" value="{{ $ch->youtube_channel_id }}">
                                <button class="btn secondary" type="submit" style="width:100%;padding:8px 12px;font-size:12px;cursor:pointer;">
                                    Bu Kanalı Yönet →
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div style="margin-top:25px;max-width:620px;">
    <!-- Tek Güvenli Seçenek: Google OAuth Doğrulaması ile Gerçek Kanal Ekleme -->
    <div class="card">
        <div class="eyebrow" style="color:var(--wine);">Resmi Google OAuth Doğrulaması</div>
        <h2 style="font:25px 'DM Serif Display';margin:10px 0;">Google Hesabımdan Kanal Ekle</h2>
        <p class="muted" style="margin-bottom:14px;line-height:1.6;">
            Google hesabınıza bağlı diğer YouTube kanallarınızı (Marka Hesaplarınızı) açılan Google penceresinden seçerek mevcut profilinize ekleyin. Yalnızca sahibi olduğunuz doğrulanmış kanallar ve son 10 videoları sisteme aktarılır.
        </p>
        <div style="background:#f5ede3;border:1px solid #e3d3c2;border-radius:8px;padding:10px 13px;margin-bottom:16px;font-size:12px;color:#6b5247;line-height:1.5;">
            💡 <strong>Birden Fazla Kanalınız Varsa:</strong> Google penceresinde diğer YouTube kanalınızı (Marka Hesabınızı) seçtiğinizde Google doğrulama uyarısı gösterirse, <strong>"Gelişmiş"</strong> → <strong>"YouTube Keşif uygulamasına git"</strong> → <strong>"Devam"</strong> adımıyla kanalınızı güvenle mevcut hesabınıza bağlayabilirsiniz.
        </div>

        <button id="btn-sync-google" class="btn" type="button" style="width:100%;padding:14px;background:#4285F4;display:flex;align-items:center;justify-content:center;gap:10px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#fff"/>
                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#fff" opacity="0.9"/>
            </svg>
            <span id="btn-sync-text">Google ile Doğrulanmış Kanalımı Getir</span>
        </button>
    </div>
</div>

<!-- Firebase Google OAuth ile Doğrulanmış Kanal Çekme Skripti -->
<script type="module">
    import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.12.0/firebase-app.js';
    import { getAuth, signInWithPopup, GoogleAuthProvider } from 'https://www.gstatic.com/firebasejs/10.12.0/firebase-auth.js';

    const firebaseConfig = {
        apiKey: "{{ config('services.firebase.api_key') }}",
        authDomain: "{{ config('services.firebase.auth_domain', config('services.firebase.project_id', 'aiajan-portfolio') . '.firebaseapp.com') }}",
        projectId: "{{ config('services.firebase.project_id', 'aiajan-portfolio') }}"
    };

    const app = initializeApp(firebaseConfig);
    const auth = getAuth(app);
    const ytProvider = new GoogleAuthProvider();
    ytProvider.addScope('openid');
    ytProvider.addScope('email');
    ytProvider.addScope('profile');
    ytProvider.addScope('https://www.googleapis.com/auth/youtube.readonly');
    ytProvider.setCustomParameters({ prompt: 'select_account' });

    const syncBtn = document.getElementById('btn-sync-google');
    const syncText = document.getElementById('btn-sync-text');
    const alertBox = document.getElementById('channel-alert');

    function showAlert(msg, isError = true) {
        alertBox.style.display = 'block';
        alertBox.className = isError ? 'alert' : 'alert success';
        alertBox.textContent = msg;
    }

    syncBtn?.addEventListener('click', async () => {
        syncBtn.disabled = true;
        syncText.textContent = 'Google ile doğrulanıyor...';
        alertBox.style.display = 'none';

        try {
            const result = await signInWithPopup(auth, ytProvider);
            const credential = GoogleAuthProvider.credentialFromResult(result);
            const accessToken = credential?.accessToken || result?._tokenResponse?.oauthAccessToken || '';

            if (!accessToken) {
                showAlert('Google erişim belirteci alınamadı. Lütfen izin ekranında YouTube erişimini onaylayarak tekrar deneyin.');
                syncBtn.disabled = false;
                syncText.textContent = 'Google ile Doğrulanmış Kanalımı Getir';
                return;
            }

            syncText.textContent = 'Doğrulanmış kanal ve videoları aktarılıyor...';

            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const resp = await fetch("{{ route('channels.sync_google') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    accessToken: accessToken
                })
            });

            const data = await resp.json();

            if (resp.ok && data.success) {
                showAlert(data.message || 'Kanal başarıyla eklendi! Sayfa güncelleniyor...', false);
                syncText.textContent = '✓ Kanal Eklendi, Yenileniyor...';
                setTimeout(() => window.location.reload(), 500);
            } else {
                showAlert(data.message || 'Seçilen Google hesabında yetkili bir YouTube kanalı bulunamadı.');
                syncBtn.disabled = false;
                syncText.textContent = 'Google ile Doğrulanmış Kanalımı Getir';
            }
        } catch (err) {
            console.error(err);
            if (err.code !== 'auth/popup-closed-by-user') {
                showAlert('Google kanal doğrulaması tamamlanamadı (' + (err.message || err.code) + ').');
            }
            syncBtn.disabled = false;
            syncText.textContent = 'Google ile Doğrulanmış Kanalımı Getir';
        }
    });
</script>
@endsection
