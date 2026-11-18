# BC-02 — Active role vs stored role

**Gejala:** akses bocor (tombol edit/simpan muncul untuk role yang tidak berhak); superadmin melihat 0 data; dekan/kaprodi melihat data lintas unit.

**Akar masalah:** blade/controller memakai `hasRole()` (role tersimpan) bukan `activeHasRole()` (role aktif), sehingga multi-role user bisa "menyamar"; superadmin tidak diwarisi ke admin lppm.

**Perbaikan:** H2 — ganti 32× `hasRole()` → `activeHasRole()` di blade; `User::activeHasAnyRole()` delegasi ke `activeHasRole()`; tambah `superadmin` eksplisit di `ProposalIndex`, `ReportAuthorization`, `ReportData`, `ModernProposalController`.

**Penjaga:** `tests/Feature/ActiveRoleAuthorizationTest.php`; lint `->hasRole(` di `resources/views/`.

**Aturan:**
1. Di view/controller gunakan `activeHasRole()`/`activeHasAnyRole()`, bukan `hasRole()`.
2. Setiap endpoint yang filter data user wajib cek kepemilikan di controller/policy (BOLA).
3. Superadmin diwarisi ke admin lppm di semua titik global-role.

**Kasus:** 2026-11-12 (32 titik); 2026-11-13 (superadmin 0 data); 2026-11-18 (tab anggaran master data: guard `mount()` disamakan, dekan/kaprodi tidak lagi bisa CRUD kelompok/komponen).
