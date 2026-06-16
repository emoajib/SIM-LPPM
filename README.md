# SIM LPPM — ITSNU Pekalongan

[![CI](https://github.com/emoajib/SIM-LPPM/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/emoajib/SIM-LPPM/actions/workflows/tests.yml)
[![Security](https://github.com/emoajib/SIM-LPPM/actions/workflows/security.yml/badge.svg?branch=main)](https://github.com/emoajib/SIM-LPPM/actions/workflows/security.yml)
[![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel)](https://laravel.com)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-336791?logo=postgresql)](https://postgresql.org)
[![License](https://img.shields.io/badge/License-MIT-green)](LICENSE)

**Sistem Informasi Penelitian dan Pengabdian Masyarakat** — Institut Teknologi dan Sains Nahdlatul Ulama Pekalongan.

Platform manajemen siklus hidup penelitian dan pengabdian masyarakat: dari pengajuan usulan, review, perbaikan, catatan harian, hingga laporan akhir.

---

## Daftar Isi

- [Tech Stack](#tech-stack)
- [Arsitektur](#arsitektur)
- [Modul & Fitur](#modul--fitur)
- [Workflow Proposal](#workflow-proposal)
- [Status Machine](#status-machine)
- [Roles & Permissions](#roles--permissions)
- [Dashboard Metrics](#dashboard-metrics)
- [Feature Flags](#feature-flags)
- [PDF Export System](#pdf-export-system)
- [Digital Signatures](#digital-signatures)
- [Sistem Persuratan](#sistem-persuratan)
- [API & Integration](#api--integration)
- [Installation](#installation)
- [Testing](#testing)
- [Deployment](#deployment)
- [Changelog](#changelog)

---

## Tech Stack

### Backend
| Komponen | Teknologi |
|----------|-----------|
| **Bahasa** | PHP 8.4 |
| **Framework** | Laravel 12 (Livewire Starter Kit) |
| **UI Framework** | Livewire 3 + Flux 2.1 (Tabler wrapper) |
| **Frontend** | Alpine.js, Bootstrap 5, Tabler UI |
| **Database** | PostgreSQL 16 (prod), SQLite (test) |
| **Auth** | Laravel Fortify (login, 2FA, password reset) |
| **PDF** | barryvdh/laravel-dompdf, setasign/fpdf + fpdi |
| **File** | Spatie Media Library 11 |
| **RBAC** | Spatie Laravel Permission 6 |
| **Export** | Maatwebsite Laravel Excel 3 |
| **Storage** | Lokal (`storage/app`), Google Drive adapter |

### Development & CI
| Komponen | Teknologi |
|----------|-----------|
| **Testing** | PHPUnit (228 test, 13 skipped) |
| **Code Style** | Laravel Pint (PSR-12) |
| **Static Analysis** | PHPStan level 4 (max) |
| **CI/CD** | GitHub Actions (Test & Code Quality, Security Audit, Deploy manual) |
| **Build** | Vite 7 + Bun |
| **Package Manager** | Composer + Bun |

---

## Arsitektur

```
sim-lppm-itsnu-main/
├── app/
│   ├── Actions/              # Workflow action classes (single-responsibility)
│   │   ├── Kaprodi/          # Kaprodi approval actions
│   │   └── SubmitProposalAction.php, ApproveProposalAction.php
│   ├── Enums/                # Status enums (ProposalStatus, ReportStatus, etc.)
│   ├── Http/
│   │   ├── Controllers/      # Thin controllers (export, verification, settings)
│   │   └── Middleware/       # Role/permission middleware
│   ├── Livewire/             # Full Livewire component tree
│   │   ├── Research/         # Penelitian module
│   │   ├── CommunityService/ # Pengabdian module
│   │   ├── Dashboard/        # Role-specific dashboards
│   │   ├── Settings/         # Feature flags, PDF export, master data
│   │   └── Actions/          # Reusable workflow Livewire actions
│   ├── Models/               # Eloquent models
│   ├── Observers/            # Model observers (ProposalStatusLog, etc.)
│   ├── Services/             # Business logic (PDF, eligibility, notification)
│   └── View/Composers/       # MenuComposer (dynamic menu per role)
├── database/
│   └── migrations/           # 100+ migration files
├── resources/views/
│   ├── livewire/             # Livewire blade components
│   └── pdf/                  # PDF export templates
├── routes/
│   └── web.php               # All routes (496 lines)
└── tests/
    └── Feature/              # Feature tests (ProposalWorkflow, ReportWorkflow, etc.)
```

### Pola Arsitektur
- **Livewire Full-Stack Components** — setiap halaman adalah Livewire component dengan logic + view menyatu
- **Action Classes** — workflow logic dipisah ke action classes (`SubmitProposalAction`, `ApproveProposalAction`)
- **Service Layer** — `ProposalPdfService`, `LecturerEligibilityService`, `NotificationService`
- **Enum-Based State Machine** — `ProposalStatus::canTransitionTo()` untuk validasi transisi
- **Trait-Based Reusability** — `HasFileUploads`, `HasReportTemplates`, `WithReportApproval`, `ReportAccess`

---

## Modul & Fitur

### 1. Proposal Usulan (Penelitian & Pengabdian)
| Fitur | Detail |
|-------|--------|
| **Pengajuan** | Dosen submit usulan dengan substance file (PDF) |
| **Tim** | Multiple anggota dengan invitation + accept/reject |
| **RAB** | Rencana Anggaran Biaya per item |
| **Luaran Wajib** | Per skema proposal (jurnal, seminar, HKI, dll) |
| **Luaran Tambahan** | Optional outputs |
| **Mitra** | Untuk Pengabdian Masyarakat (wajib minimal 1 mitra) |
| **Kata Kunci** | Keywords untuk klasifikasi |
| **Riwayat Status** | ProposalStatusLog mencatat setiap perubahan status |

### 2. Validasi Kaprodi (Optional)
| Fitur | Detail |
|-------|--------|
| **Feature Flag** | `feature_kaprodi_validation` — bisa diaktifkan/dinonaktifkan |
| **Validasi** | Kaprodi memvalidasi roadmap proposal |
| **Cross-Program** | Kaprodi hanya bisa validasi proposal di program studinya |

### 3. Persetujuan Dekan
| Fitur | Detail |
|-------|--------|
| **Faculty Matching** | Dekan hanya bisa approve proposal di fakultasnya |
| **Self-Approval Prevention** | Dekan tidak bisa approve proposal miliknya sendiri |
| **Actions** | Setujui / Tolak / Kembalikan |

### 4. Reviewer System
| Fitur | Detail |
|-------|--------|
| **Assignment** | Admin LPPM menugaskan reviewer ke proposal |
| **Beban Kerja** | ReviewerWorkload — monitoring jumlah review per reviewer |
| **Review Rounds** | Multiple review rounds per proposal |
| **Scoring** | Nilai per aspek (metodologi, kebaruan, dampak, dll) |
| **Recommendation** | Disetujui / Revisi / Ditolak |
| **File Upload** | Reviewer upload file review |
| **Monitoring** | ReviewMonitoring — admin lihat progress review |

### 5. Perbaikan Usulan (Revision Flow)
| Fitur | Detail |
|-------|--------|
| **Trigger** | Kepala LPPM meminta revisi (REVISION_NEEDED) |
| **Submit Revision** | Dosen perbaiki proposal, submit ulang -> REVISION_SUBMITTED |
| **Review Revisi** | Kepala LPPM tinjau hasil revisi |
| **Decisions** | Setujui (COMPLETED) / Minta revisi lagi / Tolak |
| **No Restart** | Revisi tidak restart workflow — langsung ke Kepala LPPM |

### 6. Catatan Harian (Daily Notes / Logbook)
| Fitur | Detail |
|-------|--------|
| **Create** | Dosen buat catatan harian per tanggal |
| **Sign** | Dosen tanda tangan digital |
| **Approve** | Kepala LPPM approve catatan harian |
| **Attachment** | Terlampir di Laporan Akhir PDF |
| **Budget Validation** | 100% budget validation via daily notes sum |

### 7. Laporan Akhir (Final Report)
| Fitur | Detail |
|-------|--------|
| **Status** | DRAFT -> SUBMITTED -> APPROVED_BY_DEKAN -> APPROVED |
| **Output** | Mandatory + Additional outputs |
| **Substance** | Upload file PDF laporan akhir |
| **Budget** | Realisasi anggaran (validasi 100%) |
| **Daily Notes** | Catatan harian dilampirkan |
| **Approval** | Dekan approve -> Kepala LPPM approve |

### 8. Dashboard Analytics
| Role | Metrics |
|------|---------|
| **Dosen** | Chart tren (Penelitian/Pengabdian x Ketua/Anggota), stat usulan, stat didanai |
| **Admin LPPM** | Total proposal, total pengguna, review pending, laporan pending |
| **Kepala LPPM** | Proposal pending approval, review completion, laporan pending |
| **Eksekutif** | IKU capaian, total penelitian, total pengabdian, luaran |
| **Dekan** | Proposal approval queue, stat per fakultas |
| **Reviewer** | Review assignments, completion rate |

### 9. IKU & Luaran
| Fitur | Detail |
|-------|--------|
| **Dashboard IKU** | Capaian Indikator Kinerja Utama |
| **Verifikasi Luaran** | Admin/Kepala LPPM verifikasi luaran yang dilaporkan |
| **Output Reports** | Rekap semua luaran (wajib + tambahan) |

### 10. Laporan Institusional
| Laporan | Deskripsi |
|---------|-----------|
| **Laporan Penelitian** | Rekap semua proposal penelitian |
| **Laporan PKM** | Rekap semua proposal pengabdian |
| **Laporan Luaran** | Semua output yang dihasilkan |
| **Laporan Kerjasama Mitra** | Mitra kolaborasi |
| **Laporan IKU** | Capaian IKU |
| **Laporan Monev** | Monitoring dan evaluasi |
| **Laporan Reviewer** | Kinerja reviewer |

### 11. Sistem Persuratan
| Fitur | Detail |
|-------|--------|
| **Jenis Surat** | Tugas, Undangan, Keterangan, Pengantar, dll |
| **Template** | Per jenis surat dengan variable dinamis |
| **Approval** | Kepala LPPM approve surat |
| **Digital Signature** | Tanda tangan digital di surat |
| **Arsip** | Pengarsipan surat |

### 12. Admin & Settings
| Fitur | Detail |
|-------|--------|
| **User Management** | CRUD + Import Excel + Sinkronisasi SINTA |
| **Roles** | Dosen, Dekan, Kaprodi, Kepala LPPM, Admin LPPM, Reviewer, Rektor |
| **Master Data** | Fakultas, Prodi, Skema Penelitian/Pengabdian, dll |
| **Feature Flags** | 10+ toggle (kaprodi, persuratan, mitra wajib, dll) |
| **PDF Settings** | Cover title, subtitle, logo, approval text, tanda tangan |
| **Proposal Schedule** | Jadwal pengajuan per tahun ajaran |
| **Template Proposal** | Upload template substansi proposal |
| **Manual Book** | Panduan penggunaan aplikasi |
| **Audit Log** | Catatan perubahan pengaturan |

### 13. Digital Signatures
| Fitur | Detail |
|-------|--------|
| **Method** | Barcode QR + URL verifikasi |
| **Signatories** | Dosen (saat SUBMITTED), Dekan (saat APPROVED), Kepala LPPM (saat COMPLETED) |
| **Verification** | Signed route verification (signedRoute + expire) |
| **History** | DocumentSignature morph model |

---

## Workflow Proposal

### Flow Lengkap: Usulan -> Laporan Akhir

```
DRAFT
  |  Dosen isi proposal, tambah tim, RAB, luaran
  v
SUBMITTED
  |
  +-- [Kaprodi ON] -> Kaprodi validasi roadmap
  |
  v
APPROVED (Dekan)
  |
  +-- [Admin LPPM] -> Assign reviewer
  |
  v
WAITING_REVIEWER -> UNDER_REVIEW
  |  Reviewer memberikan nilai & rekomendasi
  v
REVIEWED
  |
  +-- COMPLETED ---------------------> (barcode Kepala LPPM muncul)
  |                                         |
  |                                         v
  |                                    CATATAN HARIAN
  |                                    (dosen isi -> sign -> LPPM approve)
  |                                         |
  |                                         v
  |                                    LAPORAN AKHIR
  |                                    DRAFT -> SUBMITTED -> DEKAN -> LPPM
  |
  +-- REJECTED (selesai)
  |
  +-- REVISION_NEEDED
        |  Dosen perbaiki proposal via Perbaikan Usulan
        v
      REVISION_SUBMITTED <-- submit ulang (skip kaprodi/eligibility)
        |
        +-- COMPLETED --------> barcode LPPM muncul
        +-- REVISION_NEEDED lagi (dosen perbaiki lagi)
        +-- REJECTED
```

### Alur Revision (Baru)

Sebelum: `REVISION_NEEDED -> SUBMITTED` (restart full workflow — Dekan, Reviewer, semuanya)

Sesudah: `REVISION_NEEDED -> REVISION_SUBMITTED` (langsung ke Kepala LPPM, tidak perlu re-review)

---

## Status Machine

### ProposalStatus (10 status + 1 new)

```
DRAFT -> SUBMITTED
SUBMITTED -> APPROVED | NEED_ASSIGNMENT | REJECTED
NEED_ASSIGNMENT -> SUBMITTED (resubmit)
APPROVED -> WAITING_REVIEWER | UNDER_REVIEW | REJECTED
WAITING_REVIEWER -> UNDER_REVIEW
UNDER_REVIEW -> REVIEWED
REVIEWED -> COMPLETED | REVISION_NEEDED | REJECTED
REVISION_NEEDED -> REVISION_SUBMITTED        <- BARU
REVISION_SUBMITTED -> COMPLETED | REVISION_NEEDED | REJECTED
COMPLETED -> (final)
REJECTED -> (final)
```

### ReportStatus
```
DRAFT -> SUBMITTED -> APPROVED_BY_DEKAN -> APPROVED
```

### ReviewStatus
```
PENDING -> COMPLETED
```

### Final States
- **Proposal**: `COMPLETED`, `REJECTED`
- **Report**: `APPROVED`

### Editable States (by dosen)
- **Proposal**: `DRAFT`, `REVISION_NEEDED`
- **Report**: `DRAFT`

---

## Roles & Permissions

Berikut role yang digunakan:

| Role | Deskripsi | Module Access |
|------|-----------|---------------|
| `dosen` | Dosen pengusul | Penelitian, Pengabdian, Catatan Harian |
| `dekan` | Dekan fakultas | Persetujuan proposal, Persetujuan laporan |
| `kaprodi` | Kepala Program Studi | Validasi roadmap proposal |
| `kepala lppm` | Kepala LPPM | Semua approval, final decision, verifikasi luaran |
| `admin lppm` | Admin LPPM | Full access: user, settings, master data, reviewer assignment |
| `reviewer` | Reviewer proposal | Review form, penilaian |
| `rektor` | Rektor | Dashboard eksekutif, IKU |
| `superadmin` | Pengembang | Sama dengan admin lppm + install |

Permission modules (diatur via feature flags):

| Permission | Deskripsi |
|------------|-----------|
| `module_penelitian` | Akses menu Penelitian |
| `module_pengabdian` | Akses menu Pengabdian |
| `module_laporan` | Akses menu Laporan |
| `module_rekognisi` | Akses menu Rekognisi |
| `module_persuratan` | Akses menu Persuratan |
| `module_ekspor_sinta` | Akses menu Ekspor SINTA |
| `module_arsip` | Akses menu Arsip |

---

## Dashboard Metrics

### Dosen Dashboard
- **Chart**: 4 lines — Penelitian (Ketua/blue-solid, Anggota/blue-light) vs Pengabdian (Ketua/orange-solid, Anggota/orange-light)
- **Cards**: Total usulan, usulan didanai, usulan pending, usulan ditolak
- **Periode**: Per tahun ajaran
- **Anggota**: Termasuk sebagai anggota (filter `role != ketua`)

### Kepala LPPM Dashboard
- **Cards**: Proposal pending approval, reviewer completion, laporan pending
- **Charts**: Tren proposal per tahun, per jenis

### Admin LPPM Dashboard
- **Cards**: Total proposal, total users, pending reviews, pending reports
- **Status pie**: Proposal by status

### Eksekutif Dashboard
- **Cards**: IKU capaian, total penelitian, total pengabdian, total luaran

---

## Feature Flags

| Flag | Default | Deskripsi |
|------|---------|-----------|
| `feature_kaprodi_validation` | false | Aktifkan validasi Kaprodi |
| `feature_kaprodi_validation_deadline` | null | Deadline validasi (hari) |
| `feature_community_partner_required` | true | Mitra wajib untuk Pengabdian |
| `feature_research_eligibility_check` | true | Cek eligibilitas dosen |
| `feature_persuratan_active` | false | Aktifkan modul Persuratan |
| `feature_persuratan_with_proposal` | false | Surat hanya untuk proposal aktif |
| `feature_daily_note_deadline` | null | Deadline catatan harian (hari) |

Semua diatur via UI Admin -> Pengaturan -> Feature Flags.

---

## PDF Export System

Sistem PDF menggunakan **barryvdh/laravel-dompdf** dengan kustomisasi:
- Kop surat dinamis (logo, alamat institusi)
- Cover page (judul, subtitle, tim peneliti)
- Signature blocks (Dosen, Dekan, Kepala LPPM) dengan barcode QR
- Footer dengan nomor halaman

### PDF Types

| Type | Route/Component | Template |
|------|----------------|----------|
| **Proposal** | `ProposalExportController` | `pdf/proposal-export.blade.php` |
| **Laporan Akhir** | `ReportExportController` | `pdf/report-export.blade.php` |
| **Laporan Progress** | (removed) | `pdf/report-export.blade.php` |
| **Catatan Harian** | `DailyNoteExportController` | `pdf/daily-notes.blade.php` |
| **Review** | `ReviewExportController` | `pdf/reviewer-form.blade.php` |
| **Surat** | `LetterController` | `pdf/letter-export.blade.php` |
| **Cover** | Partial | `pdf/partials/cover.blade.php` |
| **Kop Surat** | Shared partial | `pdf/partials/kop-surat.blade.php` |

### PDF Settings (Admin UI)
`/settings/pdf-export` — konfigurasi:
- Kop surat: logo (upload), alamat, telepon, email, website
- Cover: title, subtitle, tampilkan tim
- Approval: custom text
- Ukuran logo: custom width (px)
- Tanda tangan: nama, NIDN, jabatan

### Signature Integration
PDF proposal mencakup barcode QR digital signature untuk:
- **Dosen** — `action: submitted` (di SUBMITTED)
- **Dekan** — `action: approved` (di APPROVED)
- **Kepala LPPM** — `action: finalized` (hanya di COMPLETED)

---

## Digital Signatures

Menggunakan fitur `URL::signedRoute()` Laravel + QR Code:

1. Saat PDF di-generate, `ProposalPdfService` membuat record `DocumentSignature`
2. Record `DocumentSignature` memiliki: `id`, `signed_role`, `action`, `signed_at`
3. QR Code berisi URL signed: `signatures.verify` dengan parameter `documentSignature.id`
4. URL kedaluwarsa (default: 30 hari)
5. Saat scan QR -> halaman verifikasi menampilkan: nama penandatangan, role, timestamp

Implementasi:
- `app/Models/DocumentSignature.php` — polymorphic morph
- `app/Http/Controllers/DocumentSignatureVerificationController.php` — verifikasi
- `resources/views/pdf/proposal-export.blade.php` — QR Code render

---

## Sistem Persuratan

Modul opsional (diaktifkan via `feature_persuratan_active`).

| Fitur | Detail |
|-------|--------|
| **Jenis Surat** | Surat Tugas, Undangan, Keterangan, Pengantar, Rekomendasi |
| **Template** | Per jenis, dengan variable `{nama}`, `{judul}`, `{nidn}`, dll |
| **Buat Surat** | Manual (tanpa proposal) atau terlink (dengan proposal aktif) |
| **Approval** | Kepala LPPM approve via dashboard |
| **Signature** | QR barcode digital signature di PDF |
| **Arsip** | Search & filter per jenis, tanggal, status |
| **Nomor Surat** | Auto-increment per tahun |

---

## API & Integration

### SINTA Integration
- Ekspor data dosen ke format SINTA
- Sinkronisasi data dari SINTA
- Token-based API ke server SINTA

### Google Drive
- Backup file PDF ke Google Drive (opsional)
- `masbug/flysystem-google-drive-ext` adapter

### Health Check
- `GET /health-check` — endpoint monitoring
- Return status aplikasi + database connection

---

## Installation

### Prerequisites
```bash
PHP >= 8.2
Composer >= 2.0
PostgreSQL >= 14 (production)
Node.js >= 20 / Bun >= 1.0
```

### Local Development Setup

```bash
# 1. Clone
git clone https://github.com/emoajib/SIM-LPPM.git
cd SIM-LPPM

# 2. Install PHP dependencies
composer install

# 3. Environment
cp .env.example .env
php artisan key:generate

# 4. Database (SQLite for local)
# Edit .env: DB_CONNECTION=sqlite, DB_DATABASE=/full/path/to/database.sqlite
touch database/database.sqlite
php artisan migrate --seed

# 5. Install JS dependencies
bun install

# 6. Build assets
bun run build

# 7. Storage link
php artisan storage:link

# 8. Serve
php artisan serve
```

### Docker (optional)
```bash
# Not yet available — coming soon
```

---

## Testing

```bash
# Clear caches
php artisan rate-limiter:clear --force
php artisan config:clear

# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature

# Run with coverage (Xdebug required)
php artisan test --coverage
```

### Test Configuration
- `phpunit.xml` — SQLite in-memory database
- 228 tests, 13 skipped, 1 risky (baseline)
- Key test files:
  - `tests/Feature/ProposalWorkflowTest.php` — full lifecycle
  - `tests/Feature/ProposalSignatureTest.php` — digital signatures
  - `tests/Feature/ReportWorkflowTest.php` — report submissions
  - `tests/Feature/KaprodiApprovalTest.php` — kaprodi validation

### Code Quality
```bash
# Code style (PSR-12)
./vendor/bin/pint

# Static analysis (level 4 max)
./vendor/bin/phpstan analyse --memory-limit=2G
```

---

## Deployment

### GitHub Actions (CI)
3 workflows:

| Workflow | Trigger | Status |
|----------|---------|--------|
| **Test & Code Quality** | `push` to `main` | Auto |
| **Security Audit** | `push` to `main` | Auto (non-blocking) |
| **Deploy to cPanel** | `workflow_dispatch` | Manual |

### Manual Deploy (cPanel)
```bash
ssh user@hosting
cd /home/simlppmi/sim-lppm
bash update_production.sh
```

Script `update_production.sh`:
1. Backup (`storage/app/backup/`)
2. `php artisan down --retry=300`
3. `git pull origin main`
4. `composer install --no-dev --optimize-autoloader`
5. Preview migrations (5 detik untuk cancel)
6. `php artisan migrate --force`
7. `php artisan optimize:clear`
8. `php artisan config:cache && php artisan route:cache && php artisan view:cache`
9. Hapus PDF cache
10. OPCache reset
11. Permission fix
12. Rate limiter clear
13. `php artisan up`

---

## Changelog

### 16 Juni 2026 — v2.1.0
- **Status baru**: `REVISION_SUBMITTED` — revision tidak restart workflow
- **Barcode fix**: Kepala LPPM hanya muncul di COMPLETED (sebelumnya sejak WAITING_REVIEWER)
- **Revision flow**: REVISION_NEEDED -> REVISION_SUBMITTED -> Kepala LPPM (skip kaprodi/eligibility)
- **Hapus Progress Report**: Laporan Kemajuan dihilangkan (sesuai arahan simplifikasi)
- **Catatan LPPM**: Decision notes tersimpan ke ProposalStatusLog
- **UI revision**: Alert + modal text berbeda untuk revision review vs initial review
- **+224 tests passing**, 0 PHPStan errors

### 15 Juni 2026 — v2.0.1
- **Anggota query fix**: Filter `role != ketua` untuk akurasi data dashboard
- **Chart tren**: 4 dataset (Penelitian/Pengabdian x Ketua/Anggota)
- **Dead code cleanup**: `helpers.php` — hapus `active_can()`, `active_has_all_roles()`
- **N+1 fix**: `ProposalReviewer::latestLog()` — eager loading support

### 14 Juni 2026 — v2.0.0
- **PDF Export Settings**: Cover editor + approval custom text via Livewire UI
- **Kop-surat refactor**: Shared partial `kop-surat.blade.php`, 6 views updated
- **ModuleKey**: PDF config moduleKey injection di semua controllers
- **Bug fix**: ProposalPdfService copy-paste bug (`$deanId` -> `$lppmHeadId`)
- **Security Audit** non-blocking + Deploy workflow manual-only
- **P0 fix**: LetterService template_view null guard

### Sebelumnya
- Initial release with full proposal workflow, reviewer system, daily notes, final reports
- Dashboard analytics (Dosen, Admin, Kepala LPPM, Eksekutif)
- Digital signatures with QR code verification
- Institutional reports (IKU, Monev, Output, Mitra, Reviewer)
- Sistem Persuratan module
- SINTA integration
- User management with Excel import

---

## License

MIT — Institut Teknologi dan Sains Nahdlatul Ulama Pekalongan

---

## Kontak

**LPPM ITSNU Pekalongan**
Website: [https://lppm.itsnupekalongan.ac.id](https://lppm.itsnupekalongan.ac.id)
Email: lppm@itsnupekalongan.ac.id
