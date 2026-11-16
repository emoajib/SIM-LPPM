# Riwayat Pengembangan SIM-LPPM (2024-2026)

**Proyek:** SIM-LPPM ITSNU  
**Repository:** github.com/emoajib/SIM-LPPM (branch `main`)  
**Produksi:** https://sim-lppm.itsnupekalongan.ac.id  
**Terakhir update:** 2026-11-16

---

## Ringkasan Eksekutif

Aplikasi SIM-LPPM telah melewati transformasi besar dari sistem klasik (Livewire + Blade) ke sistem **hybrid modern** (React + Vite untuk aksi sering, Livewire untuk alur kompleks). Selama 2024-2016, **8 gelombang (Wave 0-8)** diselesaikan dengan 817 test passing, CI 4-pipeline hijau, dan deploy produksi pertama yang sukses.

---

## Timeline Per Gelombang

### Wave 0 — Fondasi (Sebelum 2026)
- Laravel 11 → 12 upgrade
- Spatie Permission + MediaLibrary setup
- Baseline test: 487 passed

### Wave 1 — Stabilisasi Lokal (2026-10 - early Nov)
- Proposal detail blank fix (map key → array)
- Active role check (`activeHasRole` bukan `hasRole`)
- Verifikasi Luaran: team-member scoping, UI role gating
- Classic redirect untuk route modern-only

### Wave 2 — Kontrak Tema (Nov 2026)
- Enum `Theme` + `is_modern_theme()` terpusat
- `theme:check`, `modern:check`, `regression:check` commands
- Registry halaman modern (`config/modern-pages.php`)
- `copy:check` (Vetted by SOSIOMEN header)
- Build verify adaptif (BC-07)

### Wave 3 — Gap Paritas Modern (Nov 2026)
- Filter sub-modul modern (status, tahun, skema, fakultas, prodi, peran)
- Status Laporan Akhir dari `meta.stats`
- ModuleSubpageIndex komponen generik
- RoleThemeSmokeTest: 8 peran × 2 tema × 9 halaman
- Kontrak React ↔ API (ModernProposalShowContractTest, ModernFilterContractTest)

### Wave 4 — Engineering Memory (Nov 2026)
- `history:brief`, `codebase:map`, `bug:capture`, `history:sync`
- `error_signatures` + `dev_events` tabel permanen
- `observability:prune`, `logs:prune` terjadwal
- `changelog:build`, `digest:weekly`, `regression:health`

### Wave 5 — Deploy Produksi (16 Nov 2026)
- **Deploy pertama**: `9b8d9724` → `6a53ca53` (61 commit, 368 file)
- Migrasi batch 38: `error_signatures`, `dev_events`
- Snapshot DB pra-migrasi (`db:snapshot --label=pre-migrate`)
- Hardened `update_production.sh`: trap EXIT, `--ff-only`, tree guard, preflight mysqldump
- Checklist keamanan 10 item otomatis
- Smoke test 6 URL + verifikasi manual 4 halaman × 2 tema
- `GITHUB_TOKEN` aktif → `history:sync` jalan
- `budget-caps:audit` lolos

### Wave 6 — Dokumentasi & Kebersihan (Nov 2026)
- Gap analysis modern vs classic
- Arsip dokumen usang
- Master plan konsolidasi
- BC registry (BC-01..BC-09)

