#!/bin/zsh

# PANDUAN BUKA APLIKASI SIM-LPPM (Mode Lokal)
# "Vetted by AI - Manual Review Required by Senior Engineer/Manager"

clear
echo "===================================================="
echo "   🚀 MEMULAI APLIKASI SIM-LPPM ITSNU PEKALONGAN"
echo "===================================================="
echo ""

# 1. Cek User Directory
cd "$(dirname "$0")"

# 2. Pengingat XAMPP
echo "📢 PENTING: Pastikan MySQL di XAMPP sudah status 'Running'."
echo "----------------------------------------------------"

# 3. Bersihkan Cache View (Mencegah Error Komponen Blade)
echo "🧹 Membersihkan cache view lama..."
php artisan view:clear --quiet
echo "   ✅ View cache dibersihkan."
echo ""

# 4. Jalankan Server PHP
echo "🛠️  Menjalankan Server Lokal..."
echo "🔗 Aplikasi akan tersedia di: http://127.0.0.1:8000"
echo "----------------------------------------------------"
echo "Tekan CTRL+C untuk mematikan server jika sudah selesai."
echo ""

php artisan serve
