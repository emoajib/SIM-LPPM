#!/bin/bash
# update_production.sh — Script update cPanel (versi simpel)
# Vetted by AI - Manual Review Required by Senior Engineer/Manager

set -euo pipefail
trap 'echo "❌ Update GAGAL! Memulihkan..."; php artisan up 2>/dev/null; exit 1' ERR

echo "=== SIM-LPPM Production Update Script ==="
cd /home/simlppmi/sim-lppm || { echo "❌ Direktori /home/simlppmi/sim-lppm tidak ditemukan!"; exit 1; }

# Cek disk space (min 1 GB)
AVAIL=$(df --output=avail /home/simlppmi 2>/dev/null | tail -1)
if [[ -n "$AVAIL" && "$AVAIL" -lt 1048576 ]]; then
  echo "❌ Disk space < 1 GB. Tersedia: $((AVAIL/1024)) MB"
  exit 1
fi

# Maintenance mode ON (sebelum backup agar data konsisten)
php artisan down --retry=300

# Backup (ke storage/ yang aman — BUKAN ../  yang bisa terekspos)
echo "📦 Membuat backup..."
BACKUP_DIR="storage/app/backup"
mkdir -p "$BACKUP_DIR"
tar czf "$BACKUP_DIR/backup-$(date +%Y%m%d-%H%M%S).tar.gz" \
  --exclude="./storage/app/backup" \
  --exclude="./node_modules" \
  --exclude="./.git" \
  --exclude="./vendor" \
  --exclude="./storage/logs/*.log" \
  --exclude="./storage/framework/cache/data/*" \
  --exclude="./storage/framework/views/*" \
  --exclude="./storage/framework/sessions/*" \
  --exclude="./storage/debugbar/*" . || { echo "❌ Backup gagal! Periksa disk space."; exit 1; }

# Pull changes — gunakan git rm --cached agar file yg masuk gitignore tidak konflik
echo "Pulling latest changes..."
git rm --cached -r storage/backups/ 2>/dev/null || true
git checkout -- . 2>/dev/null || true
git clean -fd 2>/dev/null || true
git pull origin main || { echo "❌ git pull gagal! Periksa koneksi atau konflik manual, lalu jalankan: php artisan up"; exit 1; }

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

# Patch missing Software TKT levels
echo "Patching Software TKT levels..."
php artisan patch:software-tkt

# Clear & rebuild caches
echo "Rebuilding caches..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

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

# Harden backup — file tetap readable oleh web server untuk streaming download
chmod 755 storage/app/backup
find storage/app/backup -type f -print0 | xargs -0 chmod 644 2>/dev/null || true

# Test application
echo "Testing application..."
php artisan tinker --execute="
\$p = App\Models\Proposal::first();
if (\$p) {
    echo '✓ Proposal found: ' . \$p->title . PHP_EOL;
    echo '✓ Status: ' . \$p->status->label() . PHP_EOL;
} else {
    echo '✗ No proposals found' . PHP_EOL;
}
"

# Clear rate limiters (biar user yg kena lockout bisa login lagi)
php artisan rate-limiter:clear --force
echo "Rate limiters cleared"

# Maintenance mode OFF
trap - ERR
php artisan up

echo "=== ✅ Update completed successfully! ==="
echo "Test URL: https://sim-lppm.itsnupekalongan.ac.id"
