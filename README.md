# YouTube Keşif

YouTube kanallarını ve videolarını keşfetmeye yönelik PHP tabanlı platform projesi.

> **Mevcut durum:** Proje dokümantasyonu ve başlangıç yapılandırması GitHub'a aktarılmıştır. Henüz Google/YouTube API anahtarı, OAuth istemci bilgisi veya ödeme sağlayıcısı API bilgisi alınmamıştır. Bu nedenle entegrasyonlar çalışır durumda değildir.

## GitHub deposu

https://github.com/jackxx4/youtube.aiajan.com.tr

## Teknoloji hedefi

- PHP 8.4
- MySQL 8+
- Google OAuth 2.0
- YouTube Data API v3
- HTML, CSS ve JavaScript
- Google API PHP Client

## API ve gizli bilgi alanları

Şu anda hiçbir gerçek API anahtarı veya OAuth bilgisi tanımlı değildir. İlgili alanlar `BURAYA_YAZILACAK` yer tutucusuyla hazırlanmıştır:

- Google OAuth Client ID
- Google OAuth Client Secret
- YouTube Data API v3 Key
- MySQL veritabanı bağlantı bilgileri
- Token şifreleme anahtarı
- İleride seçilecek katkı/ödeme sağlayıcısının API bilgileri

Alanların listesi için `.env.example` ve `docs/API_CREDENTIALS.md` dosyalarına bakın. `BURAYA_YAZILACAK` gerçek anahtar değildir; uygulamanın çalışması için ilgili sağlayıcıdan alınan gerçek değer gerekir.

**Güvenlik:** Gerçek API anahtarlarını, OAuth secret değerlerini, veritabanı şifrelerini veya tokenları GitHub'a yüklemeyin. Hosting ortam değişkenlerinde ya da Git tarafından izlenmeyen `config/app.php` dosyasında saklayın.

## Temel ürün kararları

- Bir platform hesabı bir YouTube kanalını temsil eder.
- Kullanıcı Google ile giriş yaptıktan sonra erişebildiği kanallardan birini seçer.
- Kanal değişimi 30 günde bir yapılabilir; eski kanal bağlantı geçmişi korunur.
- Platform takipleri YouTube aboneliklerinden ayrıdır.
- Günlük keşif: 10 normal kullanıcıdan 3, 10 destekçi kullanıcıdan 3 kişi seçimi.
- Destekçi statüsü 30 gün sürer ve yalnızca platform içi profil keşfinde öncelikli görünürlük sağlar.
- Destekçi statüsü video sıralamasını etkilemez.
- Her kanalın son 3 videosu video havuzuna alınır; günlük 10 video önerilir ve en fazla 5'i seçilebilir.
- Abone sayısı ve kanal toplam görüntülenmesi yalnızca yönetim panelinde aylık ölçüm olarak tutulur.

## Kurulum

1. PHP 8.4 ve MySQL 8+ barındıran hosting hazırlayın.
2. Depoyu hosting'e aktarın.
3. `composer install` çalıştırın.
4. `database/schema.sql` dosyasını veritabanında çalıştırın.
5. `config/app.example.php` dosyasını `config/app.php` olarak kopyalayın.
6. Google Cloud Console'dan gerekli OAuth ve YouTube API bilgilerini alın.
7. Gerçek değerleri güvenli sunucu ortam değişkenlerine girin.
8. OAuth yönlendirme adresini `https://youtube.aiajan.com.tr/callback.php` olarak yapılandırın.

## YouTube politika notu

YouTube görüntüleme, beğeni veya aboneliklerini ödüle/uygunluğa bağlayan ya da yapay etkileşim oluşturabilecek akışlar geliştirilmeden önce güncel YouTube API Services Developer Policies incelenmelidir. Video keşfi isteğe bağlı tutulmalı; standart oynatıcı kontrolleri korunmalıdır. Gömülü oynatıcıda ileri sarmayı her cihazda kesin engellemek garanti edilemez.

## Belgeler

- `docs/TECHNICAL_SPEC.md` — ayrıntılı teknik şartname
- `docs/API_CREDENTIALS.md` — API alanları ve edinme adımları
- `docs/IMPLEMENTATION_STATUS.md` — uygulama durumu
- `database/schema.sql` — başlangıç MySQL şeması
