#!/bin/bash
# update_production.sh — Script update cPanel (versi simpel)
# Vetted by AI - Manual Review Required by Senior Engineer/Manager

set -euo pipefail
trap 'echo "❌ Update GAGAL! Jalankan: php artisan up"; php artisan up; exit 1' ERR

echo "=== SIM-LPPM Production Update Script ==="
cd /home/simlppmi/sim-lppm

# Backup (ke storage/ yang aman — BUKAN ../  yang bisa terekspos)
echo "📦 Membuat backup..."
BACKUP_DIR="storage/app/backup"
mkdir -p "$BACKUP_DIR"
tar czf "$BACKUP_DIR/backup-$(date +%Y%m%d-%H%M%S).tar.gz" \
  --exclude="./storage/app/backup" \
  --exclude="./node_modules" \
  --exclude="./.git" \
  --exclude="./vendor" . || { echo "❌ Backup gagal! Periksa disk space."; exit 1; }

# Maintenance mode ON
php artisan down --retry=300

# Pull changes
echo "Pulling latest changes..."
git stash push -m "auto-stash-$(date +%Y%m%d-%H%M%S)" 2>/dev/null || echo "⚠️ Tidak ada perubahan lokal"
git pull origin main
git stash pop 2>/dev/null || { echo "❌ CONFLICT setelah git pull! Perbaiki manual lalu jalankan: php artisan up"; exit 1; }

# Install dependencies
echo "Installing dependencies..."
composer install --no-dev --optimize-autoloader

# Preview migration dulu
echo "📋 Pending migrations:"
php artisan migrate --pretend --force
echo "⏳ Lanjut dalam 5 detik... (Ctrl+C untuk batal)"
sleep 5
php artisan migrate --force

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
find storage/app/pdf_cache -type f -name "*.pdf" -delete 2>/dev/null
rm -rf storage/app/public/pdf_cache 2>/dev/null
echo "PDF cache cleaned"

# Set permissions
echo "Setting permissions..."
find . -type f -not -path './vendor/*' -not -path './node_modules/*' -print0 | xargs -0 chmod 644
find . -type d -not -path './vendor/*' -not -path './node_modules/*' -print0 | xargs -0 chmod 755
chmod -R 775 storage bootstrap/cache
chmod 755 public
chmod 644 public/.htaccess
chmod 644 public/index.php
chmod 600 .env     # KRITIS: .env hanya boleh dibaca owner
chmod +x update_production.sh # Agar script ini tetap bisa dieksekusi langsung

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
