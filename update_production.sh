#!/usr/bin/env bash
# update_production.sh — Script update cPanel (hardened)
# Vetted by SOSIOMEN - Manual Review Required by Senior Engineer/Manager

set -euo pipefail

APP_DIR="${APP_DIR:-/home/simlppmi/sim-lppm}"
BASE_URL="${BASE_URL:-https://sim-lppm.itsnupekalongan.ac.id}"

PRE_DEPLOY_SHA="$(git -C "$APP_DIR" rev-parse --short HEAD 2>/dev/null || echo unknown)"
DEPLOY_SHA="$PRE_DEPLOY_SHA"
MAINTENANCE_ON=0

fail() {
  echo "❌ $*" >&2
  exit 1
}

# Catat kegagalan deploy ke dev_events (W4-H2.5). Tidak boleh menggagalkan deploy.
record_deploy_failure() {
  php artisan dev:record deploy "Deploy gagal (${DEPLOY_SHA})" \
    --area=produksi --status=failure --ref="${DEPLOY_SHA}" 2>/dev/null || true
}

# EXIT trap menangkap semua jalur gagal — termasuk `exit 1` eksplisit yang
# tidak memicu trap ERR. Situs wajib dinyalakan kembali bila maintenance aktif.
on_exit() {
  local status=$?
  trap - EXIT

  if [ "$status" -ne 0 ]; then
    echo "❌ Update GAGAL (exit $status). Memulihkan..." >&2
    record_deploy_failure

    if [ "$MAINTENANCE_ON" -eq 1 ]; then
      php artisan up 2>/dev/null || true
    fi
  fi

  exit "$status"
}
trap on_exit EXIT

echo "=== SIM-LPPM Production Update Script ==="
cd "$APP_DIR" || fail "Direktori ${APP_DIR} tidak ditemukan!"

# Cek disk space (min 1 GB)
AVAIL=$(df --output=avail "$APP_DIR" 2>/dev/null | tail -1 || true)
if [[ -n "$AVAIL" && "$AVAIL" -lt 1048576 ]]; then
  fail "Disk space < 1 GB. Tersedia: $((AVAIL/1024)) MB"
fi

# Maintenance mode ON (sebelum backup agar data konsisten)
php artisan down --retry=300
MAINTENANCE_ON=1

# Backup (ke storage/ yang aman — BUKAN ../  yang bisa terekspos)
echo "📦 Membuat backup..."
BACKUP_DIR="storage/app/backup"
mkdir -p "$BACKUP_DIR"
BACKUP_FILE="$BACKUP_DIR/backup-$(date +%Y%m%d-%H%M%S).tar.gz"
tar czf "$BACKUP_FILE" \
  --exclude="./storage/app/backup" \
  --exclude="./node_modules" \
  --exclude="./.git" \
  --exclude="./vendor" \
  --exclude="./.env" \
  --exclude="./storage/logs/*.log" \
  --exclude="./storage/framework/cache/data/*" \
  --exclude="./storage/framework/views/*" \
  --exclude="./storage/framework/sessions/*" \
  --exclude="./storage/debugbar/*" . || fail "Backup gagal! Periksa disk space."
chmod 600 "$BACKUP_FILE" 2>/dev/null || true

# Retensi backup otomatis — simpan 3 terbaru, hapus yang lama agar disk tidak penuh
KEEP_BACKUPS=3
BACKUP_COUNT=$(ls -1 "$BACKUP_DIR"/backup-*.tar.gz 2>/dev/null | wc -l || true)
if [ "$BACKUP_COUNT" -gt "$KEEP_BACKUPS" ]; then
  echo "🧹 Retensi backup: menyimpan $KEEP_BACKUPS terbaru, menghapus $((BACKUP_COUNT - KEEP_BACKUPS)) lama..."
  ls -1t "$BACKUP_DIR"/backup-*.tar.gz 2>/dev/null | tail -n +$((KEEP_BACKUPS + 1)) | while IFS= read -r OLD_BACKUP; do
    echo "  🗑️  Menghapus $OLD_BACKUP"
    rm -f "$OLD_BACKUP"
  done || true
else
  echo "Jumlah backup ($BACKUP_COUNT) masih dalam batas retensi ($KEEP_BACKUPS)."
fi

# Pull changes — JANGAN pakai git checkout/clean di produksi: perintah itu bisa
# menghapus perubahan lokal atau file untracked yang masih diperlukan.
if [ -n "$(git status --short 2>/dev/null || true)" ]; then
  echo "⚠️  Production working tree tidak bersih:" >&2
  git status --short >&2
  fail "Working tree tidak bersih. Commit/stash manual sebelum deploy."
