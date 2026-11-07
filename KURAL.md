# YouTube Keşif — Proje Kuralları

> Bu dosya projenin değişmez mimari kararlarını, güvenlik kurallarını ve geliştirme
> standartlarını tanımlar. Her geliştirici ve AI asistan bu kurallara uymak zorundadır.

---

## 1. Güvenlik — Kesinlikle Uyulacak Kurallar

### 1.1 Sırlar (Secrets)
- `DEPLOY_SECRET`, `FIREBASE_API_KEY`, `DB_PASSWORD`, `APP_KEY`, FTP bilgileri **asla** kaynak
  koduna yazılamaz; yalnızca `.env` veya `.env.deploy` dosyalarında bulunur.
- `config()` ve `env()` çağrılarında hardcoded fallback değer **yasaktır**:
  ```php
  // YANLIŞ
  env('FIREBASE_API_KEY', 'AIzaSy...')
  // DOĞRU
  env('FIREBASE_API_KEY')
  ```
- `.env` ve `.env.deploy` dosyaları git'e commit edilmez (`.gitignore`'da olmalı).

### 1.2 Deploy Endpoint (`/_deploy/artisan`)
- Yalnızca **POST** metoduyla erişilebilir; GET yasaktır.
- Token **request body**'den (`input()`) okunur, query string'den (`query()`) değil.
- `DEPLOY_SECRET` boş olduğunda endpoint **her zaman 403** döndürür (`$secret !== ''` kontrolü zorunlu).
- Endpoint CSRF korumasından muaftır (`bootstrap/app.php` → `validateCsrfTokens(except: ['_deploy/artisan'])`).

### 1.3 Mass Assignment
- `User::$fillable` içinde `role` ve `status` **asla** yer almaz (privilege escalation riski).
- Yeni model eklenirken `$fillable` yalnızca kullanıcının değiştirebileceği alanları içermelidir.

### 1.4 HTTP İstekleri (cURL)
- `CURLOPT_SSL_VERIFYPEER` her zaman `true` olmalıdır.
- Python `deploy.py`'da `ssl.CERT_NONE` / `ctx.check_hostname = False` kullanılmaz.

### 1.5 Firebase
- Firebase API key Blade şablonlarına hardcoded fallback olarak **yazılamaz**:
  ```blade
  {{-- YANLIŞ --}}
  apiKey: "{{ config('services.firebase.api_key', 'AIzaSy...') }}"
  {{-- DOĞRU --}}
  apiKey: "{{ config('services.firebase.api_key') }}"
  ```

---

## 2. Mimari Kararlar

### 2.1 Veritabanı
- **Üretim:** MS SQL Server (`sqlsrv`) — Plesk Windows IIS.
- **Yerel geliştirme:** MySQL veya SQLite.
- Migration dosyaları production'da `php artisan migrate --force` ile çalıştırılır.
- Yeni migration oluşturulduğunda deploy endpoint üzerinden tetiklenmelidir:
  ```bash
  curl -X POST https://youtube.aiajan.com.tr/_deploy/artisan \
    -d "token=<DEPLOY_SECRET>&cmd=migrate"
  ```

### 2.2 Video Vitrin Sistemi
- Her kullanıcı **en fazla 3** video seçebilir (`user_video_selections` tablosu).
- Video seçimi kullanıcının kendi kanalına ait olmalıdır (sahiplik doğrulaması zorunlu).
- Discovery feed (`/kesfet/videolar`) yalnızca başka kullanıcıların seçtiği videoları gösterir.
- `addVideoByUrl()` → oEmbed `author_name` ile kanal başlığı karşılaştırılarak sahiplik doğrulanır.

### 2.3 Kanal Değişimi
- Kanal değişimi her zaman açıktır (`$canChangeChannel = true`).
- 30 gün kısıtlaması kaldırıldı.

### 2.4 Session ve DB Transaction Sıralaması
- `session()` yazma işlemleri DB transaction **dışında** yapılmalıdır:
  ```php
  // DOĞRU
  DB::transaction(function() { /* sadece DB işlemleri */ });
  session(['key' => 'value']); // transaction sonrası
  ```

### 2.5 Kullanıcı Ban Kontrolü
- Kullanıcı `status !== 'active'` ise giriş/güncelleme işlemi transaction içinde **erken döner**
  (veri güncellenmez).

---

## 3. Deploy Sistemi

### 3.1 Akış
```
Yerel geliştirme → git push origin main
                       ↓
              deploy.sh çalıştır (FTP upload + migration)
                       ↓
              git pull --rebase + git push
```

### 3.2 deploy.sh Kuralları
- `git add -A` **yasaktır** — yalnızca uygulama klasörleri eklenir:
  ```bash
  git add app/ config/ database/ resources/ routes/ bootstrap/ public/
  ```
- Deploy öncesi `git pull --rebase origin main` çalıştırılır (uzak commit'ler korunur).
- `.env.deploy` deploy araçlarına özel sırları içerir ve git'e commit edilmez.

### 3.3 Artisan Çağrısı (deploy.py)
- `trigger_remote_artisan()` **POST** metoduyla çağrılır, GET değil.
- Tüm sırlar (`DEPLOY_SECRET`, `DB_PASSWORD`, `APP_KEY`, `FIREBASE_API_KEY`) `.env.deploy`'dan okunur.

---

## 4. Kod Standartları

### 4.1 Marka Yazımı
- Platform adı her yerde küçük harf: **`aiajan`**, **`aiajan.com.tr`** — `AIAjan` veya `AIAJAN` yasaktır.

### 4.2 PHP Versiyon Uyumluluğu
- Yerel: PHP 8.5 (Homebrew), Production: PHP 8.3 (Plesk).
- `PDO::MYSQL_ATTR_SSL_CA` PHP 8.5'te deprecated → `Pdo\Mysql::ATTR_SSL_CA` kullan.

### 4.3 IDE Uyarıları
- Blade şablonlarındaki `{{ }}` syntax'ı VS Code CSS parser'ı tarafından hata olarak işaretlenir;
  bunlar **yanlış pozitiftir**, gerçek hata değildir.

### 4.4 Username Üretimi
- `uniqueUsername()` → maksimum 50 karakter sınırı korunmalıdır (overflow koruması var).

---

## 5. Google OAuth & Firebase

- Firebase & GCP Projesi: `aiajan-portfolio` (Proje No: `472630529521`). Google Auth Platform altında `YouTube Keşif` markalaması yeşil tik ile onaylanmıştır.
- Destek Grubu E-Postası: `iajan-destek@googlegroups.com`
- Login akışı: **Firebase** (`aiajan-portfolio` projesi) üzerinden `signInWithPopup`.
- YouTube kanal sync: Firebase Google OAuth popup → `youtube.readonly` scope.
- Gizlilik politikası URL'si: `https://youtube.aiajan.com.tr/gizlilik`

---

## 6. Veritabanı Tabloları (Kritik)

| Tablo | Açıklama |
|---|---|
| `users` | Platform üyeleri |
| `youtube_channels` | YouTube kanal meta verileri |
| `user_channel_links` | Kullanıcı↔Kanal bağlantıları (geçmiş dahil) |
| `videos` | Senkronize edilmiş videolar |
| `user_video_selections` | Kullanıcının vitrine seçtiği videolar (max 3) |
| `platform_follows` | Platform içi takip ilişkileri |
| `user_impressions` | Discovery havuzu görüntüleme geçmişi |
| `supporter_statuses` | Destekçi üyelik kayıtları |
| `audit_logs` | İletişim formu ve güvenlik olayları |
