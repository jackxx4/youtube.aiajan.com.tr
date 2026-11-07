@extends('layouts.app')
@section('title', 'Kanal Seçimi')
@section('content')
<div class="eyebrow">Hesap kurulumu</div>
<h1 class="h1">Temsil edeceğin YouTube kanalını bağla</h1>
<p class="lead">Toplulukta içeriklerinizi sergilemek ve diğer üreticilerle bağ kurmak için kanalınızı bağlayın. Kanal değişimi 30 günde bir yapılabilir.</p>

<div id="channel-alert" class="alert" style="display:none;margin-top:18px;"></div>

@if(session('status'))
    <div class="alert success">{{ session('status') }}</div>
@endif
@if($errors->any())
    <div class="alert">{{ $errors->first() }}</div>
@endif

<!-- Dinamik Google Kanal Listesi Konteyneri -->
<div id="dynamic-channels-container" style="{{ $channels->isEmpty() ? 'display:none;' : '' }}margin-top:24px;">
    <div class="card" style="border:2px solid var(--wine);">
        <div class="eyebrow" style="color:var(--wine);">Google Hesabınızdaki Kanallar</div>
        <h2 style="font:25px 'DM Serif Display';margin:8px 0 16px;">Bağlamak İstediğiniz Kanalı Seçin</h2>
        
        <form method="post" action="{{ route('channels.store') }}" id="google-channel-select-form">
            @csrf
            <div id="channel-cards-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:14px;margin-bottom:20px;">
                @foreach($channels as $ch)
                    <label style="border:1px solid var(--line);border-radius:12px;padding:14px;display:flex;align-items:center;gap:12px;cursor:pointer;background:var(--cream);transition:border-color 0.15s,box-shadow 0.15s;">
                        <input type="radio" name="channel_id" value="{{ $ch->youtube_channel_id }}" @checked($loop->first) style="accent-color:var(--wine);width:18px;height:18px;">
                        @if($ch->thumbnail_url)
                            <img src="{{ $ch->thumbnail_url }}" alt="{{ $ch->title }}" style="width:48px;height:48px;border-radius:50%;object-fit:cover;border:1px solid var(--line);">
                        @else
                            <div style="width:48px;height:48px;border-radius:50%;background:var(--sand);display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--wine);">{{ strtoupper(mb_substr($ch->title, 0, 1)) }}</div>
                        @endif
                        <div style="overflow:hidden;">
                            <div style="font-weight:700;font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $ch->title }}</div>
                            @if($ch->custom_url)
                                <div style="font-size:12px;color:var(--muted);">{{ $ch->custom_url }}</div>
                            @endif
                        </div>
                    </label>
                @endforeach
            </div>
            <button class="btn" type="submit" style="padding:13px 24px;">Seçilen Kanalı Platforma Bağla</button>
        </form>
    </div>
</div>

<div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:24px;margin-top:25px;">
    <!-- 1. Seçenek: Google ile Otomatik Kanal Çekme -->
    <div class="card">
        <div class="eyebrow" style="color:var(--wine);">Önerilen Yöntem</div>
        <h2 style="font:25px 'DM Serif Display';margin:10px 0;">Google ile Kanalları Getir</h2>
        <p class="muted" style="margin-bottom:20px;line-height:1.6;">
            Google hesabınızda yetkilendirilmiş YouTube kanallarını otomatik olarak listeler ve tek tıkla seçmenizi sağlar.
        </p>

        <button id="btn-sync-google" class="btn" type="button" style="width:100%;padding:14px;background:#4285F4;display:flex;align-items:center;justify-content:center;gap:10px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#fff"/>
                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#fff" opacity="0.9"/>
            </svg>
            <span id="btn-sync-text">Google ile Kanallarımı Getir</span>
        </button>
    </div>

    <!-- 2. Seçenek: Kanal Linki / Kullanıcı Adı ile Hızlı Bağlama -->
    <div class="card">
        <div class="eyebrow">Hızlı & Bağımsız Kurulum</div>
        <h2 style="font:25px 'DM Serif Display';margin:10px 0;">Kanal Adı veya Linki</h2>
        <p class="muted" style="margin-bottom:20px;line-height:1.6;">
            Kanal kullanıcı adınızı (örn: <code>@aiajan</code>) veya YouTube kanal linkinizi girin. Sistem kanalınızı anında tanır.
        </p>

        <form method="post" action="{{ route('channels.store') }}">
            @csrf
            <div style="margin-bottom:16px;">
                <label for="channel_input" style="display:block;font-weight:600;font-size:13px;margin-bottom:6px;">YouTube Kanal Bağlantısı veya @kullanıcıadı</label>
                <input type="text" class="field" id="channel_input" name="channel_input" placeholder="@kanaladi veya https://youtube.com/@..." value="{{ old('channel_input') }}" required>
            </div>
            <button class="btn secondary" type="submit" style="width:100%;padding:14px;font-weight:700;">Kanalı Doğrula ve Bağla</button>
        </form>
    </div>
