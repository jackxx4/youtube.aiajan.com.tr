#!/usr/bin/env bash

set -Eeuo pipefail

PROJECT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
DEPLOY_PULL="${DEPLOY_PULL:-0}"
MAINTENANCE_ENABLED=0

cd "$PROJECT_DIR"

fail() {
    printf 'Dağıtım durduruldu: %s\n' "$*" >&2
    exit 1
}

on_error() {
    local exit_code=$?

    if [ "$MAINTENANCE_ENABLED" -eq 1 ]; then
        printf 'Dağıtım başarısız oldu; uygulama bakım modunda bırakıldı.\n' >&2
    fi

    exit "$exit_code"
}

trap on_error ERR

command -v "$PHP_BIN" >/dev/null 2>&1 || fail "PHP bulunamadı. PHP_BIN ile yolu belirtin."
command -v "$COMPOSER_BIN" >/dev/null 2>&1 || fail "Composer bulunamadı. COMPOSER_BIN ile yolu belirtin."
[ -f artisan ] || fail "Bu komut Laravel proje kökünde çalıştırılmalıdır."
[ -f .env ] || fail ".env bulunamadı. Önce .env.example dosyasından oluşturun."

for required_key in DB_DATABASE DB_USERNAME DB_PASSWORD; do
    required_value="$(sed -n "s/^${required_key}=//p" .env | head -n 1)"
    if [ -z "$required_value" ] || [ "$required_value" = "BURAYA_YAZILACAK" ]; then
        fail ".env içindeki ${required_key} değeri ayarlanmamış."
    fi
done

if ! "$PHP_BIN" -r 'exit version_compare(PHP_VERSION, "8.3.0", ">=") ? 0 : 1;'; then
    fail "PHP 8.3 veya daha yeni bir sürüm gerekir."
fi

if [ "$DEPLOY_PULL" = "1" ]; then
    git fetch origin main
    git pull --ff-only origin main
fi

[ -w storage ] || fail "storage dizini PHP kullanıcısı tarafından yazılabilir değil."
[ -w bootstrap/cache ] || fail "bootstrap/cache dizini PHP kullanıcısı tarafından yazılabilir değil."

if [ -f composer.lock ]; then
    "$COMPOSER_BIN" install --no-dev --no-interaction --prefer-dist --optimize-autoloader
else
    "$COMPOSER_BIN" update --no-dev --no-interaction --prefer-dist --optimize-autoloader
fi

if command -v cargo >/dev/null 2>&1 && [ -d crates/youtube-sync ]; then
    printf 'Rust YouTube Sync Motoru derleniyor...\n'
    (cd crates/youtube-sync && cargo build --release)
    mkdir -p bin
    if [ -f crates/youtube-sync/target/release/youtube-sync ]; then
        cp crates/youtube-sync/target/release/youtube-sync bin/
        chmod +x bin/youtube-sync
    elif [ -f crates/youtube-sync/target/release/youtube-sync.exe ]; then
        cp crates/youtube-sync/target/release/youtube-sync.exe bin/
    fi
fi
if ! grep -Eq '^APP_KEY=base64:.+' .env; then
    "$PHP_BIN" artisan key:generate --force
fi
"$PHP_BIN" artisan down --retry=60
MAINTENANCE_ENABLED=1

"$PHP_BIN" artisan migrate --force
"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan route:cache
"$PHP_BIN" artisan view:cache
"$PHP_BIN" artisan up
MAINTENANCE_ENABLED=0

printf 'Dağıtım başarıyla tamamlandı.\n'
