# LAPORAN PENGAJUAN ANGGARAN PENGEMBANGAN SISTEM INFORMASI LPPM

## Kepada: Kepala LPPM ITSNU Pekalongan

---

## 1. Pendahuluan

Laporan ini disusun sebagai dasar pengajuan anggaran untuk pengembangan dan pemeliharaan sistem informasi LPPM (SIM LPPM) yang telah dilakukan selama periode November 2026. Pengembangan ini berfokus pada modernisasi antarmuka (theme modern) yang memungkinkan dosen dan pejabat LPPM mengakses fitur-fitur manajemen penelitian dan pengabdian masyarakat dengan lebih mudah, cepat, dan aman.

## 2. Ringkasan Pekerjaan yang Telah Dilakukan

### 2.1 Perbaikan Kritis (Bug Fix)

| No | Masalah | Solusi | Dampak |
|----|---------|--------|--------|
| 1 | Navbar menu tidak terlihat di halaman Penelitian & Pengabdian | Root cause: Tailwind `.collapse` utility menimpa Tabler `.collapse`. Fix via override CSS scoped ke `body.theme-modern` | Menu navigasi kembali berfungsi untuk semua role |
| 2 | Tombol "Cari" filter tidak terlihat | Scoped preflight (unlayered) mengalahkan Tailwind utilities. Fix via `@layer` order | Filter bar berfungsi normal |
| 3 | Navbar item terpotong + horizontal scroll (overflow 67px) | CSS wrap rule di `app.css` scoped `body.theme-modern` | Tidak ada scroll horizontal di desktop & mobile |
| 4 | Filter "Sebagai Anggota" menampilkan usulan sendiri | Pivot `proposal_user.role = 'anggota'` (ketua juga tersimpan di pivot) | Filter peran bekerja akurat |
| 5 | 6 halaman sub-modul menampilkan daftar "Usulan" yang sama | Parameter `submodule` diabaikan API. Fix: implementasi filtering per submodule | Revisi, Laporan Akhir, Realisasi menampilkan data yang sesuai |
| 6 | Tab "Verifikasi Luaran" mati (href="#") | Ganti href ke `/iku/verification` (halaman modern React) | Tab fungsional untuk dosen + verifier |

### 2.2 Fitur Baru (Modern Theme)

| No | Fitur | Deskripsi |
|----|-------|-----------|
| 1 | **Filter Bar Real-time** | Filter status, tahun, skema, fakultas, prodi + pencarian multi-kolom (judul, nama dosen, NIDN) + tombol Reset. Data dari `/research/api/v1/filters` |
| 2 | **5 Tab Alur Manajemen** | Usulan → Perbaikan Usulan → Laporan Akhir → Realisasi Anggaran → Verifikasi Luaran. Konstanta `MODULE_TABS` tunggal (single source of truth) |
| 3 | **Realisasi Anggaran (Catatan Harian)** | Halaman modern untuk entri logbook aktivitas harian dengan nominal pengeluaran + upload bukti transaksi (nota/kuitansi/invoice). Auto-sync: RAB (BudgetItem) vs Realisasi (DailyNote) dengan selisih & persentase |
| 4 | **Verifikasi Luaran (IKU)** | Halaman modern: dosen update status output (status_type, author_status), verifier (admin LPPM/kepala LPPM/rektor) verify/unverify/updateRank. API endpoint baru dengan authorization per role |
| 5 | **Preview & Download PDF** | Tombol Preview PDF dan Download PDF di halaman Realisasi Anggaran |
| 6 | **Deduplikasi Kode** | 6 file JSX (138 baris masing-masing) → 1 komponen reusable `ModuleSubpageIndex` (pengurangan 807 baris duplikasi) |

### 2.3 Peningkatan Kualitas Kode

| Item | Detail |
|------|--------|
| Test suite | 487 passed, 13 skipped, 0 failed (1545 assertions) |
| Code style (Laravel Pint) | ✅ Passed |
| Static analysis (PHPStan) | ✅ 0 errors |
| CI/CD (GitHub Actions) | ✅ Semua workflow success (CI Test, Security Audit, PostgreSQL CI, MySQL Migration Test) |
| Classic theme | ✅ Zero regression — tidak ada file classic yang diubah |

## 3. Cakupan Pengembangan

### 3.1 File yang Diubah/Dibuat

