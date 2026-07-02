#!/bin/bash

# Fix Proposal Substance File Script
# Solusi untuk masalah: dosen belum upload file PDF substansi usulan namun sudah bisa mengajukan perbaikan usulan

PROJECT_ROOT="/Volumes/WORK/PROJECT PROTOTYPE/sim-lppm-itsnu-main"
PROPOSAL_ID="019ea101-1845-7154-ac1d-986658462a5b"

# Navigate to project directory
cd "$PROJECT_ROOT"

# Step 1: Check if substance file exists for this proposal
echo "🔍 Memeriksa proposal ID: $PROPOSAL_ID"
echo "==================================================="
php artisan proposals:fix-substance-file "$PROPOSAL_ID"

# Check if substance file exists
echo -e "\n" && echo "📋 Status atau Hasil:"
result=$(php artisan proposals:fix-substance-file "$PROPOSAL_ID" 2>&1)

if echo "$result" | grep -q "Tidak ada file substance yang ditemukan"; then
    echo "✅ Tidak ada file substance yang ditemukan - DOSEN PERLU UPLOAD PDF SUBSTANSI BARU"
elif echo "$result" | grep -q "semua file substance sudah dalam format PDF"; then
    echo "✅ Semua file substance sudah dalam format PDF"
else
    echo "📄 File non-PDF ditemukan - perlu diunduh dan dikonversi"
fi

# Check if we need to proceed with upload
if echo "$result" | grep -q "Tidak ada file substance yang ditemukan"; then
    echo "\n⚠️  DOSEN BELUM MENGUNGAH FILE PDF SUBSTANSI!"
    echo "\n🔧 SOLUSI:"
    echo "1. Proses: Jenis proposal: Peran Literasi Keuangan Hijau dalam Mendukung Implementasi Green Accounting melalui Corporate Sosial Responsibility (CSR) bagi Pelaku UMKM Kabupaten Pekalongan"
    echo "   Dosen: Nur Rokhman"
    echo "   Status: revision_submitted"
    echo ""
    echo "2. Jalankan perintah ini untuk men-download file yang diperlukan (jika ada):"
    echo "   php artisan proposals:fix-substance-file $PROPOSAL_ID --download"
    echo ""
    echo "3. Konversi file Word ke PDF (Word → Save as PDF)"
    echo "   Lokasi: /tmp/ atau direktori lain yang diinginkan"
    echo "   Nama: Lampirkan.pdf (atau nama lain)"
    echo ""
    echo "4. Upload file PDF ke server web hosting:"
    echo "   scp Lampirkan.pdf user@sim-lppm.itsnupekalongan.ac.id:/tmp/"
    echo "   (menggantikan 'user' dan path sesuai CPanel hosting)"
    echo ""
    echo "5. Jalankan replace untuk meng-upload file sebagai PDF substansi:"
    echo "   php artisan proposals:fix-substance-file $PROPOSAL_ID --pdf=/tmp/Lampirkan.pdf"
    echo ""
    echo "6. Verifikasi di browser:"
    echo "   → https://sim-lppm.itsnupekalongan.ac.id/research/proposal/$PROPOSAL_ID"
    echo "   File substansi harus muncul sebagai lampiran PDF"
    echo ""
    echo "🚀 PERINGATAN: DOSEN PERLU SEGERA UPLOAD FILE PDF untuk melanjutkan proses revisi proposal!"
else
    echo "\n✅ File sudah ada, tidak perlu upload"
fi
