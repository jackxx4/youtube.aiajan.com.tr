#!/usr/bin/env bash
# ==============================================================================
# YouTube Keşif — Canlı Dağıtım & Telemetri Protokolü (deploy.sh)
# Kural: Önce deploy.sh ile canlı sunucuya aktarılır, loglar denetlenir,
#       sonrasında GitHub origin/main'e push edilir.
#
# Kullanım:
#   ./deploy.sh            -> Canlıya dağıt, logları çek & denetle, GitHub'a push et
#   ./deploy.sh log çek    -> Canlı sunucudaki laravel.log'u yerele çeker
#   ./deploy.sh log oku    -> Canlı logları çeker ve en son hataları listeler
# ==============================================================================

set -Eeuo pipefail

PROJECT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
cd "$PROJECT_DIR"

ARG1="${1:-}"
ARG2="${2:-}"

# Alt Komut: Log Çek
if [ "$ARG1" = "log" ] && [ "$ARG2" = "çek" -o "$ARG2" = "cek" -o "$ARG2" = "pull" ] || [ "$ARG1" = "log-cek" ]; then
    echo "================================================================="
    echo "📥 CANLI HATA LOGU ÇEKİLİYOR -> https://youtube.aiajan.com.tr"
    echo "================================================================="
    python3 deploy.py log-cek
    exit 0
fi

# Alt Komut: Log Oku
if [ "$ARG1" = "log" ] && [ "$ARG2" = "oku" -o "$ARG2" = "read" ] || [ "$ARG1" = "log-oku" ]; then
    echo "================================================================="
    echo "📋 CANLI HATA LOGLARI OKUNUYOR -> https://youtube.aiajan.com.tr"
    echo "================================================================="
    python3 deploy.py log-oku
    exit 0
fi

# Alt Komut: Versiyon
if [ "$ARG1" = "versiyon" ] || [ "$ARG1" = "version" ]; then
    echo "================================================================="
    echo "🏷️ YOUTUBE KEŞİF VERSİYON BİLGİSİ"
    echo "================================================================="
    python3 -c "
import urllib.request, ssl, json
with open('config/version.php') as f:
    text = f.read()
import re
m = re.search(r\"'current'\s*=>\s*'([^']+)'\", text)
ver = m.group(1) if m else '1.1.0'
print(f'Yerel Versiyon: v{ver}')
try:
    ctx = ssl.create_default_context()
    ctx.check_hostname = False
    ctx.verify_mode = ssl.CERT_NONE
    with urllib.request.urlopen('https://youtube.aiajan.com.tr/api/version', timeout=5, context=ctx) as r:
        data = json.loads(r.read().decode())
        print(f\"Canlı Versiyon: v{data.get('version', 'Bilinmiyor')}\")
except Exception as e:
    print('Canlı versiyon sorgulanamadı:', e)
"
    exit 0
fi

# Standart Dağıtım Akışı
echo "================================================================="
echo "🚀 YOUTUBE KEŞİF DAĞITIM PROTOKOLÜ BAŞLATILIYOR"
echo "Hedef: https://youtube.aiajan.com.tr (31.186.11.163)"
echo "================================================================="

# 1. Aşama: Canlı Sunucuya Dağıtım + Canlı Log Denetimi
echo "▶ 1. Adım: Canlı sunucuya dağıtılıyor ve telemetri kontrol ediliyor..."
python3 deploy.py "$@"

echo ""
echo "▶ 2. Adım: Canlı sunucu yayını ve logları doğrulandı. GitHub senkronizasyonu başlatılıyor..."

# 2. Aşama: Canlı sunucu başarıyla doğrulandıktan sonra GitHub'a push et
if git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
    # Önce uzak değişiklikleri çek — aksi halde bizim git commit'lerimizi ezer
    echo "  [↓] GitHub origin main'den son değişiklikler çekiliyor..."
    git pull --rebase origin main || true

    if ! git diff-index --quiet HEAD -- 2>/dev/null; then
        echo "  [i] Yerel değişiklikler commit ediliyor..."
        # deploy.py ve deploy.sh gibi deploy araçlarını hariç tut
        git add app/ config/ database/ resources/ routes/ bootstrap/ public/ tests/
        git commit -m "deploy: canlı sunucuya dağıtıldı, loglar kontrol edildi ve doğrulandı"
    fi

    echo "  [↑] GitHub origin main'e gönderiliyor..."
    git push origin main
    echo "  ✓ GitHub push tamamlandı."
else
    echo "  ⚠️ Git deposu bulunamadı, push atlandı."
fi

echo ""
echo "================================================================="
echo "🎉 DAĞITIM VE GİTHUB PUSH BAŞARIYLA TAMAMLANDI!"
echo "Canlı Adres: https://youtube.aiajan.com.tr"
echo "Yerel Canlı Log: storage/logs/remote_laravel.log"
echo "================================================================="
