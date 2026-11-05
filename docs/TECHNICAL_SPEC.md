# YouTube Keşif — Laravel teknik şartname

## Mimari
- Laravel 13, PHP 8.3+, MySQL 8+
- Blade tabanlı, mobil uyumlu sıcak/vintage arayüz
- Google OAuth: Laravel Socialite
- YouTube Data API: Google API PHP Client
- Sunucu tarafında OAuth token şifreleme: Laravel Crypt (APP_KEY)
- Zamanlanmış görevler: Laravel Scheduler

## Ürün kuralları
- Bir platform hesabı aynı anda bir YouTube kanalını temsil eder.
- Google girişinden sonra API'nin döndürdüğü erişilebilir kanallardan biri seçilir.
- Kanal değişikliği 30 günde bir yapılır; geçmiş bağlantılar tutulur.
- Platform takipleri YouTube aboneliklerinden ayrıdır.
- Günlük normal havuzdan 3, destekçi havuzundan 3 kullanıcı takip edilir; toplam sınır 6'dır.
- Normal ve destekçi kullanıcı havuzları ayrıdır.
- Destekçi statüsü 30 gün sürer ve yalnızca platform içi keşifte görünürlük fırsatı sağlar.
- Video keşfi isteğe bağlıdır; YouTube izlenmesi ödül, statü veya takip hakkı koşulu değildir.
- Profilde yalnızca kanal adı, platform kullanıcı adı ve kanal görseli gösterilir.
- Abone/görüntülenme metrikleri yalnızca admin alanı içindir.

## OAuth
- Callback: https://youtube.aiajan.com.tr/auth/google/callback
- İlk kapsamlar: openid, email, profile, https://www.googleapis.com/auth/youtube.readonly
- Gerçek Google credentials .env içinde saklanır; Git'e gönderilmez.
- YouTube aboneliği oluşturma gibi yazma işlemleri bu sürümde uygulanmamıştır.

## Uygulama ayrıntıları
- app/Http/Controllers: OAuth, kanal seçimi, dashboard, keşif, profil, admin
- app/Services/YouTubeChannelService.php: Google YouTube API istemcisi
- app/Console/Commands: kanal senkronizasyonu ve destekçi süresi
- database/migrations: Laravel veritabanı şeması
- resources/views: Blade ekranları
- docs/LARAVEL_SETUP.md: Plesk yayına alma

## Canlı öncesi
OAuth credentials, veritabanı, Plesk PHP uzantıları, document root, scheduler, güvenlik testleri ve YouTube politika kontrolü tamamlanmalıdır.
