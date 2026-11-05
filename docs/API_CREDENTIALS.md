# API ve erişim bilgileri

## Mevcut durum

**Henüz hiçbir API anahtarı veya OAuth istemci bilgisi alınmadı.** Bu depodaki tüm ilgili alanlar `BURAYA_YAZILACAK` yer tutucusuyla hazırlanmıştır. Bu ifade gerçek bir anahtar değildir ve API bağlantısı sağlamaz.

## Doldurulacak alanlar

| Değişken | Ne için? | Nereden alınacak? |
|---|---|---|
| `GOOGLE_CLIENT_ID` | Google ile giriş OAuth istemcisi | Google Cloud Console |
| `GOOGLE_CLIENT_SECRET` | OAuth istemci sırrı | Google Cloud Console |
| `YOUTUBE_API_KEY` | YouTube Data API v3 genel API erişimi | Google Cloud Console |
| `DB_DATABASE` | MySQL veritabanı adı | Hosting kontrol paneli |
| `DB_USERNAME` | MySQL kullanıcı adı | Hosting kontrol paneli |
| `DB_PASSWORD` | MySQL parolası | Hosting kontrol paneli |
| `APP_KEY (Laravel)` | OAuth tokenlarını sunucuda şifrelemek için anahtar | Laravel artisan key:generate ile sunucuda üretilecek |
| `CONTRIBUTION_PROVIDER` | Katkı/ödeme sağlayıcısı adı | Sağlayıcı seçilince |
| `CONTRIBUTION_API_KEY` | Katkı sağlayıcısı API anahtarı | Seçilen sağlayıcı |
| `CONTRIBUTION_API_SECRET` | Katkı sağlayıcısı gizli anahtarı | Seçilen sağlayıcı |
| `CONTRIBUTION_WEBHOOK_SECRET` | Webhook imza doğrulama sırrı | Seçilen sağlayıcı |

## Google Cloud hazırlığı

1. Google Cloud Console'da proje oluşturun veya uygun mevcut projeyi seçin.
2. YouTube Data API v3 hizmetini etkinleştirin.
3. OAuth consent screen / Google Auth Platform ayarlarını tamamlayın.
4. Web application türünde OAuth istemcisi oluşturun.
5. Yetkili yönlendirme URI'si olarak `https://youtube.aiajan.com.tr/auth/google/callback` ekleyin.
6. Üretilen Client ID ve Client Secret değerlerini sunucu ortam değişkenlerine kaydedin.
7. YouTube Data API için API key oluşturup uygun API kısıtlamalarını uygulayın.

OAuth kapsamları kullanım amacına göre en az yetki ilkesiyle seçilmelidir. Sadece kanal ve profil bilgisi okumak için gereken izinlerle, YouTube hesabında abonelik gibi değişiklik yapan izinler birbirinden ayrılmalıdır.

## Güvenlik kuralları

- Gerçek değerleri bu depodaki `.env.example` dosyasına yazmayın.
- `.env` dosyasını Git'e eklemeyin.
- API key için mümkün olan API ve uygulama kısıtlarını etkinleştirin.
- OAuth Client Secret, refresh token ve token şifreleme anahtarını tarayıcıya göndermeyin. Laravel uygulamasında bu anahtar APP_KEY değeridir.
- Bir anahtar yanlışlıkla GitHub'a gönderilirse yalnızca dosyadan silmek yeterli değildir; sağlayıcı panelinden iptal edip yenisini üretin.
- Ödeme sağlayıcısı seçilmeden ödeme API alanları doldurulmaz.

## Önemli

YouTube API key, kullanıcı adına OAuth yetkisi vermez. Kullanıcıya özel kanal bilgileri ve hesap üzerinde işlem gerektiren özellikler için uygun OAuth izni ve kullanıcı onayı gerekir.


## Laravel notu
OAuth tokenları Laravel Crypt ile şifrelenir. Şifreleme anahtarı .env içindeki APP_KEY değeridir. APP_KEY üretildikten sonra değiştirilmesi mevcut şifreli tokenları okunamaz hale getirebilir; yedekli ve güvenli saklayın.
