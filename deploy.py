#!/usr/bin/env python3
"""
youtube.aiajan.com.tr — Akıllı Delta (Diff-Only) Canlı Dağıtım & Telemetri Sistemi
- Akıllı Delta: Yalnızca boyutu veya mtime'ı değişen dosyaları aktarır
- Sıfır-Kesinti: Plesk Windows IIS ve MS SQL Server için optimize edilmiş aktarım
- Otomatik Hata Takibi: Dağıtım sonrasında canlı hata loglarını yerele çeker ve analiz eder
- Bağımsız Komutlar: 'log çek' / 'log oku' komutlarıyla canlı logları anlık denetler
- Doğrulama: https://youtube.aiajan.com.tr/ adresini test eder
"""

import ftplib
import io
import json
import os
import re
import sys
import time
import urllib.parse
import urllib.request
from pathlib import Path

BASE_DIR = Path(__file__).resolve().parent
CACHE_FILE = BASE_DIR / ".deploy_cache.json"
LOCAL_LOG_PATH = BASE_DIR / "storage" / "logs" / "remote_laravel.log"


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

FIRESTORE_PROJECT = _env.get("FIREBASE_PROJECT_ID", "aiajan-portfolio")


def get_current_version() -> str:
    ver_path = BASE_DIR / "config" / "version.php"
    if ver_path.exists():
        content = ver_path.read_text(encoding="utf-8")
        m = re.search(r"'current'\s*=>\s*'([^']+)'", content)
        if m:
            return m.group(1)
    return "1.1.0"


def update_firestore_version(version: str):
    api_key = _env.get("FIREBASE_API_KEY", "")
    if not api_key:
        return
    url = (
        f"https://firestore.googleapis.com/v1/projects/{FIRESTORE_PROJECT}"
        f"/databases/(default)/documents/meta/youtube_version?key={api_key}"
    )
    payload = json.dumps({
        "fields": {
            "current": {"stringValue": version},
            "updatedAt": {"stringValue": str(int(time.time()))}
        }
    }).encode("utf-8")
    try:
        req = urllib.request.Request(url, data=payload, headers={"Content-Type": "application/json"}, method="PATCH")
        with urllib.request.urlopen(req, timeout=10) as resp:
            pass
        print(f"📡 Canlı versiyon Firestore üzerinde senkronize edildi (v{version}).")
    except Exception as e:
        print(f"⚠️ Firestore versiyon güncelleme uyarısı: {e}")

FTP_HOST = _env.get("FTP_HOST", "")
FTP_PORT = int(_env.get("FTP_PORT", "21"))
FTP_USER = _env.get("FTP_USER", "")
FTP_PASS = _env.get("FTP_PASS", "")
REMOTE_ROOT = _env.get("FTP_REMOTE_DIR", "/youtube.aiajan.com.tr").rstrip("/")
DEPLOY_SECRET = _env.get("DEPLOY_SECRET", "")
LIVE_URL = _env.get("LIVE_URL", "https://youtube.aiajan.com.tr")

for _req_key, _req_val in [("FTP_PASS", FTP_PASS), ("DEPLOY_SECRET", DEPLOY_SECRET), ("FTP_HOST", FTP_HOST)]:
    if not _req_val:
        print(f"❌ HATA: {_req_key} tanımlı değil. Lütfen .env.deploy dosyasını kontrol edin.", file=sys.stderr)
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
    "remote_laravel.log",
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
    """Canlı sunucunun .env dosyasını MS SQL Server ve canlı ayarlarıyla yapılandırır"""
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

    # Canlı ortam zorunlu değişkenleri (Plesk Windows MS SQL Server)
    updates = {
        "APP_NAME": '"YouTube Kesif"',
        "APP_ENV": "production",
        "APP_DEBUG": "false",
        "APP_URL": LIVE_URL,
        "DB_CONNECTION": "sqlsrv",
        "DB_HOST": "127.0.0.1",
        "DB_PORT": "1433",
        "DB_DATABASE": "youtube_db",
        "DB_USERNAME": _env.get("DB_USERNAME", ""),
        "DB_PASSWORD": _env.get("DB_PASSWORD", ""),
        "DB_TRUST_SERVER_CERTIFICATE": "true",
        "SESSION_DRIVER": "file",
        "CACHE_STORE": "file",
        "QUEUE_CONNECTION": "sync",
        "FIREBASE_API_KEY": _env.get("FIREBASE_API_KEY", ""),
        "FIREBASE_PROJECT_ID": _env.get("FIREBASE_PROJECT_ID", ""),
        "YOUTUBE_API_KEY": _env.get("YOUTUBE_API_KEY", ""),
        "DEPLOY_SECRET": DEPLOY_SECRET,
    }

    changed = False
    for k, v in updates.items():
        if v and env_dict.get(k) != v:
            env_dict[k] = v
            changed = True

    app_key = _env.get("APP_KEY", "")
    if app_key and not env_dict.get("APP_KEY"):
        env_dict["APP_KEY"] = app_key
        changed = True

    if changed:
        out = "\n".join(f"{k}={v}" for k, v in env_dict.items()) + "\n"
        bio_out = io.BytesIO(out.encode("utf-8"))
        ftp.storbinary(f"STOR {remote_env_path}", bio_out)
        print("✓ Canlı sunucu .env konfigürasyonu (MS SQL Server + Üretim) güncellendi.")


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
    url = f"{LIVE_URL}/_deploy/artisan"
    body = urllib.parse.urlencode({"token": DEPLOY_SECRET, "cmd": command}).encode("utf-8")
    req = urllib.request.Request(
        url, data=body,
        headers={"User-Agent": "YouTubeKesifDeployer/1.0", "Content-Type": "application/x-www-form-urlencoded"},
        method="POST"
    )
    try:
        with urllib.request.urlopen(req, timeout=30) as resp:
            data = resp.read().decode("utf-8")
            return json.loads(data)
    except Exception as e:
        return {"status": "error", "message": str(e)}


