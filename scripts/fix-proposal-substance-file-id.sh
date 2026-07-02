#!/bin/bash

# Fix Proposal Substance File Script
# Solusi dalam Bahasa Indonesia: dosen belum upload file PDF substansi usulan namun sudah bisa mengajukan perbaikan usulan

PROJECT_ROOT="/Volumes/WORK/PROJECT PROTOTYPE/sim-lppm-itsnu-main"
PROPOSAL_ID="019ea101-1845-7154-ac1d-986658462a5b"

# Navigasi ke direktori project
cd "$PROJECT_ROOT"

echo "======================================"
echo "🔍 PENYEBAB MASALAH: File PDF Substansi Tidak Terupload"
echo "======================================"
echo ""
echo "Hasil Pemeriksaan:"
echo "==================="
php artisan proposals:fix-substance-file "$PROPOSAL_ID"

# Menyimpan hasil untuk diproses selanjutnya
hasil_pemantauan=$(php artisan proposals:fix-substance-file "$PROPOSAL_ID" 2>&1)

# Menampilkan status file
echo ""
echo "📋 STATUS FILE SUBSTANSI:"
echo "======================="
if echo "$hasil_pemantauan" | grep -q "Tidak ada file substance yang ditemukan"; then
    echo "❌ KETERANGAN: Dosen belum pernah mengupload file PDF substansi usulan"
elif echo "$hasil_pemantauan" | grep -q "semua file substance sudah dalam format PDF"; then
    echo "✅ KETERANGAN: File sudah dalam format PDF yang benar"
else
    echo "⚠️  KETERANGAN: File non-PDF ditemukan, perlu diunduh"
fi

# Menyediakan solusi lengkap
if echo "$hasil_pemantauan" | grep -q "Tidak ada file substance yang ditemukan"; then
    echo ""
    echo "🚨 PERINGATAN KRITIS:"
    echo "======================="
    echo "Dosen belum mengupload file PDF untuk proposal yang berbeda-beda:"
    echo ""
    echo "📝 DETAIL PROPOSAL:"
    echo "-----------------"
    echo "Masalah: Peran Literasi Keuangan Hijau..."
    echo "Dosen: Nur Rokhman"
    echo "Status: revision_submitted"
    echo "ID: $PROPOSAL_ID"
    echo ""
    echo "🔧 LANGKAH-LANGGAH PENYELESAIAN MASALAH:"
    echo "----------------------------------"
    echo ""
    echo "1️⃣  DOWNLAOD FILE (JIKA ADA)"
    echo "   Jalankan perintah ini terlebih dahulu:"
    echo "   $ php artisan proposals:fix-substance-file $PROPOSAL_ID --download"
    echo ""
    echo "2️⃣  KONVERSI KE PDF"
    echo "   Ubah file Word ke PDF (Word → Save as PDF)"
    echo "   Simpan di /tmp/ sebagai Lampirkan.pdf"
    echo ""
    echo "3️⃣  UPLOAD KE SERVER WEB HOSTING"
    echo "   Gunakan SCP untuk mengupload ke server hosting:"
    echo "   $ scp Lampirkan.pdf user@sim-lppm.itsnupekalongan.ac.id:/tmp/"
    echo "   (Ganti 'user' dengan username CPanel Anda)"
    echo ""
    echo "4️⃣  REPLACE FILE SUBSTANSI"
    echo "   Jalankan perintah ini untuk mengupload sebagai PDF:"
    echo "   $ php artisan proposals:fix-substance-file $PROPOSAL_ID --pdf=/tmp/Lampirkan.pdf"
    echo ""
    echo "5️⃣  VERIFIKASI"
    echo "   Cek di browser:"
    echo "   $ https://sim-lppm.itsnupekalongan.ac.id/research/proposal/$PROPOSAL_ID"
    echo "   File substansi harus muncul sebagai lampiran PDF yang sah"
    echo ""
    echo "⚠️  PENTING: Dosen harus segera mengupload file PDF untuk melanjutkan revisi!"
    echo ""
    echo "✅ JIKA BERHASIL: setelah upload, file akan otomatis ter-regenerate dengan Lampiran PDF"
    echo ""
else
    echo ""
    echo "✅ STATUS: File sudah ada, tidak perlu upload"
fi
