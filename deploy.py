#!/usr/bin/env python3
"""
youtube.aiajan.com.tr — Akıllı Delta (Diff-Only) Canlı Dağıtım Sistemi
- Akıllı Delta: Yalnızca boyutu veya mtime'ı değişen dosyaları aktarır
- Sıfır-Kesinti: Plesk Windows IIS için optimize edilmiş dosya aktarımı
- Otomatik Geçiş: Uzak index.html placeholder temizliği ve derlenmiş view temizliği
- Güvenli Migration: _deploy/artisan uç noktası ile canlı veritabanı şemasını günceller
- Doğrulama: https://youtube.aiajan.com.tr/ adresini test eder
"""

import ftplib
import io
import json
import os
import ssl
import sys
import time
import urllib.request
import urllib.parse
from pathlib import Path

BASE_DIR = Path(__file__).resolve().parent
CACHE_FILE = BASE_DIR / ".deploy_cache.json"


def load_env_file(path: Path) -> dict:
    env = {}
    if not path.exists():
        return env
    for line in path.read_text(encoding="utf-8", errors="ignore").splitlines():
        line = line.strip()
        if not line or line.startswith("#") or "=" not in line:
            continue
        k, v = line.split("=", 1)
        env[k.strip()] = v.strip().strip("'").strip('"')
    return env


_env = {}
_env.update(load_env_file(BASE_DIR / ".env"))
_env.update(load_env_file(BASE_DIR / ".env.deploy"))

FTP_HOST = _env.get("FTP_HOST", "31.186.11.163")
FTP_PORT = int(_env.get("FTP_PORT", "21"))
FTP_USER = _env.get("FTP_USER", "aia321comtr")
FTP_PASS = _env.get("FTP_PASS", "")
REMOTE_ROOT = _env.get("FTP_REMOTE_DIR", "/youtube.aiajan.com.tr").rstrip("/")
DEPLOY_SECRET = _env.get("DEPLOY_SECRET", "k78Y2m9PqR1sT5vW4xZ8bA3cE6fH0jL2")
LIVE_URL = _env.get("LIVE_URL", "https://youtube.aiajan.com.tr")

if not FTP_PASS:
    print("❌ HATA: FTP_PASS tanımlı değil. Lütfen .env.deploy dosyasını kontrol edin.", file=sys.stderr)
    sys.exit(1)

SYNC_DIRS = [
    "app",
    "config",
    "database/migrations",
    "public",
    "resources",
    "routes",
    "crates/youtube-sync/src",
]

SYNC_FILES = [
    "bootstrap/app.php",
    "bootstrap/providers.php",
    "composer.json",
    "crates/youtube-sync/Cargo.toml",
]

SKIP_NAMES = {
    ".DS_Store",
    "hot",
    ".env",
    ".env.deploy",
    ".deploy_cache.json",
    "database.sqlite",
}

FTP_ERRORS = ftplib.all_errors + (OSError,)


def load_cache() -> dict:
    if CACHE_FILE.exists():
        try:
            return json.loads(CACHE_FILE.read_text(encoding="utf-8"))
        except Exception:
            return {}
    return {}


def save_cache(cache: dict):
    try:
        CACHE_FILE.write_text(json.dumps(cache, indent=2), encoding="utf-8")
    except Exception:
        pass


def get_file_sig(path: Path) -> str:
    st = path.stat()
    return f"{st.st_size}_{int(st.st_mtime)}"


def connect_ftp() -> ftplib.FTP:
    ftp = ftplib.FTP()
    ftp.connect(FTP_HOST, FTP_PORT, timeout=30)
    ftp.login(FTP_USER, FTP_PASS)
    ftp.encoding = "utf-8"
    return ftp


def ensure_remote_dir(ftp: ftplib.FTP, remote_dir: str, created_cache: set):
    if remote_dir in created_cache:
        return
    parts = [p for p in remote_dir.split("/") if p]
    curr = ""
    for part in parts:
        curr += "/" + part
        if curr in created_cache:
            continue
        try:
            ftp.cwd(curr)
            created_cache.add(curr)
        except ftplib.error_perm:
            try:
                ftp.mkd(curr)
                created_cache.add(curr)
            except ftplib.error_perm:
                pass