def verify_live_site() -> tuple:
    req = urllib.request.Request(LIVE_URL, headers={"User-Agent": "YouTubeKesifDeployer/1.0"})
    try:
        with urllib.request.urlopen(req, timeout=20) as resp:
            status = resp.status
            body = resp.read().decode("utf-8", errors="ignore")
            has_brand = "Keşfet. Tanış. Bağ kur." in body or "YouTube" in body
            return status, has_brand
    except Exception as e:
        return 0, False


def pull_remote_log(verbose: bool = True) -> Path:
    """Canlı sunucudaki storage/logs/laravel.log dosyasını yerele çeker"""
    LOCAL_LOG_PATH.parent.mkdir(parents=True, exist_ok=True)
    remote_log = f"{REMOTE_ROOT}/storage/logs/laravel.log"
    bio = io.BytesIO()
    ftp = connect_ftp()
    try:
        ftp.retrbinary(f"RETR {remote_log}", bio.write)
    except Exception as e:
        if verbose:
            print(f"⚠️ Canlı log çekilemedi veya henüz uzak log oluşmadı: {e}")
        return LOCAL_LOG_PATH
    finally:
        try:
            ftp.quit()
        except Exception:
            pass

    data = bio.getvalue()
    LOCAL_LOG_PATH.write_bytes(data)
    if verbose:
        size_kb = len(data) / 1024
        line_count = len(data.decode("utf-8", errors="ignore").splitlines())
        print(f"📥 Canlı log yerele çekildi -> {LOCAL_LOG_PATH} ({size_kb:.1f} KB, {line_count} satır)")
    return LOCAL_LOG_PATH


def read_error_log(limit: int = 5):
    """Yerele çekilmiş canlı logları analiz eder ve en son hataları listeler"""
    if not LOCAL_LOG_PATH.exists() or LOCAL_LOG_PATH.stat().st_size == 0:
        print("ℹ️ Henüz yerelde log dosyası bulunmuyor. Önce log çekin.")
        return

    content = LOCAL_LOG_PATH.read_text(encoding="utf-8", errors="ignore")
    lines = content.splitlines()

    entries = []
    current_entry = None
    header_pattern = re.compile(r"^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] \w+\.(ERROR|CRITICAL|ALERT|EMERGENCY): (.*)")

    for line in lines:
        m = header_pattern.match(line)
        if m:
            if current_entry:
                entries.append(current_entry)
            current_entry = {
                "time": m.group(1),
                "level": m.group(2),
                "message": m.group(3),
                "stack": [],
            }
        elif current_entry:
            # Sadece uygulama içi veya temel stack trace satırlarını al
            if len(current_entry["stack"]) < 3 and line.strip().startswith("#"):
                current_entry["stack"].append(line.strip())

    if current_entry:
        entries.append(current_entry)

    if not entries:
        print("✨ Canlı loglarda herhangi bir hata kaydı bulunamadı. Sistem %100 sağlıklı!")
        return

    print("=" * 70)
    print(f"📋 CANLI HATA LOGLARI (Toplam: {len(entries)} hata kaydı | Son {min(limit, len(entries))} gösteriliyor)")
    print("=" * 70)

    for entry in entries[-limit:]:
        print(f"🔴 [{entry['time']}] {entry['level']}: {entry['message'][:180]}")
        for st in entry["stack"]:
            print(f"     {st[:140]}")
        print("-" * 70)


def main():
    args_str = " ".join(sys.argv[1:]).lower()

    # 1. 'log çek' / 'log-cek' komut kurgusu
    if "cek" in args_str or "çek" in args_str or "pull" in args_str:
        pull_remote_log(verbose=True)
        return

    # 2. 'log oku' / 'log-oku' komut kurgusu
    if "oku" in args_str or "read" in args_str or "show" in args_str:
        pull_remote_log(verbose=False)
        read_error_log(limit=8)
        return

    # 3. Normal Tam Dağıtım Kurgusu
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
        current_version = get_current_version()
        print("=" * 70)
        print(f"✅ CANLI SUNUCU BAŞARIYLA YAYINDA: {LIVE_URL} (HTTP {status_code}) [v{current_version}]")
        print(f"⏱️ Süre: {elapsed:.2f}s | Aktarılan: {to_upload_count} dosya | Temizlenen View: {removed_views}")
        print("=" * 70)

        # 6. Versiyon Senkronizasyonu (Firestore & Ekosistem)
        update_firestore_version(current_version)

        # 7. Dağıtım Sonrası Otomatik Canlı Hata Loglarını Çek ve Kontrol Et
        print("\n🔍 Dağıtım Sonrası Canlı Hata Logları Yerele Çekiliyor ve Kontrol Ediliyor...")
        pull_remote_log(verbose=True)
        read_error_log(limit=3)

        sys.exit(0)
    elif status_code == 200:
        print(f"⚠️ Canlı site HTTP 200 döndü ancak marka metni bulunamadı. Lütfen kontrol edin.")
        pull_remote_log(verbose=True)
        read_error_log(limit=3)
        sys.exit(0)
    else:
        print(f"❌ HATA: Canlı site HTTP {status_code} hatası verdi!", file=sys.stderr)
        pull_remote_log(verbose=True)
        read_error_log(limit=5)
        sys.exit(1)


if __name__ == "__main__":
    main()
