# BC-08 — Workflow dead-end

**Gejala:** LPJ tidak bisa disahkan karena amandemen pending tanpa cara putuskan; tombol surat hilang saat modul off padahal surat lama harus tetap bisa diakses; halaman modern tidak punya aksi yang ada di klasik.

**Akar masalah:** alur kerja terputus (LPJ menunggu amandemen tanpa aksi di tempat yang sama); gating modul terlalu luas (route verifikasi/unduh ikut mati); paritas modern-klasik tidak lengkap.

**Perbaikan:** inline Setujui/Tolak + textarea di halaman Persetujuan Keuangan; un-gate route `letter.download`/`letter.view`/`letters.verify`; tombol dashboard surat gated `LetterService::isActive()`; cache menu via `menu_cache_version`.

**Penjaga:** `tests/Feature/FinancialApprovalTest.php`, `tests/Feature/LetterModuleGatingTest.php`.

**Aturan:**
1. Setiap state yang memblokir aksi lain wajib punya aksi penyelesaian di tempat yang sama.
2. Gating modul hanya menutup *pembuatan* baru; akses baca/unduh/verifikasi surat lama tetap hidup.
3. Perubahan flag wajib menginvalidasi cache menu (`menu_cache_version`).

**Kasus:** 2026-11-13 (LPJ–amandemen); 2026-11-12 (surat saat modul off).
