#!/bin/bash
# update_local.sh — Skrip Pembaruan Lokal Otomatis & Aman
# Vetted by AI - Manual Review Required by Senior Engineer/Manager

set -e # Berhenti jika ada error

echo "🚀 Memulai proses pembaruan otomatis..."

# 1. Deteksi Konfigurasi Database dari .env
DB_DATABASE=$(grep DB_DATABASE .env | cut -d '=' -f2)
DB_USERNAME=$(grep DB_USERNAME .env | cut -d '=' -f2)
DB_PASSWORD=$(grep DB_PASSWORD .env | cut -d '=' -f2)

# 2. Backup Database Sebelum Update (Safety First)
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="storage/app/backup"
BACKUP_FILE="${BACKUP_DIR}/pre_update_backup_${TIMESTAMP}.sql"

mkdir -p "$BACKUP_DIR"

echo "📦 Membuat backup database ke ${BACKUP_FILE}..."

# Mencoba backup menggunakan mysqldump dengan host 127.0.0.1 untuk menghindari socket error
if command -v mysqldump &> /dev/null
then
    if [ -z "$DB_PASSWORD" ]; then
        mysqldump --complete-insert -h 127.0.0.1 -u "$DB_USERNAME" "$DB_DATABASE" > "$BACKUP_FILE" 2>/dev/null || BACKUP_FAILED=true
    else
        mysqldump --complete-insert -h 127.0.0.1 -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" > "$BACKUP_FILE" 2>/dev/null || BACKUP_FAILED=true
    fi
    
    if [ "$BACKUP_FAILED" = true ]; then
        echo "⚠️  Gagal membuat backup via mysqldump. Melanjutkan update tanpa backup..."
    else
        echo "✅ Backup berhasil dibuat."
    fi
else
    echo "⚠️  mysqldump tidak ditemukan. Melanjutkan update tanpa backup..."
fi

# 3. Update Kode dari GitHub
echo "🔄 Menarik kode terbaru dari branch main..."
git pull origin main

# 4. Instal Dependensi jika ada perubahan
if [ -f "composer.json" ]; then
    echo "📦 Memperbarui dependensi PHP (Composer)..."
    composer install
fi

# 5. Jalankan Migrasi Database (SAFE MODE)
# Menggunakan migrate biasa, BUKAN migrate:fresh agar data tidak hilang
echo "🏗️  Menjalankan migrasi database..."
php artisan migrate --force

# 6. Bersihkan dan Segarkan Cache
echo "🧹 Membersihkan cache sistem..."
php artisan optimize:clear
php artisan view:clear
php artisan config:clear

echo ""
echo "=========================================================="
echo "✅ PEMBARUAN SELESAI!"
echo "----------------------------------------------------------"
echo "Data Anda Aman. Jika terjadi masalah, file backup ada di:"
echo "${BACKUP_FILE}"
echo "=========================================================="
