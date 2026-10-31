# Plan: Jadikan Data Tema Modern 100% Real

## Tujuan
Menghilangkan semua nilai hardcoded/mock pada UI tema modern (dashboard infographic + komponen React) dan memastikan setiap role melihat data real yang konsisten dengan scoping-nya, tanpa menyentuh modul/fitur yang tidak terduga.

## Ruang Lingkup (In Scope)
- `resources/views/components/dashboard/modern-infographic.blade.php` (gauge 2/3, kapsul 02/04, bulletin default, placeholder initial render)
- `resources/css/app.css` (fix stacking dropdown vs banner)
- `app/Livewire/Dashboard.php` (`loadInfographicStats`, `loadTrendData`, scoping `isLeader`, `total_luaran`)
- `resources/js/modern/DashboardIndex.jsx` (surat & prototipe hardcoded 0) — opsional/cleanup
- Tests: `tests/Feature/DashboardTest.php`, `DashboardAnalyticsTest.php`

## Di Luar Ruang Lingkup (Out of Scope)
- Modul fungsional (penelitian, PKM, surat, prototipe, KKI, kontrak) — data sudah real, tidak disentuh.
- Styling/CSS tema modern — tidak diubah.
- Database schema/migration — tidak ada perubahan skema.

## Urutan Pengerjaan (Execution Order)

### Fase 0 — Baseline & Safety (wajib sebelum apa pun)
1. `git status --short` harus bersih; kalau ada perubahan tak terduga → stash/commit dulu.
2. Jalankan baseline: `php artisan test` (catat jumlah pass).
3. Buat branch kerja: `git checkout -b fix/modern-real-data`.
4. Backup file yang akan diubah (copy ke `storage/app/backup/` lokal).
**Mitigasi:** Jika ada test gagal di baseline, hentikan — perbaiki dulu sebelum lanjut.

### Fase 1 — Perbaiki mock di blade infographic (impact tertinggi, risiko rendah)
Target file: `modern-infographic.blade.php`.

Langkah:
1. **Gauge 2 "Kemajuan Riset"**: ganti `72%` statis dengan nilai real. Opsi aman:
   - Hitung rasio proposal didanai yang sudah memiliki `ProgressReport` submitted/approved.
   - Jika data belum tersedia/reliabel → **sembunyikan gauge 2** (hapus blok) daripada menampilkan angka palsu.
   Mitigasi: pilih "sembunyikan" bila query ProgressReport belum teruji; hindari menampilkan angka tidak akurat.
2. **Gauge 3 "Penyaluran Hibah"**: ganti `85%` dengan rasio `total_dana_m` terhadap pagu/total anggaran (jika ada kolom pagu) **atau** sembunyikan gauge. Juga perbaiki `stroke-dashoffset` agar sesuai nilai (via inline style binding).
3. **Kapsul 02 "Desk Review"**: ganti `round($totalUsulan * 0.88)` dengan jumlah proposal berstatus `reviewed` (query real) atau sembunyikan.
4. **Kapsul 04 "Monev & Luaran"**: ganti `38 BASTB` dengan `MandatoryOutput::count()` yang terverifikasi (atau total luaran real).
5. **Initial placeholder** (2026, 70.4%, 54 Proposal, 38 Proposal, Rp 1.080 Jt): biarkan ditimpa JS (sudah), tapi pastikan nilai default tidak menyesatkan (ganti ke `—` atau 0).
6. **Bulletin default**: biarkan (admin-managed via localStorage); tambahkan catatan bahwa bukan data sistem. Tidak wajib diubah.

Verifikasi Fase 1:
- `php artisan test` (DashboardTest, DashboardAnalyticsTest) harus pass.
- Visual check: tidak ada angka statis yang menyesatkan.

**Mitigasi risiko:** Semua perubahan hanya di blade; tidak menyentuh controller. Jika ada regresi, revert file tunggal.

### Fase 1b — Fix UI: dropdown tertutup banner (stacking/z-index)
Target: `resources/css/app.css`, `resources/views/components/dashboard/modern-infographic.blade.php`.

Masalah: Di `/dashboard` (tema modern), menu dropdown (header/navbar atau aksi tabel) tertutup oleh banner infographic.

Langkah diagnosis:
1. Inspect element: cek stacking context dari `.sli-banner` dan ancestor (`.row`, `.col-*`, `.page-body`, `.container-xl`).
2. Pastikan tidak ada ancestor dengan `transform`/`filter`/`opacity` yang membuat stacking context baru di atas header.
3. Cek apakah dropdown `.show` benar-benar `position: absolute` dan parent-nya `overflow: visible`.

