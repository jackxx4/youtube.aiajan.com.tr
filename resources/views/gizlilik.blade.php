@extends('layouts.app')
@section('title','Gizlilik Politikası')
@section('content')
<p class="eyebrow">Yasal</p>
<h1 class="h1">Gizlilik Politikası</h1>
<p class="muted" style="margin-bottom:32px;">Son güncelleme: {{ date('d.m.Y') }}</p>

<div class="card" style="max-width:720px;line-height:1.75;">

<h2 style="font-size:1.1rem;font-weight:700;margin:0 0 8px;">1. Toplanan Veriler</h2>
<p>YouTube Keşif platformu (<strong>youtube.aiajan.com.tr</strong>) aşağıdaki verileri toplar:</p>
<ul style="margin:0 0 20px;padding-left:20px;">
    <li>Google hesabı temel bilgileri: ad, e-posta adresi, profil fotoğrafı</li>
    <li>YouTube kanalı bilgileri: kanal adı, kanal ID'si, abone sayısı, kanal kapak görseli</li>
    <li>Paylaşmayı seçtiğiniz video başlıkları, küçük resimler ve yayın tarihleri</li>
    <li>Platform içi etkileşimler: takip geçmişi, keşif tercihleri</li>
</ul>

<h2 style="font-size:1.1rem;font-weight:700;margin:0 0 8px;">2. Verilerin Kullanımı</h2>
<p>Toplanan veriler yalnızca şu amaçlarla kullanılır:</p>
<ul style="margin:0 0 20px;padding-left:20px;">
    <li>Platform hesabınızı oluşturmak ve yönetmek</li>
    <li>YouTube kanalınızı diğer içerik üreticileriyle eşleştirmek</li>
    <li>Keşif önerileri sunmak</li>
    <li>Platform güvenliğini sağlamak</li>
</ul>

<h2 style="font-size:1.1rem;font-weight:700;margin:0 0 8px;">3. YouTube API Hizmetleri</h2>
<p>Bu uygulama <strong>YouTube API Hizmetleri</strong>'ni kullanmaktadır. YouTube kanallarınıza erişim için Google OAuth 2.0 kullanılır. Yalnızca kanalınızı platforma bağlamak için gereken minimum izinler istenir (<code>youtube.readonly</code>).</p>
<p style="margin-bottom:20px;">YouTube API Hizmetleri Kullanım Koşulları: <a href="https://www.youtube.com/t/terms" target="_blank">youtube.com/t/terms</a><br>
Google Gizlilik Politikası: <a href="https://policies.google.com/privacy" target="_blank">policies.google.com/privacy</a></p>

<h2 style="font-size:1.1rem;font-weight:700;margin:0 0 8px;">4. Veri Paylaşımı</h2>
<p style="margin-bottom:20px;">Verileriniz hiçbir üçüncü tarafla ticari amaçla paylaşılmaz veya satılmaz. Veriler yalnızca platformun çalışması için gerekli altyapı hizmetlerinde (barındırma, veritabanı) işlenir.</p>

<h2 style="font-size:1.1rem;font-weight:700;margin:0 0 8px;">5. Veri Saklama ve Silme</h2>
<p style="margin-bottom:20px;">Hesabınızı silmek istediğinizde tüm kişisel verileriniz kalıcı olarak silinir. YouTube API erişim iznini iptal etmek için <a href="https://myaccount.google.com/permissions" target="_blank">Google Hesabım → Üçüncü Taraf Erişimi</a> sayfasını kullanabilirsiniz.</p>

<h2 style="font-size:1.1rem;font-weight:700;margin:0 0 8px;">6. Çerezler</h2>
<p style="margin-bottom:20px;">Platform, oturum yönetimi için yalnızca zorunlu oturum çerezi kullanır. Reklam veya takip amaçlı çerez kullanılmaz.</p>

<h2 style="font-size:1.1rem;font-weight:700;margin:0 0 8px;">7. İletişim</h2>
<p>Gizlilik ile ilgili sorularınız için: <a href="mailto:jackalxxxx4@gmail.com">jackalxxxx4@gmail.com</a></p>

</div>
@endsection
