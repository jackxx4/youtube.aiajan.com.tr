# YouTube Keşif — Teknik Şartname

## 1. Amaç ve kapsam

YouTube kanallarının ve videolarının platform içinde keşfedilmesini sağlayan; kullanıcıların birbirlerini platform üzerinden takip edebildiği bir web uygulaması.

Bu doküman ürün kararlarını ve hedef mimariyi tanımlar. Henüz uygulanmamış özellikler tamamlanmış gibi değerlendirilmemelidir.

## 2. Mimari

- Sunucu: PHP 8.4
- Veritabanı: MySQL 8+
- Kimlik doğrulama: Google OAuth 2.0
- YouTube veri kaynağı: YouTube Data API v3
- İstemci: HTML/CSS/JavaScript
- Harici paket: google/apiclient
- Barındırma: HTTPS destekli hosting; hedef alan adı youtube.aiajan.com.tr

API çağrıları sunucu tarafından yapılır. Google istemci sırrı ve refresh token tarayıcıya gönderilmez. YouTube verileri uygun önbellek politikasıyla saklanır; her sayfa açılışında gereksiz API çağrısı yapılmaz.

## 3. Hesap ve kanal bağlantısı

1. Kullanıcı Google ile giriş yapar.
2. OAuth kimliği doğrulanır.
3. Kullanıcının erişebildiği YouTube kanalları API üzerinden listelenir.
4. Kullanıcı temsil edeceği kanalı seçer.
5. Bir platform hesabına aynı anda yalnızca bir aktif kanal bağlanır.
6. Aynı YouTube kanalının iki farklı platform hesabına aktif bağlanması engellenir.
7. Kanal değişimi en erken 30 gün sonra yapılabilir.
8. Eski kanal bağlantı geçmişi silinmez; yeni kanalın profil ve video verileri güncellenir.

Google hesabında birden fazla kanal veya Brand Account bulunması durumunda API'nin gerçekten döndürdüğü kanallar gösterilir. Erişilemeyen kanallar varmış gibi varsayılmaz.

## 4. Platform takip sistemi

Platform takibi, YouTube aboneliğinden tamamen ayrıdır.

- Takip tek yönlüdür ve anında gerçekleşir.
- Kullanıcıların birbirlerini YouTube'da takip etmesi/abone olması gerekmez.
- Takip ilişkileri veritabanında saklanır.
- Kullanıcı arayüzünde takipçi ve takip edilen listelerinin gösterilmesi zorunlu değildir.
- Daha önce takip edilen hesaplar yeni önerilerden çıkarılır.
- Engelleme ilişkileri varsa karşılıklı öneri ve etkileşimde dikkate alınır.

## 5. Günlük keşif görevleri

### 5.1 Normal kullanıcı keşfi

- Sistem 10 normal kullanıcı önerir.
- Kullanıcı bu 10 kişi arasından 3 kişiyi seçebilir.
- Daha önce takip edilen hesaplar önerilmez.
- Aynı öneri grubunda tekrar bulunmaz.
- Yakın zamanda gösterilen profiller mümkün olduğunca tekrar gösterilmez.

### 5.2 Destekçi kullanıcı keşfi

- Sistem 10 destekçi kullanıcı önerir.
- Kullanıcı bu 10 kişi arasından 3 kişiyi seçebilir.
- Destekçi havuzu normal kullanıcı havuzundan ayrıdır.
- Destekçi statüsü 30 gün sürer.
- Destekçilik yalnızca platform içi profil keşfinde öncelikli görünürlük sağlar; takip, izlenme veya abone garantisi vermez.
- Destekçi statüsü video sıralamasını etkilemez.

### 5.3 Video keşfi

- Her bağlı kanalın son 3 videosu video havuzuna alınır.
- Günlük öneri listesinde 10 video bulunur.
- Kullanıcı en fazla 5 video seçebilir.
- Havuzdaki silinmiş, gizli veya erişilemeyen videolar uygun kontrollerle çıkarılır.
- Video seçimi ve izleme, YouTube etkileşimi satın alma/ödüllendirme mekanizmasına dönüştürülmemelidir.
- Video izleme bir ödül, statü, takip hakkı veya başka avantajın koşulu yapılmamalıdır.
- YouTube oynatıcısının standart kontrolleri korunmalıdır. İleri sarmayı her cihaz ve oynatıcı sürümünde kesin engellemek mümkün olduğu varsayılmamalıdır.

## 6. Destekçi katkı modeli

- Katkı gönüllüdür.
- Destekçi statüsü 30 gün geçerlidir.
- Katkı karşılığında YouTube abonesi, görüntülenme, beğeni veya başka YouTube etkileşimi vaat edilmez.
- Katkı, daha fazla video ekleme veya daha fazla kişi takip etme kapasitesi sağlamaz.
- Avantaj platform içi profil keşfinde görünürlük fırsatıdır; sonuç garantisi değildir.
- Katkı sağlayıcısı ve ödeme akışı proje için ayrıca yapılandırılmalıdır.

