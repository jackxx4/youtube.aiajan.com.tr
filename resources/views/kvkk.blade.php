@extends('layouts.app')
@section('title', 'KVKK Aydınlatma Metni')
@section('content')
<div style="max-width:820px;margin:0 auto;">
    <p class="eyebrow">6698 Sayılı Kanun Kapsamında</p>
    <h1 class="h1">KVKK Aydınlatma Metni</h1>
    <p class="muted" style="margin-bottom:28px;">Son güncelleme: {{ date('d.m.Y') }} · Metin Sürümü: 1.0</p>

    <div class="card" style="line-height:1.8;padding:32px 28px;background:var(--cream);border:1px solid var(--line);border-radius:16px;">
        
        <p>
            Bu Aydınlatma Metni, 6698 sayılı <strong>Kişisel Verilerin Korunması Kanunu ("KVKK")</strong> Madde 10 kapsamında, veri sorumlusu sıfatıyla hareket eden <strong>aiajan.com.tr</strong> ve <strong>YouTube Keşif Platformu</strong> (bundan böyle <em>"Platform"</em> olarak anılacaktır) tarafından, platform kullanıcılarının kişisel verilerinin işlenme amaçları, hukuki sebepleri, aktarım şartları ve ilgili kişilerin hakları hususunda şeffaf bir şekilde bilgilendirilmesi amacıyla hazırlanmıştır.
        </p>

        <h2 style="font-size:1.2rem;font-weight:700;color:var(--wine);margin:24px 0 10px;">1. Veri Sorumlusunun Kimliği</h2>
        <p>
            KVKK uyarınca kişisel verileriniz; veri sorumlusu olarak <strong>YouTube Keşif / aiajan ekosistemi</strong> tarafından aşağıda açıklanan çerçevede toplanmakta, işlenmekte ve saklanmaktadır.
            <br><strong>Resmi İletişim E-Postası:</strong> <a href="mailto:jackalxxxx4@gmail.com" style="color:var(--rust);text-decoration:underline;">jackalxxxx4@gmail.com</a>
            <br><strong>Platform Web Adresi:</strong> <a href="https://youtube.aiajan.com.tr" target="_blank" style="color:var(--rust);text-decoration:underline;">youtube.aiajan.com.tr</a>
        </p>

        <h2 style="font-size:1.2rem;font-weight:700;color:var(--wine);margin:24px 0 10px;">2. İşlenen Kişisel Veri Kategorileri</h2>
        <p>Platformumuz tarafından işlenen kişisel veri grupları aşağıda sıralanmıştır:</p>
        <ul style="padding-left:20px;margin-bottom:18px;">
            <li><strong>Kimlik Bilgileri:</strong> Ad, soyad, Google hesap kimliği (Google Sub ID), kullanıcı adı (username).</li>
            <li><strong>İletişim Bilgileri:</strong> Google hesabı ile ilişkili e-posta adresi.</li>
            <li><strong>İçerik ve Kanal Bilgileri:</strong> Temsil edilen YouTube kanalı adı, kanal ID'si, özel bağlantısı, avatar/profil görseli ve vitrine eklenen YouTube video URL'leri ile başlıkları.</li>
            <li><strong>Kullanıcı İşlem Verileri:</strong> Platform içi takip kayıtları, vitrin tercihleri, oturum kayıtları.</li>
            <li><strong>İşlem Güvenliği Verileri:</strong> Kriptografik özetleme (hashing) ile korunan IP adresi özetleri (ip_hash), oturum çerezleri, sistem denetim kayıtları (audit log).</li>
        </ul>

        <h2 style="font-size:1.2rem;font-weight:700;color:var(--wine);margin:24px 0 10px;">3. Kişisel Verilerin İşlenme Amaçları</h2>
        <p>Kişisel verileriniz aşağıdaki amaçlarla sınırlı ve ölçülü olarak işlenmektedir:</p>
        <ul style="padding-left:20px;margin-bottom:18px;">
            <li>Platforma üyelik kaydının oluşturulması, tek tıkla güvenli Google OAuth kimlik doğrulaması sağlanması,</li>
            <li>Kullanıcının YouTube kanalının doğrulanması ve üretici profilinin oluşturulması,</li>
            <li>Bağımsız içerik üreticilerinin birbirlerini keşfedebilmesi ve video vitrinlerinin topluluğa sunulması,</li>
            <li>Platform güvenliğinin temini, kötü niyetli bot faaliyetlerinin ve yetkisiz erişimlerin engellenmesi,</li>
            <li>Kullanıcı talep, soru ve şikayetlerinin değerlendirilerek çözüme kavuşturulması.</li>
        </ul>

        <h2 style="font-size:1.2rem;font-weight:700;color:var(--wine);margin:24px 0 10px;">4. Kişisel Veri Toplamanın Hukuki Sebepleri</h2>
        <p>Kişisel verileriniz, KVKK'nın 5. maddesinde belirtilen aşağıdaki hukuki sebeplere dayanılarak otomatik veya kısmen otomatik yöntemlerle işlenmektedir:</p>
        <ul style="padding-left:20px;margin-bottom:18px;">
            <li><strong>KVKK m. 5/2-c:</strong> Bir sözleşmenin kurulması veya ifasıyla doğrudan doğruya ilgili olması (Platform kullanım koşullarının yerine getirilmesi).</li>
            <li><strong>KVKK m. 5/2-ç:</strong> Veri sorumlusunun hukuki yükümlülüğünü yerine getirebilmesi (Bilişim güvenliği ve yasal kayıt tutma zorunlulukları).</li>
            <li><strong>KVKK m. 5/2-f:</strong> İlgili kişinin temel hak ve özgürlüklerine zarar vermemek kaydıyla, veri sorumlusunun meşru menfaatleri için veri işlenmesinin zorunlu olması (Sistem güvenliği, bot engelleme, topluluk kalitesinin korunması).</li>
        </ul>

        <h2 style="font-size:1.2rem;font-weight:700;color:var(--wine);margin:24px 0 10px;">5. Kişisel Verilerin Aktarımı</h2>
        <p>
            Kişisel verileriniz, <strong>hiçbir suretle reklam şirketlerine, pazarlama ajanslarına veya ticari üçüncü taraflara satılmaz, kiralanmaz veya devredilmez.</strong>
            <br>Verileriniz yalnızca:
        </p>
        <ul style="padding-left:20px;margin-bottom:18px;">
            <li>Giriş ve yetkilendirme doğrulaması için Google OAuth altyapısına,</li>
            <li>Sunucu barındırma ve güvenli veritabanı altyapı hizmeti sağlayıcılarına (Plesk/Microsoft Windows Server altyapısı),</li>
            <li>Yetkili kamu kurum ve kuruluşlarının yasal talepleri doğrultusunda adli ve idari mercilere mevzuat gereği aktarılabilir.</li>
        </ul>

        <h2 style="font-size:1.2rem;font-weight:700;color:var(--wine);margin:24px 0 10px;">6. İlgili Kişi Olarak KVKK Madde 11 Kapsamındaki Haklarınız</h2>
        <p>KVKK'nın 11. maddesi uyarınca veri sahibi olarak aşağıdaki haklara sahipsiniz:</p>
        <ul style="padding-left:20px;margin-bottom:18px;">
            <li>Kişisel verilerinizin işlenip işlenmediğini öğrenme,</li>
            <li>Kişisel verileriniz işlenmişse buna ilişkin bilgi talep etme,</li>
            <li>Kişisel verilerin işlenme amacını ve bunların amacına uygun kullanılıp kullanılmadığını öğrenme,</li>
            <li>Kişisel verilerin eksik veya yanlış işlenmiş olması hâlinde bunların düzeltilmesini isteme,</li>
            <li>KVKK'nın 7. maddesinde öngörülen şartlar çerçevesinde kişisel verilerin silinmesini veya yok edilmesini isteme,</li>
            <li>İşlenen verilerin münhasıran otomatik sistemler vasıtasıyla analiz edilmesi suretiyle aleyhinize bir sonucun ortaya çıkmasına itiraz etme,</li>
            <li>Kişisel verilerin kanuna aykırı olarak işlenmesi sebebiyle zarara uğramanız hâlinde zararın giderilmesini talep etme.</li>
        </ul>

        <h2 style="font-size:1.2rem;font-weight:700;color:var(--wine);margin:24px 0 10px;">7. Veri Sahibi Başvuru Yöntemi</h2>
        <p>
            Yukarıda sıralanan haklarınıza ilişkin taleplerinizi, kimliğinizi teyit edici bilgilerle birlikte <a href="mailto:jackalxxxx4@gmail.com" style="color:var(--rust);text-decoration:underline;">jackalxxxx4@gmail.com</a> adresine e-posta göndererek veya doğrudan <a href="{{ route('contact') }}" style="color:var(--rust);text-decoration:underline;">İletişim Formumuz</a> üzerinden platform yönetimine iletebilirsiniz. Başvurularınız en geç 30 (otuz) gün içerisinde ücretsiz olarak sonuçlandırılacaktır.
        </p>

        <div style="margin-top:28px;padding-top:16px;border-top:1px solid var(--line);display:flex;gap:14px;flex-wrap:wrap;font-size:13px;">
            <a href="{{ route('privacy') }}" class="muted" style="text-decoration:underline;">Gizlilik Politikası</a>
            <span>·</span>
            <a href="{{ route('cookies') }}" class="muted" style="text-decoration:underline;">Çerez Politikası</a>
            <span>·</span>
            <a href="{{ route('contact') }}" class="muted" style="text-decoration:underline;">İletişim Formu</a>
        </div>
    </div>
</div>
@endsection

