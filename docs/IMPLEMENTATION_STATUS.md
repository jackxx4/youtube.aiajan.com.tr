# Uygulama Durumu

## Laravel deposuna eklenenler
- Laravel 12 Composer yapılandırması ve uygulama başlangıç dosyaları
- Google OAuth giriş/callback/çıkış akışı
- YouTube Data API üzerinden OAuth ile erişilebilir kanal listesini alma
- Kanal seçimi ve 30 günlük değişim kuralı
- OAuth access/refresh tokenlarının Laravel Crypt ile şifrelenmesi
- Kullanıcı profili, platform takipleri ve normal/destekçi keşif ekranları
- Video keşif ekranı; standart YouTube embed kontrolleri
- Admin özet ekranı
- MySQL Laravel migration dosyaları
- Kanal/video senkronizasyonu ve destekçi süresi komutları
- Plesk kurulum rehberi

## Henüz tamamlanması / yapılandırılması gerekenler
- Google Cloud OAuth Client ID/Secret ve YouTube API projesi
- Plesk sunucusunda Composer install, APP_KEY, .env ve migration çalıştırılması
- Hosting üzerinde gerçek HTTP/OAuth testi
- Kullanıcı bloklarının öneri sorgularına tam entegrasyonu ve admin kullanıcı yönetimi
- Katkı/ödeme sağlayıcısı seçimi ve ödeme akışı
- Günlük öneri algoritmasının üretim verisiyle iyileştirilmesi
- Aylık metrik snapshot zamanlayıcısı
- Yetkilendirme, rate limit, gizlilik ve hesap silme akışlarının tamamlanması
- PHPUnit testleri, güvenlik taraması ve yük testleri
- Güncel YouTube API politika incelemesi

## Yayına alma durumu
GitHub kodu tek başına canlıya alınmış uygulama değildir. Plesk üzerinde bağımlılık kurulumu, çevre değişkenleri, veritabanı migration'ları, dosya izinleri ve Google OAuth bilgileri gereklidir. Bu işlemler hosting üzerinde henüz doğrulanmamıştır.