| Kategori | Jumlah | Keterangan |
|----------|--------|------------|
| PHP Controller (baru) | 2 | `ModernProposalController` (update), `VerifikasiLuaranController` (baru), `DailyNoteController` (baru) |
| React JSX (baru) | 3 | `VerifikasiLuaranIndex.jsx`, `ModuleSubpageIndex.jsx`, `DailyNoteIndex.jsx` |
| React JSX (refactor) | 8 | `ProposalsIndex`, `PengamdianIndex`, 6 sub-page (deduplikasi) |
| Blade view (baru) | 2 | `verifikasi-luaran.blade.php`, `daily-note.blade.php` |
| CSS | 1 | `modern.css` (override `.collapse` + `@layer`) |
| Routes | 1 | `web.php` (API daily-note, verifikasi-luaran, route halaman modern) |
| Constants | 1 | `themeColors.js` (MODULE_TABS + VERIFIER_TAB) |

### 3.2 Modul yang Terdampak

1. **Penelitian** — Usulan, Perbaikan Usulan, Laporan Akhir, Realisasi Anggaran, Verifikasi Luaran
2. **Pengabdian Masyarakat** — Usulan, Perbaikan Usulan, Laporan Akhir, Realisasi Anggaran, Verifikasi Luaran
3. **IKU / Verifikasi Luaran** — MandatoryOutput & AdditionalOutput verification

## 4. Manfaat bagi Institusi

1. **Efisiensi**: Dosen dapat mengakses dan mengelola proposal, laporan akhir, realisasi anggaran, dan verifikasi luaran dari satu antarmuka modern yang konsisten
2. **Transparansi**: Realisasi anggaran dibandingkan dengan RAB (pagu) secara otomatis, menampilkan selisih dan persentase realisasi
3. **Akuntabilitas**: Bukti transaksi (nota/kuitansi/invoice) diunggah per entri catatan harian, memudahkan audit
4. **Kontrol Akses**: Filter per role (admin LPPM, kepala LPPM, dekan, kaprodi, dosen, reviewer) memastikan data hanya diakses oleh pihak berwenang
5. **Kualitas Kode**: Semua perubahan lulus test suite (487 tests), code style (Pint), dan static analysis (PHPStan) — meminimalkan risiko bug di produksi
6. **Zero Regression**: Tema klasik tidak tersentuh, pengguna yang masih menggunakan tema klasik tidak terpengaruh

## 5. Anggaran yang Diajukan

### 5.1 Biaya Pengembangan (Sudah Dilakukan)

| Item | Jumlah | Keterangan |
|------|--------|------------|
| Analisa & Perancangan | 1 unit | Analisa 5 tahap alur manajemen, perancangan arsitektur modern theme |
| Pengembangan Frontend (React) | 1 unit | 8 halaman modern + 3 komponen reusable + deduplikasi |
| Pengembangan Backend (Laravel) | 1 unit | 3 controller baru/update, API endpoints, authorization per role |
| Testing & Quality Assurance | 1 unit | 487 test suite, Pint, PHPStan, CI/CD verification |
| Dokumentasi | 1 unit | ADR, analisa dampak, laporan keamanan |
| **Total** | **5 unit** | |

### 5.2 Biaya Operasional (Bulanan)

| Item | Estimasi | Keterangan |
|------|----------|------------|
| Hosting & Server | Sesuai kontrak existing | Tidak ada perubahan infrastruktur |
| Domain & SSL | Sesuai kontrak existing | Tidak ada perubahan |
| Maintenance & Monitoring | Sesuai kontrak existing | Update via CI/CD pipeline |

### 5.3 Biaya Pengembangan Lanjutan (Opsional, Belum Dilakukan)

| Item | Estimasi | Keterangan |
|------|----------|------------|
| Fitur verifikasi/ubah nilai reviewer oleh admin LPPM | TBD | Memerlukan desain override layer + audit trail |
| Optimasi IdentityEligibilityAction per-scheme | TBD | Peningkatan performa endpoint eligibility |
| Halaman modern untuk monev & laporan institusional | TBD | Migrasi halaman Livewire ke React |

### 5.4 Plan: Modul Pengajuan Anggaran Hibah Internal (Kepala LPPM → Rektor)

Modul baru yang memungkinkan **Kepala LPPM mengajukan anggaran hibah internal** untuk program penelitian dan pengabdian masyarakat kepada **Rektor** untuk mendapatkan persetujuan (approval) sebelum dana dialokasikan.

#### 5.4.1 Latar Belakang

Saat ini, proses pengajuan anggaran hibah internal dari Kepala LPPM ke Rektor dilakukan secara manual (dokumen fisik/email). Modul ini akan mendigitalisasi alur tersebut sehingga:
- Kepala LPPM dapat menyusun dan mengajukan proposal anggaran hibah internal secara digital
- Rektor dapat meninjau, menyetujui, menolak, atau meminta revisi proposal anggaran
- Tracking status pengajuan anggaran dapat dipantau secara real-time
- Dokumentasi dan audit trail tercatat otomatis di sistem

