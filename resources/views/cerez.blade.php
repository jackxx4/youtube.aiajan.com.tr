@extends('layouts.app')
@section('title', 'Çerez Politikası')
@section('content')
<div style="max-width:820px;margin:0 auto;">
    <p class="eyebrow">Yasal Bilgilendirme</p>
    <h1 class="h1">Çerez (Cookie) Politikası</h1>
    <p class="muted" style="margin-bottom:28px;">Son güncelleme: {{ date('d.m.Y') }} · Sürüm: 1.0</p>

    <div class="card" style="line-height:1.8;padding:32px 28px;background:var(--cream);border:1px solid var(--line);border-radius:16px;">
        
        <div style="margin-bottom:24px;padding:14px 18px;background:#f7efe5;border-left:4px solid var(--rust);border-radius:8px;font-size:14px;color:#5a3f33;">
            <strong>Özet:</strong> YouTube Keşif platformunda üçüncü taraf reklam hedefleme çerezleri veya pazarlama pikselleri <strong>kullanılmaz</strong>. Yalnızca oturum devamlılığı, form güvenliği ve YouTube gömülü video oynatıcıları için gerekli asgari teknik çerezler kullanılır.
        </div>

        <h2 style="font-size:1.2rem;font-weight:700;color:var(--wine);margin:24px 0 10px;">1. Çerez Nedir?</h2>
        <p>
            Çerezler (cookies), ziyaret ettiğiniz internet siteleri tarafından tarayıcınız aracılığıyla cihazınıza veya ağ sunucusuna depolanan küçük metin dosyalarıdır. Çerezlerin temel amacı, internet sitesinin düzgün çalışmasını sağlamak, oturum güvenliğini temin etmek ve kullanıcı deneyimini iyileştirmektir.
        </p>

        <h2 style="font-size:1.2rem;font-weight:700;color:var(--wine);margin:24px 0 10px;">2. Platformumuzda Kullanılan Çerez Türleri</h2>
        <p>YouTube Keşif (<strong>youtube.aiajan.com.tr</strong>) platformunda aşağıdaki sınırlı kategorilerde çerezler kullanılmaktadır:</p>
        
        <div style="margin:16px 0;display:grid;gap:14px;">
            <div style="padding:16px;background:#fff;border-radius:10px;border:1px solid var(--line);">
                <div style="font-weight:700;color:var(--wine);margin-bottom:4px;">🔒 Zorunlu ve Teknik Çerezler (Oturum Güvenliği)</div>
                <p style="margin:0;font-size:13.5px;color:var(--muted);">
                    Kullanıcının platformda oturum açabilmesi, hesap güvenliğinin korunması ve siteler arası istek sahteciliği (CSRF) saldırılarına karşı korunması için zorunludur.
                    <br><strong>Kullanılan örnek çerezler:</strong> <code>youtube_session</code> (Oturum yönetimi), <code>XSRF-TOKEN</code> (Form güvenliği).
                </p>
            </div>

            <div style="padding:16px;background:#fff;border-radius:10px;border:1px solid var(--line);">
                <div style="font-weight:700;color:var(--wine);margin-bottom:4px;">⚙️ Tercih ve İşlevsellik Çerezleri</div>
                <p style="margin:0;font-size:13.5px;color:var(--muted);">
                    Platformda yaptığınız çerez tercihleri ve arayüz ayarlarını hatırlamak amacıyla yerel depolama (Local Storage) veya kısa süreli çerezlerle saklanır.
                    <br><strong>Kullanılan anahtar:</strong> <code>cookie_consent_choice</code> (Çerez bilgilendirme tercihi).
                </p>
            </div>

            <div style="padding:16px;background:#fff;border-radius:10px;border:1px solid var(--line);">
                <div style="font-weight:700;color:var(--wine);margin-bottom:4px;">🎬 Harici Video Oynatıcı Çerezleri (YouTube No-Cookie)</div>
                <p style="margin:0;font-size:13.5px;color:var(--muted);">
                    Platformumuzda yer alan video keşif ve vitrin önizlemeleri, Google'ın gizlilik dostu <code>https://www.youtube-nocookie.com</code> alan adı üzerinden çalışır. Bu modda, siz videonun oynat butonuna basmadığınız sürece YouTube cihazınıza izleme veya kişiselleştirme çerezi kaydetmez.
                </p>
            </div>
        </div>

        <h2 style="font-size:1.2rem;font-weight:700;color:var(--wine);margin:24px 0 10px;">3. Çerezlerin Saklanma Süresi</h2>
        <p>
            Oturum çerezleri, web tarayıcınızı kapattığınız anda otomatik olarak silinir. İşlevsel tercihler (örneğin çerez onay durumu) ise tarayıcınızın yerel belleğinde en fazla 180 gün süreyle saklanır ve bu süre sonunda otomatik olarak yenilenir.
        </p>

        <h2 style="font-size:1.2rem;font-weight:700;color:var(--wine);margin:24px 0 10px;">4. Çerezleri Nasıl Kontrol Edebilir veya Silebilirsiniz?</h2>
        <p>
            İnternet tarayıcınızın ayarlarını değiştirerek çerez tercihlerinizi dilediğiniz zaman kişiselleştirebilirsiniz. Zorunlu oturum çerezlerini engellemeniz durumunda platforma giriş yapamayabilir veya bazı özelliklerden faydalanamayabilirsiniz.
        </p>
        <ul style="padding-left:20px;margin-bottom:20px;">
            <li><strong>Google Chrome:</strong> Ayarlar → Gizlilik ve Güvenlik → Üçüncü taraf çerezleri</li>
            <li><strong>Apple Safari:</strong> Tercihler → Gizlilik → Çerezleri ve web sitesi verilerini yönet</li>
            <li><strong>Mozilla Firefox:</strong> Seçenekler → Gizlilik ve Güvenlik → Çerezler ve Site Verileri</li>
            <li><strong>Microsoft Edge:</strong> Ayarlar → Çerezler ve site izinleri</li>
        </ul>

        <h2 style="font-size:1.2rem;font-weight:700;color:var(--wine);margin:24px 0 10px;">5. İletişim ve Haklar</h2>
        <p>
            Çerez politikamız veya kişisel verilerinizin işlenmesi hakkındaki soru ve taleplerinizi <a href="{{ route('contact') }}" style="color:var(--rust);text-decoration:underline;">İletişim Sayfamız</a> üzerinden veya doğrudan <a href="mailto:jackalxxxx4@gmail.com" style="color:var(--rust);text-decoration:underline;">jackalxxxx4@gmail.com</a> adresine iletebilirsiniz.
        </p>

        <div style="margin-top:28px;padding-top:16px;border-top:1px solid var(--line);display:flex;gap:14px;flex-wrap:wrap;font-size:13px;">
            <a href="{{ route('privacy') }}" class="muted" style="text-decoration:underline;">Gizlilik Politikası</a>
            <span>·</span>
            <a href="{{ route('kvkk') }}" class="muted" style="text-decoration:underline;">KVKK Aydınlatma Metni</a>
            <span>·</span>
            <a href="{{ route('contact') }}" class="muted" style="text-decoration:underline;">Bize Ulaşın</a>
        </div>
    </div>
</div>
@endsection