Fix kandidat (pilih minimal):
- A. Set `.sli-banner` dan parent grid-nya `z-index: 0` / `position: relative` tanpa stacking tinggi; pastikan `.page-header`/`.navbar` z-index lebih tinggi dari banner.
- B. Beri `.dropdown-menu.show` `z-index: 99999 !important` dan `position: fixed` (jika perlu) agar lepas dari parent overflow.
- C. Hapus `backdrop-filter` pada banner (sudah) karena dapat membuat stacking context aneh.

Verifikasi:
- Manual: buka `/dashboard` tema modern, klik dropdown header → menu terlihat penuh di atas banner.
- Tidak ada regresi pada modal/toast (z-index tetap di atas dropdown).

**Mitigasi risiko:** Perubahan hanya CSS; jika ada regresi visual, revert blok CSS terkait. Uji di Chrome/Firefox/Safari.

### Fase 2 — Scoping & konsistensi data (risiko sedang)
Target file: `app/Livewire/Dashboard.php`.

Langkah:
1. `total_luaran`: scope per role (untuk non-leader, filter `MandatoryOutput` by proposals milik user) atau ganti label menjadi "Luaran Institusi" agar tidak menyesatkan.
2. `isLeader`: pertimbangkan tambahkan `kaprodi` dengan scoping fakultas/prodi (jangan langsung set isLeader=true tanpa filter), agar kaprodi melihat datanya sendiri secara konsisten.
3. Perbaiki mutation query builder (clone builder sebelum whereIn) untuk keamanan.

Verifikasi Fase 2:
- Tambahkan/ubah test untuk memastikan scoping.
- Pastikan 378+ tests tetap pass.

**Mitigasi risiko:** Ubah satu fungsi per commit; jalankan test setelah tiap perubahan. Jika scoping kaprodi memicu perubahan perilaku tak terduga, revert hanya bagian kaprodi.

### Fase 3 — Cleanup komponen React (risiko rendah, opsional)
- `DashboardIndex.jsx` tidak ter-mount di produksi (tidak ada di `modernPageMap`).
- Opsi A: hapus file + import di `modern.jsx` (bersihkan dead code).
- Opsi B: biarkan (tidak berdampak runtime).
Rekomendasi: Opsi A hanya jika tim sepakat; mitigasi dengan grep memastikan tidak ada referensi lain.

### Fase 4 — Verifikasi menyeluruh
1. `php artisan test` (semua pass).
2. `./vendor/bin/pint` + `./vendor/bin/phpstan analyse`.
3. Manual: login tiap role (superadmin, admin lppm, kepala lppm, rektor, dekan, kaprodi, dosen, reviewer) dengan tema modern → cek dashboard tidak menampilkan angka statis/menyesatkan.
4. `git diff --stat` review sebelum commit.

## Mitigasi Umum (jika menyentuh modul tak terduga)
- Batasi perubahan pada file yang terdaftar di "In Scope"; jangan refactor massal.
- Setiap fase commit terpisah dengan pesan jelas (`fix: ...`, `refactor: ...`).
- Jika test gagal dan penyebab tidak jelas dalam 15 menit → revert fase tersebut, bukan patch acak.
- Gunakan `git stash` / branch terpisah per fase agar rollback mudah.
- Jangan ubah `.env`, migrasi, atau route tanpa review khusus.

## Kriteria Selesai (Definition of Done)
- [x] Tidak ada angka hardcoded di `modern-infographic.blade.php` (gauge/kapsul).
- [x] `total_luaran` konsisten dengan scoping role (institusi/dosen/kaprodi/reviewer) + label disesuaikan.
- [x] Semua test pass (473 passed, 13 skipped).
- [x] Pint & PHPStan bersih.
- [ ] Verifikasi manual per role selesai.

## Status Eksekusi
- [x] Fase 0 — Baseline
- [x] Fase 1 — Mock blade dihapus, data real (gauge 1, kapsul 02/04, placeholder)
- [x] Fase 1b — Fix dropdown tertutup banner (CSS + build asset)
- [x] Fase 2 — Scoping role: superadmin/admin variants (institusi), kaprodi (prodi), reviewer (ditugaskan), dosen (sendiri/tim), luaran scoped
- [x] Fase 3 — `DashboardIndex.jsx` **tidak dihapus** (dead code, tidak ter-mount) agar tidak menghapus pekerjaan lain yang belum di-commit
- [x] Fase 4 — Test suite, Pint, PHPStan

### Catatan
- `DashboardIndex.jsx` masih memiliki `surat`/`prototipe = 0` hardcoded, tetapi komponen ini tidak pernah di-mount (tidak ada di `modernPageMap`). Jika nanti diaktifkan, perlu endpoint/query real.
- Verifikasi visual dropdown perlu hard-refresh browser.