## 7. Profil arayüzü

Kullanıcı kendi profil alanında yalnızca şu bilgileri görür:
- YouTube kanal adı
- Platform kullanıcı adı
- YouTube kanal profil görseli

Abone sayısı ve kanal toplam görüntülenmesi kullanıcı arayüzünde gösterilmez. Takipçi/takip edilen listeleri de zorunlu profil bileşeni değildir.

## 8. Yönetim paneli ve metrikler

Yalnızca yönetici tarafından görülebilecek kanal ölçümleri:
- Abone sayısı
- Kanal toplam görüntülenmesi
- Ölçüm ayı ve veri çekim zamanı

Ölçümler aylık snapshot olarak tutulur. YouTube abone sayısı yuvarlanmış veya gizlenmiş olabilir; kesin sayı garantisi verilmez. Bu metrikler platform sıralamasında kullanılmaz.

Kanalın platformdan kaynaklanan büyümesini kanıtlamak için yalnızca toplam abone/görüntülenme değişimine bakmak yeterli değildir. Trafik kaynağı analizi gibi bilgiler için ilgili kanal sahibinin ayrı yetkilendirmesi ve uygun Analytics API erişimi gerekir.

## 9. Veri modeli

Ana tablolar:
- users
- youtube_channels
- user_channel_links
- platform_follows
- user_blocks
- supporter_status
- contributions
- videos
- video_impressions
- user_impressions
- youtube_oauth_tokens
- youtube_subscription_actions
- youtube_metrics_monthly
- audit_logs

Ayrıntılı başlangıç şeması `database/schema.sql` dosyasındadır. Şema, uygulama geliştikçe migration yaklaşımıyla sürümlenmelidir.

## 10. Güvenlik gereksinimleri

- OAuth state doğrulaması
- CSRF tokenları
- PDO prepared statements
- HTML çıktısında bağlama uygun escaping
- Güvenli oturum çerezleri
- Sunucu tarafında token şifreleme
- Token yenileme ve iptal akışı
- Rol tabanlı yönetici yetkilendirmesi
- Hız sınırlama ve kötüye kullanım kontrolleri
- Audit log
- Hesap silme ve veri saklama politikası
- Gizli bilgilerin Git dışında tutulması

## 11. Google OAuth ve YouTube API

Başlangıçta yalnızca kanal kimliği ve herkese açık kanal/video bilgilerini okumak için gerekli en dar OAuth kapsamları kullanılmalıdır. Kanal sahibinin hesabını yönetme veya abonelik oluşturma gibi yazma işlemleri ayrı izin ve politika incelemesi gerektirir.

YouTube aboneliği oluşturma özelliği ancak:
1. Kullanıcı gerekli OAuth kapsamını açıkça verdiyse,
2. Kullanıcı her işlem için açıkça talimat veriyorsa,
3. Güncel YouTube API politikaları kullanım senaryosuna izin veriyorsa,
4. API yanıtı başarıyla doğrulanıyorsa
değerlendirilmelidir.

Bu özellik platform takip sistemiyle aynı tabloya yazılmamalıdır.

## 12. Zamanlanmış işler

- Kanal profil verilerinin yenilenmesi
- Kanal başına son 3 videonun güncellenmesi
- Erişilemeyen videoların kontrol edilmesi
- Aylık kanal metrik snapshot'larının alınması
- Süresi dolan destekçi statülerinin kapatılması
- Öneri gösterim geçmişinin temizleme/retention politikası

Cron sıklıkları YouTube API kota limitleri ve gerçek trafik ölçümlerine göre belirlenmelidir.

## 13. Uygulama aşamaları

1. Hosting, HTTPS, veritabanı ve güvenli yapılandırma
2. Google OAuth giriş/çıkış
3. Kanal listeleme ve kanal seçimi
4. Profil ve kanal verisi önbelleği
5. Platform takipleri ve engelleme
6. Normal/destekçi keşif havuzları
7. Video keşif havuzu
8. Destekçi katkı ve süre yönetimi
9. Yönetim paneli ve aylık metrikler
10. Güvenlik, politika, kota ve yük testleri

## 14. Canlıya geçiş ön koşulları

- YouTube API politika değerlendirmesi
- OAuth izinlerinin ve Google doğrulama gereksinimlerinin kontrolü
- Gizlilik politikası ve kullanım koşulları
- Hesap silme ve token iptali
- CSRF ve yetkilendirme testleri
- API kota ve hata senaryoları
- Yedekleme ve geri yükleme testi
- Gerçek kullanıcı verisiyle kontrollü pilot
