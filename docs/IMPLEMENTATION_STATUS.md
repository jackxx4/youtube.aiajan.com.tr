# Uygulama Durumu

## Laravel deposuna eklenenler
- Laravel 13 Composer yapılandırması ve uygulama başlangıç dosyaları
- Google OAuth giriş/callback/çıkış akışı
- YouTube Data API üzerinden OAuth ile erişilebilir kanal listesini alma
- Kanal seçimi ve 30 günlük değişim kuralı
- OAuth access/refresh tokenlarının Laravel Crypt ile şifrelenmesi
- Kullanıcı profili, platform takipleri ve normal/destekçi keşif ekranları
- Video keşif ekranı; en fazla 5 video seçimi ve standart YouTube embed kontrolleri
- Engellenen hesapların keşif ve takip sorgularından çıkarılması
- Admin özet ekranı
- MySQL Laravel migration dosyaları
- Kanal/video senkronizasyonu ve destekçi süresi komutları
- Plesk kurulum rehberi

## Henüz tamamlanması / yapılandırılması gerekenler
- Google Cloud OAuth Client ID/Secret ve YouTube API projesi
- Plesk sunucusunda Composer install, APP_KEY, .env ve migration çalıştırılması
- Hosting üzerinde gerçek HTTP/OAuth testi
- Admin kullanıcı yönetimi ve rol atama arayüzü
- Katkı/ödeme sağlayıcısı seçimi ve ödeme akışı
- Günlük öneri algoritmasının üretim verisiyle iyileştirilmesi
- Aylık metrik snapshot zamanlayıcısı
- Yetkilendirme, rate limit, gizlilik ve hesap silme akışlarının tamamlanması
- Güvenlik taraması ve yük testleri
- Güncel YouTube API politika incelemesi

## Otomatik test durumu
- GitHub Actions üzerinde Composer doğrulaması başarılı.
- Laravel Blade şablon derlemesi (view:cache) başarılı.
- PHPUnit testleri başarılı.

## Yayına alma durumu
GitHub kodu tek başına canlıya alınmış uygulama değildir. Plesk üzerinde bağımlılık kurulumu, çevre değişkenleri, veritabanı migration'ları, dosya izinleri ve Google OAuth bilgileri gereklidir. Bu işlemler hosting üzerinde henüz doğrulanmamıştır.
