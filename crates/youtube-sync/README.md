# YouTube Sync Engine (Rust)

YouTube Keşif platformu için geliştirilmiş, yüksek hızlı ve asenkron YouTube veri senkronizasyon motoru.

## Özellikler
- **Asenkron Çalışma:** `tokio` ve `reqwest` ile bloklanmayan HTTP/2 API istekleri.
- **Düşük Kaynak Tüketimi:** PHP'ye kıyasla %95 daha az RAM (~5-10 MB) ve anlık bitiş.
- **MariaDB / MySQL Entegrasyonu:** `sqlx` bağlantı havuzu ile güvenli, tip korumalı SQL işlemleri.
- **Otomatik Düşme (Fallback):** Laravel Artisan komutu (`php artisan youtube:sync-channels`), bu derlenmiş ikili (`bin/youtube-sync` veya `bin/youtube-sync.exe`) varsa doğrudan bunu çalıştırır, yoksa PHP koduna devam eder.

## Derleme Talimatları

### Linux / macOS
```bash
cd crates/youtube-sync
cargo build --release
mkdir -p ../../bin
cp target/release/youtube-sync ../../bin/
chmod +x ../../bin/youtube-sync
```

### Windows (Plesk Server)
```powershell
cd crates\youtube-sync
cargo build --release
mkdir ..\..\bin -Force
copy target\release\youtube-sync.exe ..\..\bin\
```

## Bağımsız Çalıştırma
Kök dizinde `.env` dosyası bulunurken doğrudan çalıştırılabilir:
```bash
./bin/youtube-sync
```

