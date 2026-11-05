# YouTube Keşif — Laravel

YouTube içerik üreticileri için kanal keşfi ve platform içi topluluk uygulaması.

## Teknoloji
- Laravel 13
- PHP 8.3+
- MySQL 8+
- Laravel Socialite (Google OAuth 2.0)
- Google API PHP Client (YouTube Data API v3)
- Blade arayüzü

## Kurulum
Ayrıntılı Plesk ve Google OAuth kurulum adımları: docs/LARAVEL_SETUP.md

Temel komutlar:
~~~bash
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan config:cache
~~~

Plesk'te alan adının Document Root değeri Laravel projesindeki public/ klasörünü göstermelidir.

## Yapılan temel modüller
- Google OAuth giriş/çıkış
- OAuth tokenlarının Laravel Crypt ile şifreli saklanması
- Google hesabındaki erişilebilir YouTube kanallarını listeleme ve kanal seçimi
- Tek aktif kanal / 30 günlük kanal değiştirme kuralı
- Profil ekranı
- Normal ve destekçi kullanıcı keşfi
- Günlük toplam 6 platform takip sınırı (normal 3 + destekçi 3)
- Video keşfi (isteğe bağlı, standart YouTube embed kontrolleri)
- Admin özet ekranı
- YouTube kanal ve son 3 video senkronizasyon komutu
- Destekçi süresi bitirme komutu

## API bilgileri
Gerçek Google OAuth bilgileri henüz tanımlı değildir. .env.example içindeki BURAYA_YAZILACAK değerleri yalnızca yer tutucudur. Gerçek anahtarları GitHub'a yüklemeyin.

## Önemli
Video izleme veya YouTube etkileşimi platform avantajı, ödül ya da takip hakkı şartı değildir. YouTube politika incelemesi tamamlanmadan YouTube aboneliği oluşturma gibi yazma işlemleri eklenmeyecektir.
