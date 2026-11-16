# BC-09 — Artefak copy/placeholder AI

**Gejala:** teks liar muncul di UI (mis. `ngapa` setelah breadcrumb modern); ratusan header komentar memakai marker AI generik yang tidak mencerminkan proses review.

**Akar masalah:** teks copy-paste dari tooling AI lolos ke kode tanpa guard. Tidak ada pemeriksaan artefak copy sebelum commit/CI, sehingga 416 baris di 239 file ikut terbawa.

**Perbaikan:** hapus teks liar di `resources/views/components/partials/breadcrumb.blade.php`; ganti marker header menjadi `Vetted by SOSIOMEN - Manual Review Required by Senior Engineer/Manager`; tambah command `php artisan copy:check` + daftarkan di CI.

**Penjaga:** `tests/Feature/Theme/ModernBreadcrumbTest.php`, `tests/Feature/Console/CopyCheckTest.php`, `php artisan copy:check` di `.github/workflows/tests.yml`.

**Aturan:**
1. Header komentar file wajib `Vetted by SOSIOMEN - Manual Review Required by Senior Engineer/Manager`.
2. Dilarang menambahkan marker AI generik; guard `copy:check` harus hijau sebelum commit.
3. Teks placeholder/artefak tidak boleh muncul di output UI — tambahkan test render bila menyentuh partial bersama.

**Kasus:** 2026-11-16 (breadcrumb `ngapa`; header `Vetted by AI`).