fi

echo "Pulling latest changes..."
git pull --ff-only origin main || fail "git pull gagal! Periksa koneksi atau konflik manual, lalu jalankan: php artisan up"
DEPLOY_SHA="$(git rev-parse --short HEAD)"

# Install dependencies
echo "Installing dependencies..."
export COMPOSER_MEMORY_LIMIT=-1
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Preview migration dulu
echo "📋 Pending migrations:"
php artisan migrate --pretend --force
echo "⏳ Lanjut dalam 5 detik... (Ctrl+C untuk batal)"
sleep 5
php artisan migrate --force

# Restart queue workers agar tidak pakai kode lama
php artisan queue:restart 2>/dev/null || true

# Migrasi path file media (idempotent — aman dijalankan setiap deploy)
# File lama format {modelslug}-{id8} dipindah ke format baru {nidn}-{name}
# Jika semua file sudah di path baru, perintah ini langsung selesai tanpa error.
echo "📁 Migrasi path file media..."
php artisan media:migrate-paths --force || echo "⚠️  Migrasi media selesai dengan error (cek log)."
echo "Media migration done."

# Bersihkan record media yatim ID 97 & 99 (idempotent)
echo "🧹 Pembersihan media yatim..."
php artisan media:cleanup-orphans --ids=97,99 --force 2>/dev/null || true

# Patch missing Software TKT levels
echo "Patching Software TKT levels..."
php artisan patch:software-tkt

# Clear & rebuild caches
echo "Rebuilding caches..."
php artisan optimize:clear
# Sentry release = commit SHA (dipakai bila SENTRY_DSN aktif)
export SENTRY_RELEASE="${DEPLOY_SHA}"
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Retensi log (W4-H2.6): cegah laravel.log/browser.log membengkak di produksi
echo "Pruning logs..."
php artisan logs:prune || echo "⚠️  logs:prune selesai dengan error (cek manual)."

# Clear OPcache
php -r 'if (function_exists("opcache_reset")) { opcache_reset(); echo "OPcache cleared\n"; } else { echo "OPcache not available\n"; }'

# Flush PDF cache (baru + bersihkan legacy)
[[ -d storage/app/pdf_cache ]] && find storage/app/pdf_cache -type f -name "*.pdf" -delete 2>/dev/null || true
[[ -d storage/app/public/pdf_cache ]] && rm -rf storage/app/public/pdf_cache 2>/dev/null || true
echo "PDF cache cleaned"

# Set permissions
echo "Setting permissions..."
find . -type f -not -path './.git/*' -not -path './vendor/*' -not -path './node_modules/*' -print0 | xargs -0 chmod 644
find . -type d -not -path './.git/*' -not -path './vendor/*' -not -path './node_modules/*' -print0 | xargs -0 chmod 755
chmod +x artisan update_production.sh  # biar tetap executable
find storage bootstrap/cache -type f -print0 | xargs -0 chmod 664 2>/dev/null || true
find storage bootstrap/cache -type d -print0 | xargs -0 chmod 775 2>/dev/null || true
chmod 755 public
chmod 644 public/.htaccess
chmod 644 public/index.php
chmod 600 .env     # KRITIS: .env hanya boleh dibaca owner

# Harden backup — direktori 700 dan file 600 sesuai Security Checklist
chmod 700 storage/app/backup
find storage/app/backup -type f -print0 | xargs -0 chmod 600 2>/dev/null || true

# Test application (single line to prevent cPanel ea_php_cli.pm stat newline warning)
echo "Testing application..."
php artisan tinker --execute="\$p = App\Models\Proposal::first(); if (\$p) { echo '✓ Proposal found: ' . \$p->title . PHP_EOL; echo '✓ Status: ' . \$p->status->label() . PHP_EOL; } else { echo '✗ No proposals found' . PHP_EOL; }"

# Clear rate limiters (biar user yg kena lockout bisa login lagi)
php artisan rate-limiter:clear --force
echo "Rate limiters cleared"

# Maintenance mode OFF sebelum smoke test
php artisan up
MAINTENANCE_ON=0

