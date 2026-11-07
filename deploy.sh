#!/usr/bin/env bash
# ==============================================================================
# YouTube Keşif — Canlı Dağıtım Protokolü (deploy.sh)
# Kural: Önce deploy.sh ile canlı sunucuya gönderilecek,
#       sonrasında GitHub origin/main'e push edilecek.
# ==============================================================================

set -Eeuo pipefail

PROJECT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
cd "$PROJECT_DIR"

echo "================================================================="
echo "🚀 YOUTUBE KEŞİF DAĞITIM PROTOKOLÜ BAŞLATILIYOR"
echo "Hedef: https://youtube.aiajan.com.tr (31.186.11.163)"
echo "================================================================="

# 1. Aşama: Canlı Sunucuya Dağıtım (FTP Delta + Migration + Health Check)
echo "▶ 1. Adım: Canlı sunucuya dağıtılıyor (deploy.py)..."
python3 deploy.py "$@"

echo ""
echo "▶ 2. Adım: Canlı sunucu yayını doğrulandı. GitHub senkronizasyonu başlatılıyor..."

# 2. Aşama: Canlı sunucu başarıyla doğrulandıktan sonra GitHub'a push et
if git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
    # Varsa commit edilmemiş değişiklikleri kontrol et
    if ! git diff-index --quiet HEAD -- 2>/dev/null; then
        echo "  [i] Değişiklikler commit ediliyor..."
        git add -A
        git commit -m "deploy: canlı sunucuya başarıyla dağıtıldı ve doğrulandı"
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
echo "================================================================="
