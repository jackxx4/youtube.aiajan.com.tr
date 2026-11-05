# Uygulama Durumu

Bu depo, sohbet sırasında kararlaştırılan ürün gereksinimlerinden ve başlangıç teknik iskeletinden oluşturulmuştur. Hosting üzerinde çalışır durumda olduğu veya gerçek Google/YouTube entegrasyonunun tamamlandığı anlamına gelmez.

## Depoya aktarılanlar

- Ürün özeti ve kurulum rehberi
- Ayrıntılı teknik şartname
- MySQL başlangıç şeması
- Composer bağımlılık tanımı
- Gizli bilgileri dışarıda tutan örnek yapılandırma
- Git ignore kuralları

## Henüz uygulanması gerekenler

- Google OAuth giriş, callback, logout ve state/CSRF kontrolleri
- Token şifreleme, yenileme ve iptal akışı
- YouTube kanal listeleme ve kanal seçimi
- 30 günlük kanal değiştirme kuralı ve aktif kanal benzersizliği
- Kanal/video senkronizasyonu ve kota yönetimi
- Platform takipleri, engelleme ve günlük öneri motoru
- Destekçi katkı sağlayıcısı entegrasyonu ve 30 günlük statü
- Yönetici paneli ve aylık metrik toplama
- Arayüz sayfaları, erişilebilirlik ve mobil uyumluluk
- Testler, hata yönetimi, rate limiting, yedekleme ve canlıya geçiş kontrolleri

## Önemli teknik notlar

- SQL şeması başlangıç taslağıdır; üretim veritabanında çalıştırılmadan önce migration ve MySQL sürüm uyumluluğu test edilmelidir.
- YouTube API yanıtları ve gömülü oynatıcı olayları, bir insanın videoyu dikkatle izlediğinin kesin kanıtı değildir.
- YouTube politika incelemesi tamamlanmadan abonelik oluşturma veya izlemeyi görev şartına bağlayan mekanikler canlıya alınmamalıdır.
- Gerçek OAuth secret, refresh token, veritabanı parolası veya ödeme anahtarı bu depoya konulmamalıdır.
