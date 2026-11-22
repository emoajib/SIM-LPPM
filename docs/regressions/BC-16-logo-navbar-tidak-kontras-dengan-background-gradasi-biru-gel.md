# BC-16 — Logo navbar tidak kontras dengan background gradasi biru gelap tema modern

**Gejala:** Logo navbar tidak kontras dengan background gradasi biru gelap tema modern

**Akar masalah:** logo.png gelap ditampilkan di navbar tanpa backdrop, sehingga menyatu dengan background #11244e-#254b8c.

**Perbaikan:**
- Backdrop putih rounded + padding pada img logo di scope body.theme-modern, plus penegasan font nama aplikasi.

**Penjaga:**
- `tests/Feature/Regression/BC16Test.php`

**Aturan:**
1. Test penjaga wajib GAGAL sebelum fix dan LULUS setelah fix.
2. Setiap perubahan terkait wajib menyebut `BC-16` di body commit.
3. Bila pola ini muncul di halaman/modul lain, periksa juga di sana (bukan hanya titik yang dilaporkan).

**Kasus:** 2026-11-22 — area `modern`
