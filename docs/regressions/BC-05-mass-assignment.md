# BC-05 — Mass assignment / silent data loss

**Gejala:** data hilang diam-diam (qualification_snapshot tidak tersimpan); field baru tidak bisa diisi walau form mengirimnya.

**Akar masalah:** field tidak ada di `$fillable` model, sehingga `fill()`/`update()` mengabaikannya tanpa error.

**Perbaikan:** H1 — tambah `qualification_snapshot` ke `Proposal::$fillable`; M1 — tambah `description` ke `ResearchScheme::$fillable`.

**Penjaga:** `tests/Feature/Regression/BC05MassAssignmentTest.php` (fillable + persistensi mass assignment); test CRUD yang memverifikasi field tersimpan; audit `$fillable` saat review.

**Aturan:**
1. Setiap field yang bisa diisi form wajib ada di `$fillable` (atau `$guarded` eksplisit).
2. Saat menambah kolom migrasi, perbarui `$fillable` di commit yang sama.
3. Test create/update wajib mengecek field baru tidak hilang.

**Kasus:** 2026-11-12 (qualification_snapshot); 2026-11-12 (description).
