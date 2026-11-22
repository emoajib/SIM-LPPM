# BC-17 — Logo navbar tidak siluet dan terlalu kecil di tema modern

**Gejala:** Logo navbar tidak siluet dan terlalu kecil di tema modern

**Akar masalah:** logo.png gelap dengan backdrop putih solid, belum berupa siluet putih dengan efek gradasi.

**Perbaikan:**
- Siluet putih via filter brightness(0) invert(1), backdrop gradasi + blur, ukuran 56px, hover scale.

**Penjaga:**
- `tests/Feature/Regression/BC17Test.php`

**Aturan:**
1. Test penjaga wajib GAGAL sebelum fix dan LULUS setelah fix.
2. Setiap perubahan terkait wajib menyebut `BC-17` di body commit.
3. Bila pola ini muncul di halaman/modul lain, periksa juga di sana (bukan hanya titik yang dilaporkan).

**Kasus:** 2026-11-22 — area `modern`