# Smoke test — 6 URL kunci. Redirect 3xx wajar untuk halaman ber-auth.
SMOKE_FAIL=0
smoke_check() {
  local label="$1"
  local url="$2"
  local allow_redirect="${3:-true}"
  local code

  code=$(curl -s -o /dev/null -w "%{http_code}" --max-time 20 "$url" 2>/dev/null || true)
  code="${code:-000}"

  case "$code" in
    200)
      echo "  ✅ $label: HTTP $code"
      ;;
    301|302|307|308)
      if [ "$allow_redirect" = "true" ]; then
        echo "  ✅ $label: HTTP $code (redirect)"
      else
        echo "  ❌ $label: HTTP $code (tidak boleh redirect)"
        SMOKE_FAIL=1
      fi
      ;;
    *)
      echo "  ❌ $label: HTTP $code"
      SMOKE_FAIL=1
      ;;
  esac
}

echo "Smoke test:"
smoke_check "homepage" "$BASE_URL/"
smoke_check "login" "$BASE_URL/login" false
smoke_check "dashboard" "$BASE_URL/dashboard" true
smoke_check "research" "$BASE_URL/research" true
smoke_check "community-service" "$BASE_URL/community-service" true
smoke_check "health-check" "$BASE_URL/health-check" false

if [ "$SMOKE_FAIL" -ne 0 ]; then
  fail "Smoke test gagal — cek log sebelum menutup deploy."
fi

echo "=== ✅ Update completed successfully! ==="
echo "Test URL: $BASE_URL"

# ─────────────────────────────────────────────────────────────
# Post-deploy security checklist (otomatis)
# Semua cek di bawah ini HARUS bukan 200. Kalau 200, berarti file
# sensitif bisa diakses publik — segera perbaiki .htaccess/konfigurasi.
# ─────────────────────────────────────────────────────────────
echo ""
echo "🔒 Post-deploy security checklist:"

SECURITY_FAIL=0
check_http() {
  local label="$1"
  local url="$2"
  local code
  code=$(curl -s -o /dev/null -w "%{http_code}" --max-time 15 "$url" 2>/dev/null || true)
  code="${code:-000}"
  if [ "$code" = "200" ]; then
    echo "  ❌ $label: HTTP $code (BOCOR — harus 403/404)"
    SECURITY_FAIL=1
  else
    echo "  ✅ $label: HTTP $code"
  fi
}

check_http ".env" "$BASE_URL/.env"
check_http ".git/HEAD" "$BASE_URL/.git/HEAD"
check_http "storage/app/backup/" "$BASE_URL/storage/app/backup/"
check_http "phpinfo.php" "$BASE_URL/phpinfo.php"
check_http "database/database.sqlite" "$BASE_URL/database/database.sqlite"

# Permission file sensitif
ENV_PERM=$(stat -c '%a' .env 2>/dev/null || echo "unknown")
if [ "$ENV_PERM" = "600" ]; then
  echo "  ✅ .env permission: $ENV_PERM"
else
  echo "  ❌ .env permission: $ENV_PERM (harus 600)"
  SECURITY_FAIL=1
fi

BACKUP_DIR_PERM=$(stat -c '%a' storage/app/backup 2>/dev/null || echo "unknown")
if [ "$BACKUP_DIR_PERM" = "700" ]; then
  echo "  ✅ storage/app/backup permission: $BACKUP_DIR_PERM"
else
  echo "  ❌ storage/app/backup permission: $BACKUP_DIR_PERM (harus 700)"
  SECURITY_FAIL=1
fi

# File sensitif nyasar di root proyek
STRAY_FILES=$(ls -1 ./*.sql ./*.zip 2>/dev/null || true)
if [ -z "$STRAY_FILES" ]; then
  echo "  ✅ Tidak ada file .sql/.zip di root proyek"
else
  echo "  ❌ File sensitif di root proyek: $STRAY_FILES"
  SECURITY_FAIL=1
fi

# Error baru di log
if tail -50 storage/logs/laravel.log 2>/dev/null | grep -qiE "ERROR|exception|FATAL"; then
  echo "  ⚠️  Ada ERROR/exception di 50 baris terakhir laravel.log — periksa manual"
else
  echo "  ✅ Tidak ada ERROR/exception baru di laravel.log"
fi

if [ "$SECURITY_FAIL" -ne 0 ]; then
  fail "ADA TEMUAN KEAMANAN — perbaiki sebelum menutup deploy!"
fi

echo "🔒 Security checklist: semua aman."

# Catat deploy sukses ke dev_events (W4-H2.5). Bila gagal, deploy tetap dianggap sukses.
php artisan dev:record deploy "Deploy ${DEPLOY_SHA}" --area=produksi --status=success --ref="${DEPLOY_SHA}" 2>/dev/null || true
echo "🗂️  Dev event deploy dicatat (bila tabel dev_events tersedia)."