### Wave 7 — Analisa Modernisasi Lanjutan (Nov 2026)
- `docs/plans/fase7-analisa-modernisasi.md`: 10 item → 6 dikerjakan (7A-7E), 3 ditunda, 1 dikeluarkan
- `docs/plans/backlog-modernisasi-30-47.md`: triase 16 item (#30-#47)
- Revisi: Tabler bukan lucide, DashboardIndex dihapus

### Wave 8 — Penutup (16 Nov 2026)
- Flaky test `ContractManagerTest` diperbaiki (PostgreSQL CI stabil)
- Sentry integration kode lengkap (butuh DSN di .env)
- Semua CI hijau: Test & Quality, MySQL, PostgreSQL, Security

---

## Metrik Kode (16 Nov 2026)

| Metrik | Nilai |
|---|---|
| Test | 817 passed, 13 skipped, 0 failed |
| Assertions | 2,459 |
| PHPStan Level 5 | 0 error |
| Laravel Pint | passed |
| Theme check | OK |
| Modern check | OK |
| Regression check | OK |
| Copy check | OK |
| Route cache | OK |
| CI pipelines | 4/4 hijau |

---

## Arsitektur Hybrid Modern

```
┌─────────────────────────────────────────────────────┐
│                   LAYOUT (Blade)                    │
│  - Navbar, Sidebar, Footer (shared)                 │
│  - Theme switcher (cookie + session)                │
└──────────────────┬──────────────────────────────────┘
                   │
        ┌──────────┴──────────┐
        ▼                     ▼
┌───────────────┐     ┌───────────────┐
│   Tema Klasik │     │   Tema Modern │
│  (Livewire)   │     │    (React)    │
├───────────────┤     ├───────────────┤
│ Semua alur    │     │ Index/List:   │
│ tulis kompleks│     │ - Proposal    │
│ - Create/Edit │     │ - Pengabdian  │
│ - Approval    │     │ - Verifikasi  │
│ - Surat       │     │ - Daily Note  │
│ - Settings    │     │ - Laporan     │
└───────────────┘     └───────────────┘
        │                     │
        └──────────┬──────────┘
                   ▼
┌─────────────────────────────────────────────────────┐
│              SHARED BACKEND (Laravel)               │
│  - API: /api/v1/proposals, /api/v1/verifikasi-luaran │
│  - Policy: ProposalPolicy, LaporanPolicy (BC-06)    │
│  - Auth: Spatie Permission + ActiveRoleMiddleware   │
│  - Media: Spatie MediaLibrary                       │
└─────────────────────────────────────────────────────┘
```

---

## Bug Class Registry (BC-01..BC-09)

| BC | Judul | Status | Test Penjaga |
|---|---|---|---|
| BC-01 | Theme Boundary (URL, auth, data shape) | ✅ | 9 test kelas |
| BC-02 | Active Role vs All Roles | ✅ | ActiveRoleAuthorizationTest |
| BC-03 | UI/API Drift (serialization) | ✅ | ModernProposalShowContractTest |
| BC-04 | State Machine UI (submit/approve) | ✅ | ReportSecurityAndLifecycleTest |
| BC-05 | Mass Assignment (fillable) | ✅ | BC05MassAssignmentTest |
| BC-06 | IDOR/BOLA (otorisasi) | ✅ | ModernAccessBolaTest, ReportSecurityAndLifecycleTest |
| BC-07 | Stale Build (public/build) | ✅ | BC07StaleBuildTest, DeployScriptTest |
| BC-08 | Workflow Dead End (redirect) | ✅ | ThemeParityTest |
| BC-09 | AI Copy Artifact (header) | ✅ | CopyCheckTest |

---

## Deploy Produksi — Checklist Final (16 Nov 2026)

| Item | Status | Detail |
|---|---|---|
| Kode deploy | ✅ | `6a53ca53` = `origin/main` |
| Migrasi | ✅ | Batch 38 (`error_signatures`, `dev_events`) |
| Snapshot DB | ✅ | `pre-migrate-20261116_135905.sql.gz` |
| Smoke test | ✅ | 6 URL (home, login, dashboard, research, cs, health) |
| Verifikasi manual | ✅ | 4 halaman × 2 tema (verifikasi luaran, daily-note, IKU) |
| Checklist keamanan | ✅ | 10 item (1 file sampah dibersihkan) |
| `GITHUB_TOKEN` | ✅ | `history:sync` 50 commit + 20 run |
| `budget-caps:audit` | ✅ | Index OK, 0 duplikat, 3 baris |
| Paket dev | ✅ | `laravel/boost`, `phpunit` tidak installed; `browser.log` tidak ada |
| Sentry | ⚠️ | Kode siap, butuh `SENTRY_DSN` di `.env` |
| Flaky test | ✅ | `ContractManagerTest` diperbaiki |

---

## Cara Deploy Berikutnya

```bash
# Di server cPanel
cd /home/simlppmi/sim-lppm
bash update_production.sh
```

Script menangani: backup → maintenance → git pull --ff-only → composer → migrate → cache → permission → smoke test → checklist keamanan → up.

---

## Konfigurasi Produksi (`.env`)

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://sim-lppm.itsnupekalongan.ac.id

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=simlppmi_sim_lppm
DB_USERNAME=simlppm
DB_PASSWORD=<rahasia>

# Sentry (opsional - kode sudah siap)
# SENTRY_DSN=https://xxx@sentry.io/xxx
# SENTRY_ENVIRONMENT=production
# SENTRY_RELEASE=auto-dari-deploy-script

# GitHub (untuk history:sync)
GITHUB_TOKEN=<fine-grained-token-scope:actions:read,contents:read>

# Queue
QUEUE_CONNECTION=sync

# Cache
CACHE_STORE=file
```

---

## Perintah Berguna

```bash
# Observability
php artisan history:brief modern           # ringkasan area
php artisan history:sync                   # tarik commit + CI ke dev_events
php artisan history:brief deploy           # konteks deploy
php artisan budget-caps:audit --dry-run    # cek index budget
php artisan logs:prune --dry-run           # cek rotasi log
php artisan observability:prune --dry-run  # cek retensi dev_events/error_signatures
php artisan db:snapshot --dry-run          # cek snapshot DB

# Deploy
php artisan rate-limiter:clear --force
php artisan config:clear
php artisan test                           # harus 0 failed

# Rollback
git revert <sha>                           # per commit
# restore DB dari storage/app/backup/pre-migrate-*.sql.gz
```

---

## File Penting

| File | Deskripsi |
|---|---|
| `update_production.sh` | Script deploy hardened (satu-satunya jalur) |
| `docs/plans/fase8-deploy-record.md` | Catatan deploy 16 Nov 2026 |
| `docs/plans/fase7-analisa-modernisasi.md` | Analisa modernisasi lanjutan |
| `docs/plans/backlog-modernisasi-30-47.md` | Backlog item #30-#47 |
| `docs/plans/gap-analysis-modern-classic.md` | Paritas modern vs classic |
| `docs/plans/master-plan.md` | Master plan konsolidasi |
| `docs/regressions/BC-*.md` | Dokumentasi 9 kelas bug |
| `config/modern-pages.php` | Registry halaman modern |
| `config/observability.php` | Konfigurasi error_signatures, dev_events, logs |
| `tests/Feature/Console/DeployScriptTest.php` | Guard script deploy |

---

## Kontak & Escalation

- **Repository:** github.com/emoajib/SIM-LPPM
- **Produksi:** sim-lppm.itsnupekalongan.ac.id
- **CI:** GitHub Actions (Test & Quality, MySQL, PostgreSQL, Security)
- **Monitoring:** `history:sync` (Senin 04:30), `logs:prune` (harian 04:00), `observability:prune` (harian 03:30)

---

*Dokumen ini dihasilkan otomatis dari riwayat commit, test, dan deploy pada 2026-11-16.*