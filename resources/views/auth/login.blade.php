@extends('layouts.app')
@section('title', 'Giriş')
@section('content')
<div style="max-width:520px;margin:50px auto;text-align:center" class="card">
    <div class="eyebrow"><a href="https://aiajan.com.tr" target="_blank" style="color:inherit;text-decoration:none;">aiajan.com.tr</a> · Hoş geldin</div>
    <h1 class="h1">Hesabına giriş yap</h1>
    <p class="lead" style="margin:0 auto 24px">
        Google hesabınla tek tıkla devam et. Bağlı YouTube kanallarını otomatik tanır ve platformda güvenle temsil eder.
    </p>

    <div id="login-alert" class="alert" style="display:none;margin-bottom:18px;"></div>

    <button id="google-login-btn" class="btn" style="width:100%;padding:14px;font-size:16px;display:flex;align-items:center;justify-content:center;gap:12px;" type="button">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z" fill="#FBBC05"/>
            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z" fill="#EA4335"/>
        </svg>
        <span id="google-login-text">Google ile devam et</span>
    </button>

    <div style="margin-top:14px;">
        <button id="redirect-login-link" type="button" style="background:transparent;border:none;color:var(--muted);font-size:13px;text-decoration:underline;cursor:pointer;">
            Pencere açılmıyorsa aynı sekmede devam et
        </button>
    </div>

    <div style="margin-top:14px;">
        <span class="muted">aiajan ekosistemi güvencesiyle tek tıkla bağlan</span>
    </div>

    <!-- Giriş Kolaylığı Rehberi -->
    <div style="margin-top:18px;padding:12px 14px;background:#f5ede3;border-radius:10px;border:1px solid #e3d3c2;font-size:12px;color:#6b5247;text-align:left;line-height:1.55;">
        <div style="font-weight:700;display:flex;align-items:center;gap:6px;margin-bottom:4px;color:var(--wine);">
            <span>⚡</span> <span>Hızlı ve Güvenli Giriş:</span>
        </div>
        Google hesabınızla tek tıkla oturum açın. Giriş sonrasında YouTube kanalınızı (kullanıcı adı veya bağlantı ile) profilinize kolayca bağlayabilirsiniz.
    </div>

    <p class="foot">
        Google OAuth izinleri açıkça gösterilir. Şifre bilgilerin platformda tutulmaz.
    </p>
</div>

<!-- Firebase Google Auth Entegrasyonu (Akıllı Hibrit: Pop-up + Otomatik Redirect) -->
<script type="module">
    import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.12.0/firebase-app.js';
    import { getAuth, signInWithPopup, signInWithRedirect, getRedirectResult, GoogleAuthProvider } from 'https://www.gstatic.com/firebasejs/10.12.0/firebase-auth.js';

    const firebaseConfig = {
        apiKey: "{{ config('services.firebase.api_key', 'AIzaSyASQDjjn7zkLDjDmKkPwXAzMoK7fZdoFYA') }}",
        authDomain: "{{ config('services.firebase.project_id', 'aiajan-portfolio') }}.firebaseapp.com",
        projectId: "{{ config('services.firebase.project_id', 'aiajan-portfolio') }}",
        storageBucket: "{{ config('services.firebase.project_id', 'aiajan-portfolio') }}.firebasestorage.app"
    };

    const app = initializeApp(firebaseConfig);
    const auth = getAuth(app);
    const provider = new GoogleAuthProvider();
    provider.addScope('openid');
    provider.addScope('email');
    provider.addScope('profile');
    provider.setCustomParameters({ prompt: 'select_account' });

    const btn = document.getElementById('google-login-btn');
    const btnText = document.getElementById('google-login-text');
    const redirectLink = document.getElementById('redirect-login-link');
    const alertBox = document.getElementById('login-alert');

    function showAlert(msg, isError = true) {
        alertBox.style.display = 'block';
        alertBox.className = isError ? 'alert' : 'alert success';
        alertBox.textContent = msg;
    }

    async function processAuthResult(res) {
        btnText.textContent = 'Oturum doğrulanıyor...';
        const user = res.user;
        const idToken = await user.getIdToken();
        const credential = GoogleAuthProvider.credentialFromResult(res);
        const accessToken = credential ? credential.accessToken : '';

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const resp = await fetch("{{ route('auth.firebase.callback') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                idToken: idToken,
                accessToken: accessToken
            })
        });

        const data = await resp.json();
        if (resp.ok && data.success) {
            btnText.textContent = 'Giriş başarılı! Yönlendiriliyorsunuz...';
            window.location.href = data.redirect || "{{ route('dashboard') }}";
        } else {
            showAlert(data.message || 'Giriş doğrulanamadı.');
            btn.disabled = false;
            btnText.textContent = 'Google ile devam et';
        }
    }

    // 1. Redirect dönüş kontrolü (Aynı sekmede oturum açılmışsa yakala)
    try {
        const redirectRes = await getRedirectResult(auth);
        if (redirectRes && redirectRes.user) {
            btn.disabled = true;
            await processAuthResult(redirectRes);
        }
    } catch (e) {
        console.error(e);
        showAlert('Yönlendirme ile giriş tamamlanamadı: ' + (e.message || e.code));
    }

    // 2. Pop-up ile giriş (Masaüstü için varsayılan)
    async function handleGooglePopup() {
        if (!btn) return;
        btn.disabled = true;
        btnText.textContent = 'Google ile bağlanılıyor...';
        alertBox.style.display = 'none';

        try {
            const res = await signInWithPopup(auth, provider);
            await processAuthResult(res);
        } catch (e) {
            console.error(e);
            if (e.code === 'auth/popup-blocked') {
                showAlert('Açılır pencere tarayıcınız tarafından engellendi. Aynı sekmede yönlendiriliyorsunuz...');
                setTimeout(() => { signInWithRedirect(auth, provider); }, 800);
            } else if (e.code === 'auth/unauthorized-domain') {
                showAlert('Firebase yetkilendirme hatası: youtube.aiajan.com.tr alan adı Firebase Console > Authentication > Settings > Authorized Domains listesine eklenmelidir.');
                btn.disabled = false;
                btnText.textContent = 'Google ile devam et';
            } else if (e.code !== 'auth/popup-closed-by-user') {
                showAlert('Giriş başarısız oldu: ' + (e.message || e.code));
                btn.disabled = false;
                btnText.textContent = 'Google ile devam et';
            } else {
                btn.disabled = false;
                btnText.textContent = 'Google ile devam et';
            }
        }
    }

    // 3. Aynı sekmede yönlendirme (İsteğe bağlı veya mobil için alternatif)
    function handleGoogleRedirect() {
        if (!btn) return;
        btn.disabled = true;
        btnText.textContent = 'Google sayfasına yönlendiriliyorsunuz...';
        signInWithRedirect(auth, provider);
    }

    btn?.addEventListener('click', handleGooglePopup);
    redirectLink?.addEventListener('click', handleGoogleRedirect);

    // Otomatik tetikleme parametresi
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('auto') === 'google') {
        setTimeout(handleGooglePopup, 400);
    }
</script>
@endsection
