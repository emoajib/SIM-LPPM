#!/bin/bash

# ==========================================
# SIM LPPM ITSNU - SAFE GO-LIVE SCRIPT
# ==========================================
# Script ini memastikan aplikasi siap diakses
# secara publik tanpa merusak tampilan.

echo "🚀 Memulai Protokol Akses Publik Aman..."

# 1. Bersihkan Proses Tunnel & Vite yang mungkin masih menyangkut
echo "🧹 Mematikan proses cloudflared dan vite yang lama..."
ps aux | grep -e "cloudflared" -e "vite" | grep -v grep | awk '{print $2}' | xargs kill -9 2>/dev/null

# 2. Hapus file 'hot' (Pemicu tampilan berantakan di publik)
if [ -f "public/hot" ]; then
    echo "🔥 Menghapus file public/hot (Penyebab styling error)..."
    rm public/hot
fi

# 3. Build Asset & Clear Cache
echo "🏗️ Membangun asset statis (Production Mode)..."
npm run build

echo "🧼 Membersihkan sistem cache Laravel..."
php artisan optimize:clear

# 4. Beri instruksi akhir
echo ""
echo "✅ PROTOKOL SELESAI!"
echo "------------------------------------------------"
echo "Sekarang silakan jalankan 2 perintah ini di jendela terminal yang BERBEDA:"
echo ""
echo "1. php artisan serve"
echo "2. cloudflared tunnel run sim-lppm-itsnu"
echo "------------------------------------------------"
echo "🌐 Aplikasi Anda akan tampil CANTIK di https://sim-lppm.itsnupekalongan.ac.id"