#### 5.4.2 Fitur yang Direncanakan

| No | Fitur | Deskripsi | Role |
|----|-------|-----------|------|
| 1 | **Form Pengajuan Anggaran** | Form untuk Kepala LPPM menyusun proposal anggaran hibah internal: judul program, jenis (penelitian/pengabdian), jumlah dana diajukan, rincian anggaran per kategori (honor, operasional, publikasi, dll.), periode, justification | Kepala LPPM |
| 2 | **Dashboard Pengajuan** | Daftar semua pengajuan anggaran dengan status (draft, diajukan, disetujui, ditolak, revisi) + filter per jenis, tahun, status | Kepala LPPM, Rektor |
| 3 | **Approval Workflow** | Rektor dapat menyetujui, menolak, atau meminta revisi pengajuan anggaran. Setiap aksi disertai catatan/alasan | Rektor |
| 4 | **Rincian Anggaran (RAB)** | Tabel RAB per pengajuan: kategori, item, volume, harga satuan, total. Dapat di-export ke PDF/Excel | Kepala LPPM, Rektor |
| 5 | **Notifikasi** | Notifikasi otomatis ke Rektor saat ada pengajuan baru; notifikasi ke Kepala LPPM saat ada keputusan | Sistem |
| 6 | **Histori & Audit Trail** | Log semua perubahan status, siapa yang melakukan aksi, kapan, dan alasan | Admin LPPM |
| 7 | **Laporan Realisasi** | Perbandingan anggaran yang disetujui vs realisasi penggunaan dana | Kepala LPPM, Rektor |

#### 5.4.3 Alur Kerja (Workflow)

```
Kepala LPPM
    ↓ (buat draft proposal anggaran hibah internal)
    ↓ (submit ke Rektor)
Rektor
    ↓ (tinjau proposal)
    ├── Setujui → Dana dialokasikan → Status: Disetujui
    ├── Tolak → Status: Ditolak (dengan alasan)
    └── Minta Revisi → Status: Perlu Revisi → Kembali ke Kepala LPPM
```

#### 5.4.4 Estimasi Biaya Pengembangan

| Item | Estimasi | Keterangan |
|------|----------|------------|
| Analisa & Desain Database | 1 unit | Tabel baru: internal_grant_budgets, budget_items, approval_logs |
| Pengembangan Backend (Laravel) | 2 unit | Model, Controller, Service, Policy, Migration, Seeder |
| Pengembangan Frontend (React/Blade) | 2 unit | Form pengajuan, dashboard, approval interface, RAB table |
| Testing & QA | 1 unit | Unit test, feature test, authorization test |
| Dokumentasi & Training | 1 unit | Panduan penggunaan untuk Kepala LPPM dan Rektor |
| **Total** | **7 unit** | |

#### 5.4.5 Estimasi Waktu Pengerjaan

| Fase | Durasi | Keterangan |
|------|--------|------------|
| Analisa & Desain | 1 minggu | ERD, wireframe, workflow design |
| Backend Development | 2 minggu | Model, migration, controller, service, policy |
| Frontend Development | 2 minggu | Form, dashboard, approval, RAB table |
| Testing & Bug Fix | 1 minggu | Unit test, integration test, UAT |
| Deployment & Training | 1 minggu | Deploy ke produksi, training pengguna |
| **Total** | **7 minggu** | |

#### 5.4.6 Manfaat yang Diharapkan

1. **Digitalisasi proses** — eliminasi dokumen fisik dan email manual
2. **Tracking real-time** — status pengajuan dapat dipantau kapan saja
3. **Audit trail** — semua aksi tercatat otomatis (siapa, kapan, apa)
4. **Transparansi** — rincian anggaran terstruktur dan dapat diaudit
5. **Efisiensi** — proses approval lebih cepat dari manual
6. **Integrasi** — terintegrasi dengan modul penelitian/pengabdian existing (RAB, budget items)

## 6. Kesimpulan

Pengembangan ini telah menyelesaikan perbaikan kritis (navbar, filter, akses kontrol) dan implementasi fitur baru (Realisasi Anggaran, Verifikasi Luaran, 5 tab alur manajemen) di theme modern. Semua perubahan lulus quality gate (test, lint, static analysis, CI/CD) dan zero regression pada tema klasik.

Anggaran yang diajukan mencakup biaya pengembangan yang telah dilakukan serta estimasi biaya pengembangan lanjutan untuk fitur yang belum diimplementasi.

---

*Dokumen ini disusun berdasarkan commit history repository SIM-LPPM (github.com/emoajib/SIM-LPPM), branch main, periode November 2026.*