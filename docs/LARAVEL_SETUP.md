# Laravel kurulum notları

## Hedef mimari
- Laravel 13, PHP 8.3+, MySQL 8+
- Google OAuth 2.0 (Laravel Socialite)
- YouTube Data API v3 (Google API PHP Client)
- Blade arayüzü; sade, mobil uyumlu, sıcak vintage tasarım
- Uygulama web kökü yalnızca `public/` dizini olmalıdır.

## Hosting kurulumu
1. Plesk'te `youtube.aiajan.com.tr` için PHP 8.3 veya uyumlu daha yeni sürümü seçin.
2. PHP eklentilerini etkinleştirin: `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `mbstring`, `openssl`, `pcre`, `PDO`, `pdo_mysql`, `session`, `tokenizer`, `xml`.
3. GitHub deposunu hosting'e alın. Composer bağımlılıklarını kurun: `composer install --no-dev --optimize-autoloader`.
4. `.env.example` dosyasını `.env` olarak kopyalayın ve gerçek hosting / Google değerlerini girin.
5. `php artisan key:generate` çalıştırın.
6. Plesk Document Root / Document Path değerini Laravel projesinin `public` klasörüne yönlendirin.
7. `php artisan migrate --force` çalıştırın.
8. `php artisan config:cache && php artisan route:cache && php artisan view:cache` çalıştırın.
9. Laravel `storage` ve `bootstrap/cache` klasörlerinin PHP tarafından yazılabilir olduğundan emin olun.
10. Plesk Scheduled Tasks içinde Laravel scheduler için dakikada bir `php /tam/yol/artisan schedule:run` görevi ekleyin.

## Google Cloud
- Authorized JavaScript origin: `https://youtube.aiajan.com.tr`
- Authorized redirect URI: `https://youtube.aiajan.com.tr/auth/google/callback`
- Google OAuth client ID/secret `.env` içine girilir.
- YouTube Data API v3 etkin olmalıdır.
- İlk OAuth kapsamı: `openid email profile https://www.googleapis.com/auth/youtube.readonly`.
- API anahtarı `YOUTUBE_API_KEY` alanında tutulur; kullanıcı OAuth tokenı gereken işlemlerin yerine geçmez.

## Mevcut uygulama kapsamı
- Google OAuth giriş ve callback
- Kullanıcının erişebildiği YouTube kanallarını listeleme ve kanal bağlama
- Aynı anda tek aktif kanal ve 30 günlük değiştirme aralığı
- Şifreli OAuth token saklama
- Profil, platform takipleri, normal/destekçi keşif havuzları
- Video keşif ekranı; standart YouTube embed kontrolleri
- Admin dashboard temel metrik ekranı
- Son 3 video / kanal istatistikleri için `youtube:sync-channels` komutu
- Destekçi süresi için `supporters:expire` komutu

## Henüz canlıya hazır olmayanlar
- Ödeme/katkı sağlayıcısı entegrasyonu (sağlayıcı seçilmedi)
- Kullanıcıların kendi katkı ödemesini başlatacağı akış
- Tamamlanmış admin CRUD ve rol atama ekranı
- YouTube Analytics API ile yetkilendirilmiş kaynak analizi
- Video öneri havuzunun gelişmiş dengeleme algoritması
- Üretim güvenlik / gizlilik / yük testleri ve YouTube politika incelemesi

YouTube etkileşimi ödül veya avantaj şartına bağlanmaz. Video keşfi isteğe bağlıdır; oynatıcının standart kontrolleri korunur.

## İlk yönetici hesabı
İlk Google OAuth girişi ve kanal bağlantısı tamamlandıktan sonra, phpMyAdmin üzerinden yalnızca kendi hesabına yönetici rolü ver:

~~~sql
UPDATE users
SET role = 'admin'
WHERE email = 'GOOGLE_HESABININ_EPOSTA_ADRESI';
~~~

E-posta adresini kendi Google hesabınla değiştir. Bu sorguyu yalnızca güvenilir yönetici hesabı için çalıştır.

## Scheduler
Plesk Scheduled Tasks alanında dakikada bir şu komutu çalıştır:
~~~bash
php /TAM/YOL/youtube.aiajan.com.tr/artisan schedule:run
~~~

Kanal senkronizasyonu altı saatte bir; süresi dolan destekçi kayıtlarının kapatılması her gün çalışır.