def upload_file(ftp: ftplib.FTP, local_path: Path, remote_path: str, created_cache: set) -> ftplib.FTP:
    remote_dir = "/".join(remote_path.split("/")[:-1])
    for attempt in range(4):
        try:
            ensure_remote_dir(ftp, remote_dir, created_cache)
            with local_path.open("rb") as f:
                ftp.storbinary(f"STOR {remote_path}", f)
            return ftp
        except FTP_ERRORS:
            if attempt == 3:
                raise
            time.sleep(0.5)
            try:
                ftp.close()
            except Exception:
                pass
            ftp = connect_ftp()
    return ftp


def cleanup_plesk_placeholder(ftp: ftplib.FTP):
    """Plesk varsayılan index.html dosyasını kaldırır (Laravel public/index.php'yi örtmesin)"""
    index_path = f"{REMOTE_ROOT}/index.html"
    try:
        ftp.delete(index_path)
        print("✓ Plesk varsayılan index.html placeholder kaldırıldı.")
    except Exception:
        pass


def sync_remote_env(ftp: ftplib.FTP):
    """Canlı sunucunun .env dosyasını MariaDB ve canlı ayarlarıyla yapılandırır"""
    remote_env_path = f"{REMOTE_ROOT}/.env"
    bio = io.BytesIO()
    existing_lines = []
    try:
        ftp.retrbinary(f"RETR {remote_env_path}", bio.write)
        existing_lines = bio.getvalue().decode("utf-8", errors="ignore").splitlines()
    except Exception:
        pass

    env_dict = {}
    for line in existing_lines:
        line = line.strip()
        if not line or line.startswith("#") or "=" not in line:
            continue
        k, v = line.split("=", 1)
        env_dict[k.strip()] = v.strip()

    # Canlı ortam zorunlu değişkenleri
    updates = {
        "APP_NAME": '"YouTube Kesif"',
        "APP_ENV": "production",
        "APP_DEBUG": "false",
        "APP_URL": LIVE_URL,
        "DB_CONNECTION": "mysql",
        "DB_HOST": "127.0.0.1",
        "DB_PORT": "3306",
        "DB_DATABASE": "youtube_db",
        "DB_USERNAME": "youtubedb",
        "DB_PASSWORD": '!?Uc8nu0YvHylad2',
        "SESSION_DRIVER": "file",
        "CACHE_STORE": "file",
        "QUEUE_CONNECTION": "sync",
        "DEPLOY_SECRET": DEPLOY_SECRET,
    }

    changed = False
    for k, v in updates.items():
        if env_dict.get(k) != v:
            env_dict[k] = v
            changed = True

    if not env_dict.get("APP_KEY"):
        env_dict["APP_KEY"] = "base64:uJwiipCjI67PdtT79IO36KQXfArQVc4FS2T4b2ZTeSc="
        changed = True

    if changed:
        out = "\n".join(f"{k}={v}" for k, v in env_dict.items()) + "\n"
        bio_out = io.BytesIO(out.encode("utf-8"))
        ftp.storbinary(f"STOR {remote_env_path}", bio_out)
        print("✓ Canlı sunucu .env konfigürasyonu (MariaDB + Üretim) güncellendi.")


def clear_remote_compiled_views(ftp: ftplib.FTP) -> tuple:
    views_dir = f"{REMOTE_ROOT}/storage/framework/views"
    try:
        ftp.cwd(views_dir)
    except FTP_ERRORS:
        try:
            ftp.close()
        except Exception:
            pass
        ftp = connect_ftp()
        try:
            ftp.cwd(views_dir)
        except Exception:
            return ftp, 0

    removed = 0
    try:
        files = ftp.nlst()
        for fname in files:
            base = fname.split("/")[-1]
            if base.endswith(".php"):
                try:
                    ftp.delete(f"{views_dir}/{base}")
                    removed += 1
                except Exception:
                    pass
    except Exception:
        pass
    return ftp, removed


def trigger_remote_artisan(command: str) -> dict:
    ctx = ssl.create_default_context()
    ctx.check_hostname = False
    ctx.verify_mode = ssl.CERT_NONE

    query = urllib.parse.urlencode({"token": DEPLOY_SECRET, "cmd": command})
    url = f"{LIVE_URL}/_deploy/artisan?{query}"
    req = urllib.request.Request(url, headers={"User-Agent": "YouTubeKesifDeployer/1.0"})

    try:
        with urllib.request.urlopen(req, timeout=30, context=ctx) as resp:
            data = resp.read().decode("utf-8")
            return json.loads(data)
    except Exception as e:
        return {"status": "error", "message": str(e)}


