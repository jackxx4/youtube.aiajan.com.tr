# Güvenli Plesk Dağıtımı

## Veritabanı

Uygulama MySQL 8+ veya MariaDB gerektirir. Plesk'te veritabanı oluştururken **MS SQL Server seçilmez**; MySQL/MariaDB sunucusu seçilir.

Veritabanı ve kullanıcı oluşturulduktan sonra gerçek değerleri yalnızca sunucudaki `.env` dosyasına yazın. Parola, GitHub'a, `deploy.sh` dosyasına veya sohbet mesajlarına eklenmez.

~~~dotenv
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=veritabani_adi
DB_USERNAME=veritabani_kullanici_adi
DB_PASSWORD=guclu_parola
~~~

## İlk hazırlık

1. Plesk'te alan adının belge kökünü proje içindeki `public` dizinine yönlendirin.
2. PHP 8.3+ ve gerekli PHP eklentilerini etkinleştirin.
3. Sunucuda `.env.example` dosyasını `.env` olarak kopyalayın; `APP_KEY`, veritabanı ve Google OAuth değerlerini ekleyin.
4. `storage` ile `bootstrap/cache` dizinlerini PHP kullanıcısının yazabileceği şekilde ayarlayın.
5. Sunucuda `composer.lock` yoksa betik otomatik olarak bağımlılıkları yükleyip oluşturur; `cargo` mevcutsa Rust YouTube motorunu derler.

## Dağıtım

Sunucuda proje kökünde bir kez aşağıdakini çalıştırın:

~~~bash
chmod +x deploy.sh
./deploy.sh
~~~

Kod sunucuda Git ile tutuluyorsa, güncel `main` dalını da almak için:

~~~bash
DEPLOY_PULL=1 ./deploy.sh
~~~

Betik; bağımlılıkları kilit dosyasından kurar, bakım moduna geçer, migration'ları uygular, Laravel önbelleklerini yeniler ve başarılı olduğunda uygulamayı yeniden açar. Bir hata oluşursa uygulama bakım modunda kalır; sebep giderilmeden `php artisan up` çalıştırılmamalıdır.

## Son kontroller

~~~bash
php artisan schedule:list
curl -fsS https://youtube.aiajan.com.tr/up
~~~

Plesk Scheduled Tasks alanında her dakika bir kez Laravel scheduler'ı çalıştırın:

~~~bash
php /TAM/YOL/youtube.aiajan.com.tr/artisan schedule:run
~~~
