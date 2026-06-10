#!/bin/bash

# ==========================================
# SIM LPPM - CPANEL PACKAGING SCRIPT
# ==========================================
# Script ini membuat paket ZIP untuk deployment
# ke cPanel secara otomatis dan aman.

echo "📦 Memulai proses pengemasan (Packaging)..."

# 1. Pastikan Asset Production Ter-update
echo "🏗️  Membangun asset production (Vite)..."
npm run build

# 2. Hapus ZIP lama jika ada
echo "🧹 Membersihkan file ZIP lama..."
rm -f app_deploy.zip vendor_deploy.zip

# 3. Pack Aplikasi (Tanpa Vendor, Tanpa Git)
echo "📂 Mengemas Kode Aplikasi (app_deploy.zip)..."
zip -r app_deploy.zip . -x \
    ".git/*" \
    "node_modules/*" \
    "vendor/*" \
    "storage/logs/*" \
    "storage/debugbar/*" \
    "storage/framework/cache/*" \
    "storage/framework/sessions/*" \
    "storage/framework/views/*" \
    "storage/app/public/*" \
    ".env" \
    ".DS_Store" \
    "infrastructure/*" \
    "tests/*" \
    "phpunit.xml" \
    ".phpunit.result.cache" \
    "*.log" \
    "*.zip" \
    "*.txt" \
    "*.sqlite"

# 4. Pack Vendor (Terpisah untuk kemudahan upload)
echo "🚚 Mengemas Folder Vendor (vendor_deploy.zip)..."
if [ -d "vendor" ]; then
    (cd vendor && zip -r ../vendor_deploy.zip . -x "*.DS_Store")
else
    echo "⚠️ Folder vendor tidak ditemukan!"
fi

echo ""
echo "✅ PROSES SELESAI!"
echo "------------------------------------------------"
echo "File Berikut Siap Diupload ke cPanel:"
echo "1. app_deploy.zip (~$(du -h app_deploy.zip | cut -f1))"
echo "2. vendor_deploy.zip (~$(du -h vendor_deploy.zip | cut -f1))"
echo "------------------------------------------------"
echo "Panduan Ekstrak ada di CPANEL_DEPLOYMENT.md"

# Vetted by AI - Manual Review Required by Senior Engineer/Manager
