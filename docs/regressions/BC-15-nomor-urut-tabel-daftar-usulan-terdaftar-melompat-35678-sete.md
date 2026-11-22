# BC-15 — Nomor urut tabel Daftar Usulan Terdaftar melompat (3,5,6,7,8) setelah filter skema dan tombol filter ikut menempel pada baris tabel

**Gejala:** Nomor urut tabel Daftar Usulan Terdaftar melompat (3,5,6,7,8) setelah filter skema dan tombol filter ikut menempel pada baris tabel

**Akar masalah:** filterProposalTable() hanya menyembunyikan baris tanpa menomori ulang, dan selector [data-scheme] pada renderAll() ikut memilih <tr data-scheme> sehingga klik baris mengubah filter.

**Perbaikan:**
- Renumber baris yang terlihat setelah filter, batasi selector tombol ke button[data-scheme], perbarui hitungan footer + hint gulir.

**Penjaga:**
- `tests/Feature/Regression/BC15Test.php`

**Aturan:**
1. Test penjaga wajib GAGAL sebelum fix dan LULUS setelah fix.
2. Setiap perubahan terkait wajib menyebut `BC-15` di body commit.
3. Bila pola ini muncul di halaman/modul lain, periksa juga di sana (bukan hanya titik yang dilaporkan).

**Kasus:** 2026-11-22 — area `modern`