def verify_live_site() -> tuple:
    ctx = ssl.create_default_context()
    ctx.check_hostname = False
    ctx.verify_mode = ssl.CERT_NONE

    req = urllib.request.Request(LIVE_URL, headers={"User-Agent": "YouTubeKesifDeployer/1.0"})
    try:
        with urllib.request.urlopen(req, timeout=20, context=ctx) as resp:
            status = resp.status
            body = resp.read().decode("utf-8", errors="ignore")
            has_brand = "Keşfet. Tanış. Bağ kur." in body or "YouTube" in body
            return status, has_brand
    except Exception as e:
        return 0, False


def main():
    force_all = "--all" in sys.argv
    verbose = "--verbose" in sys.argv
    start_time = time.time()

    print(f"🚀 Canlı Sunucuya Dağıtım Başlatılıyor -> {LIVE_URL} ({FTP_HOST})")

    cache = {} if force_all else load_cache()
    new_cache = dict(cache)

    files_to_check = []
    for rel in SYNC_FILES:
        lpath = BASE_DIR / rel
        if lpath.is_file():
            files_to_check.append(lpath)

    for d in SYNC_DIRS:
        ldir = BASE_DIR / d
        if not ldir.is_dir():
            continue
        for root, _, files in os.walk(ldir):
            for fname in sorted(files):
                if fname in SKIP_NAMES or fname.endswith(".sqlite") or fname.startswith(".git"):
                    continue
                files_to_check.append(Path(root) / fname)

    changed_files = []
    for lpath in files_to_check:
        rel = lpath.relative_to(BASE_DIR).as_posix()
        sig = get_file_sig(lpath)
        if cache.get(rel) != sig or force_all:
            changed_files.append((lpath, rel, sig))

    total_scanned = len(files_to_check)
    to_upload_count = len(changed_files)

    ftp = connect_ftp()
    created_cache = set()

    # 1. Plesk ve .env temizlik/yapılandırma
    cleanup_plesk_placeholder(ftp)
    sync_remote_env(ftp)

    # 2. Dosya yüklemeleri
    if to_upload_count > 0:
        print(f"📦 {to_upload_count}/{total_scanned} delta dosya aktarılıyor...")
        for lpath, rel, sig in changed_files:
            rpath = f"{REMOTE_ROOT}/{rel}"
            ftp = upload_file(ftp, lpath, rpath, created_cache)
            new_cache[rel] = sig
            if verbose:
                print(f"  [↑] {rel}")
        save_cache(new_cache)
    else:
        print(f"⚡ Tüm {total_scanned} dosya canlı sunucu ile senkron.")

    # 3. Derlenmiş view önbelleğini temizle
    ftp, removed_views = clear_remote_compiled_views(ftp)
    try:
        ftp.quit()
    except Exception:
        pass

    # 4. Uzak Veritabanı Migrations ve Önbellek Yenileme
    print("⚙️ Canlı sunucu migration ve optimizasyon kontrolü yapılıyor...")
    mig_res = trigger_remote_artisan("migrate")
    if mig_res.get("status") == "success":
        out_msg = mig_res.get("output", "").strip() or "Tablolar güncel."
        print(f"  ✓ Migration sonucu: {out_msg}")
    else:
        print(f"  ⚠️ Migration yanıtı: {mig_res.get('message', 'Bilinmeyen')}")

    opt_res = trigger_remote_artisan("optimize:clear")
    if opt_res.get("status") == "success":
        print("  ✓ Önbellek temizlendi (optimize:clear).")

    # 5. Canlı Sağlık Kontrolü
    status_code, has_brand = verify_live_site()
    elapsed = time.time() - start_time

    if status_code == 200 and has_brand:
        print("=" * 65)
        print(f"✅ CANLI SUNUCU BAŞARIYLA YAYINDA: {LIVE_URL} (HTTP {status_code})")
        print(f"⏱️ Süre: {elapsed:.2f}s | Aktarılan: {to_upload_count} dosya | Temizlenen View: {removed_views}")
        print("=" * 65)
        sys.exit(0)
    elif status_code == 200:
        print(f"⚠️ Canlı site HTTP 200 döndü ancak marka metni bulunamadı. Lütfen kontrol edin.")
        sys.exit(0)
    else:
        print(f"❌ HATA: Canlı site HTTP {status_code} hatası verdi!", file=sys.stderr)
        sys.exit(1)


if __name__ == "__main__":
    main()

