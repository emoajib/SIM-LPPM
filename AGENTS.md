# AGENTS.md — Workflow Rules for AI Agents

## Workflow Rules (MANDATORY)

Setelah selesai mengerjakan perubahan kode, agent AI HARUS menjalankan langkah-langkah berikut secara berurutan:

### 1. Tes Kode Lokal
```bash
php artisan config:clear
php artisan test
```
- Semua test harus PASS (214 passed, 13 skipped, 1 risky = acceptable)
- Jika ada FAILURE, perbaiki sebelum lanjut

### 2. Git Commit
```bash
git add .
git commit -m "fix: deskripsi perubahan"
```
- Pre-commit hooks akan otomatis menjalankan:
  - Laravel Pint (code style)
  - PHPStan (static analysis)
- Jika hook gagal, perbaiki error lalu commit ulang

### 3. Push ke GitHub
```bash
git push origin main
```
- Gunakan `main` branch, BUKAN `develop`

### 4. Verifikasi GitHub Actions
```bash
gh run list --limit 5
```
Pastikan:
- ✅ CI — Test & Code Quality: success
- ✅ Security Audit: success
- ❌ Deploy to cPanel: failure = NORMAL (butuh SSH credentials, deploy manual)

### 5. Update Website Manual via Terminal cPanel
```bash
cd /home/simlppmi/sim-lppm

# Backup
cp -r . ../backup-$(date +%Y%m%d-%H%M%S)

# Maintenance mode ON (cegah akses selama deploy)
php artisan down --retry=300

# Pull & install
git pull origin main
composer install --no-dev --optimize-autoloader

# Migration
php artisan migrate --force

# Cache
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Flush PDF cache (akan regenerate otomatis)
find storage/app/public/pdf_cache -type f -name "*.pdf" -delete 2>/dev/null
echo "PDF cache cleaned"

# OPcache
php -r 'if (function_exists("opcache_reset")) { opcache_reset(); echo "OPcache cleared\n"; } else { echo "OPcache not available\n"; }'

# Permissions
find . -type f -print0 | xargs -0 chmod 644
find . -type d -print0 | xargs -0 chmod 755
chmod -R 775 storage bootstrap/cache
chmod 755 public
chmod 644 public/.htaccess
chmod 644 public/index.php

# Maintenance mode OFF
php artisan up
```

### 6. Verifikasi di Browser
- Test fitur yang diperbaiki
- Cek tidak ada error 500/405

## Security Rules

- JANGAN commit file `.env` yang berisi credentials
- JANGAN commit `composer.lock` jika berisi secrets
- Semua file backup harus disimpan di `storage/app/backup/` (tidak accessible publik)
- Download backup menggunakan filename dari cache, BUKAN dari URL (mencegah directory traversal)

## Code Style

- Gunakan Laravel Pint: `./vendor/bin/pint`
- Gunakan PHPStan: `./vendor/bin/phpstan analyse`
- Semua file PHP harus mengikuti PSR-12