</div>

<!-- Firebase Google OAuth ile Kanal Çekme Skripti -->
<script type="module">
    import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.12.0/firebase-app.js';
    import { getAuth, signInWithPopup, GoogleAuthProvider } from 'https://www.gstatic.com/firebasejs/10.12.0/firebase-auth.js';

    const firebaseConfig = {
        apiKey: "{{ config('services.firebase.api_key', 'AIzaSyASQDjjn7zkLDjDmKkPwXAzMoK7fZdoFYA') }}",
        authDomain: "{{ config('services.firebase.project_id', 'aiajan-portfolio') }}.firebaseapp.com",
        projectId: "{{ config('services.firebase.project_id', 'aiajan-portfolio') }}"
    };

    const app = initializeApp(firebaseConfig);
    const auth = getAuth(app);
    const ytProvider = new GoogleAuthProvider();
    ytProvider.addScope('https://www.googleapis.com/auth/youtube.readonly');
    ytProvider.setCustomParameters({ prompt: 'consent select_account' });

    const syncBtn = document.getElementById('btn-sync-google');
    const syncText = document.getElementById('btn-sync-text');
    const alertBox = document.getElementById('channel-alert');
    const dynContainer = document.getElementById('dynamic-channels-container');
    const cardsGrid = document.getElementById('channel-cards-grid');

    function showAlert(msg, isError = true) {
        alertBox.style.display = 'block';
        alertBox.className = isError ? 'alert' : 'alert success';
        alertBox.textContent = msg;
    }

    syncBtn?.addEventListener('click', async () => {
        syncBtn.disabled = true;
        syncText.textContent = 'Google ile bağlanılıyor...';
        alertBox.style.display = 'none';

        try {
            const result = await signInWithPopup(auth, ytProvider);
            const credential = GoogleAuthProvider.credentialFromResult(result);
            const accessToken = credential ? credential.accessToken : '';

            if (!accessToken) {
                showAlert('Google erişim belirteci alınamadı.');
                syncBtn.disabled = false;
                syncText.textContent = 'Google ile Kanallarımı Getir';
                return;
            }

            syncText.textContent = 'Kanallar taranıyor...';

            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const resp = await fetch("{{ route('channels.sync_google') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ accessToken: accessToken })
            });

            const data = await resp.json();

            if (resp.ok && data.success && data.channels && data.channels.length > 0) {
                showAlert(data.message, false);
                renderChannels(data.channels);
                syncText.textContent = '✓ Kanallar Getirildi';
                dynContainer.style.display = 'block';
                dynContainer.scrollIntoView({ behavior: 'smooth' });
            } else {
                showAlert(data.message || 'Kanal bulunamadı. Lütfen kullanıcı adı (@kanaladi) ile bağlamayı deneyin.');
                syncBtn.disabled = false;
                syncText.textContent = 'Google ile Kanallarımı Getir';
            }
        } catch (err) {
            console.error(err);
            if (err.code !== 'auth/popup-closed-by-user') {
                showAlert('Google kanal erişimi alınamadı (' + (err.message || err.code) + '). Sağdaki "Kanal Adı veya Linki" kutusuna @kullanıcıadınızı yazarak da anında bağlayabilirsiniz.');
            }
            syncBtn.disabled = false;
            syncText.textContent = 'Google ile Kanallarımı Getir';
        }
    });

    function renderChannels(channels) {
        cardsGrid.innerHTML = '';
        channels.forEach((ch, idx) => {
            const label = document.createElement('label');
            label.style.cssText = 'border:1px solid var(--line);border-radius:12px;padding:14px;display:flex;align-items:center;gap:12px;cursor:pointer;background:var(--cream);';
            label.innerHTML = `
                <input type="radio" name="channel_id" value="${ch.youtube_channel_id}" ${idx === 0 ? 'checked' : ''} style="accent-color:var(--wine);width:18px;height:18px;">
                ${ch.thumbnail_url ? `<img src="${ch.thumbnail_url}" style="width:48px;height:48px;border-radius:50%;object-fit:cover;">` : `<div style="width:48px;height:48px;border-radius:50%;background:var(--sand);display:flex;align-items:center;justify-content:center;font-weight:700;">Y</div>`}
                <div style="overflow:hidden;">
                    <div style="font-weight:700;font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${ch.title}</div>
                    <div style="font-size:12px;color:var(--muted);">${ch.custom_url || ch.youtube_channel_id}</div>
                </div>
            `;
            cardsGrid.appendChild(label);
        });
    }
</script>
@endsection
