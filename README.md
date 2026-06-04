# SIM LPPM — ITSNU Pekalongan

[![CI](https://github.com/emoajib/SIM-LPPM/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/emoajib/SIM-LPPM/actions/workflows/tests.yml)
[![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel)](https://laravel.com)

Sistem Informasi Penelitian dan Pengabdian Masyarakat — Institut Teknologi dan Sains Nahdlatul Ulama Pekalongan.

## Tech Stack

- **PHP 8.4** / **Laravel 12**
- **Livewire v4** + **Flux** / **Tabler** + **Bootstrap 5**
- **PostgreSQL** / **SQLite** (testing)
- **Pest v4** + **PHPStan level 4** + **Pint**

---

## fix: feature_community_partner_required toggle & partner modal

> Dikerjakan: 4 Juni 2026
> Status: ✅ Selesai

### Ringkasan Perubahan — 11 File

| # | File | Perubahan | Prioritas |
|---|------|-----------|-----------|
| 1 | `Edit.php:43` | Import `Setting` + validation `required` → `Setting::get(...)` conditional | 🔴 WAJIB |
| 2 | `Create.php:60` | Sama | 🔴 WAJIB |
| 3 | `FeatureFlags.php` | Property `featureCommunityPartnerRequired` + `mount()` & `updated()` handler | 🔴 WAJIB |
| 4 | `feature-flags.blade.php` | Toggle switch "Mitra Wajib (Pengabdian Masyarakat)" | 🔴 WAJIB |
| 5 | CS `dokumen-pendukung.blade.php:82` | `data-bs-toggle` → `wire:click="$dispatch('open-modal')"` | 🔴 WAJIB |
| 6 | Research `dokumen-pendukung.blade.php:82` | Sama | 🔴 WAJIB |
| 7 | `modal.blade.php:82` | `wire:key="{{ $id }}"` di root div modal | 🔴 WAJIB |
| 8 | `ProposalForm.php:1021` | Hapus guard `if (!empty(...))` — `sync()` selalu, fix detach | 🔴 WAJIB |
| 9 | `SubmitProposalAction.php` | Gate validasi partner sebelum submit (defense-in-depth) | 🔴 WAJIB |
| 10 | CS `dokumen-pendukung.blade.php:89-94` | Alert "wajib 1 mitra" conditional by setting | 🔴 WAJIB |
| 11 | `ProposalWorkflowTest.php:371` | Tambah partner ke fixture | 🔴 WAJIB |

### Analisis Keamanan Data Existing

| Skenario | Dampak | Aman? |
|----------|--------|-------|
| Draft tanpa mitra | `saveDraft()` cuma validasi title — tidak kena gate | ✅ |
| Submitted proposal | Tidak bisa diedit — tidak tersentuh | ✅ |
| Draft dengan mitra existing | Data aman, `sync()` hanya jika user hapus manual | ✅ |
| Research proposal | Tidak kena dampak (step 4 tetap `nullable`) | ✅ |
| Setting ON/OFF real-time | `Setting::get()` tanpa cache — perubahan instan | ✅ |
| Submit tanpa mitra | Di-block oleh `SubmitProposalAction` gate | ✅ |
| Hapus semua mitra lalu save | `sync([])` benar-benar detach — fix stale data | ✅ |

---

## fix: rumpun ilmu per dosen & fix student

> Dikerjakan: 4 Juni 2026
> Status: ✅ Selesai

### Ringkasan Perubahan — 14 File

| # | File | Perubahan | Prioritas |
|---|------|-----------|-----------|
| 1 | Migration baru `add_science_cluster_id_to_identities` | Tambah `science_cluster_id` nullable + FK | 🔴 WAJIB |
| 2 | `app/Models/Identity.php` | +`$fillable` `science_cluster_id`, +`scienceCluster()` relasi | 🔴 WAJIB |
| 3 | `app/Models/ScienceCluster.php` | +`identities()` relasi | ✅ NICE |
| 4 | `app/Livewire/Forms/TeamMembersForm.php:162-171` | +`study_program` & `institution` di non-manual path | 🔴 WAJIB |
| 5 | `app/Livewire/Forms/ProposalForm.php:279-286` | +`study_program` & `institution` + fallback `prodi` | 🔴 WAJIB |
| 6 | `app/Livewire/Settings/ProfileForm.php` | Property + mount + validasi + save (`is_numeric>0`) | 🔴 WAJIB |
| 7 | `resources/views/livewire/settings/profile-form.blade.php` | Dropdown Rumpun Ilmu setelah study_program | 🔴 WAJIB |
| 8 | `resources/views/pdf/proposal-export.blade.php:306,311,329` | Null safety + rumpun ilmu fallback chain | 🔴 WAJIB |
| 9 | `resources/views/pdf/report-export.blade.php:296,301,314` | Null safety + rumpun ilmu fallback chain | 🔴 WAJIB |
| 10 | `app/Actions/Proposal/ValidateTeamCompositionAction.php:56,64` | `identity?->` null safety | 🔴 WAJIB |
| 11 | `app/Services/ProposalPdfService.php:288-307` | Eager loading `scienceCluster` | 🔴 WAJIB |
| 12 | `app/Services/ProposalPdfService.php:744-754` | Eager loading `clusterLevel1`, `teamMembers.identity.studyProgram`, `submitter.identity.faculty` | 🔴 WAJIB |
| 13 | `app/Livewire/Users/Edit.php` | Admin edit user: +`science_cluster_id` property, validation, save + computed `scienceClusterOptions` | 🔴 WAJIB |
| 14 | `resources/views/livewire/users/edit.blade.php` | Admin edit user: dropdown Rumpun Ilmu setelah Program Studi | 🔴 WAJIB |

### Checklist Tambahan

| # | Item | Detail |
|---|------|--------|
| N1 | AGENTS.md post-deploy | `--retry=60` → `--retry=300` |
| N2 | ProfileForm falsy bug | `is_numeric()` + `> 0` untuk defense-in-depth |
| N3 | ProposalForm fallback `prodi` | Tambah `$student['prodi']` untuk legacy JSON key |

### Analisis Keamanan Data Existing

| Skenario | Dampak | Aman? |
|----------|--------|-------|
| Proposal Draft existing (PDF) | Fallback ke proposal cluster — sama seperti sebelumnya | ✅ |
| Proposal Submitted existing (PDF) | Fallback ke proposal cluster — sama seperti sebelumnya | ✅ |
| Proposal Draft diedit & disave | Student data terselamatkan (tidak hilang lagi) | ✅ |
| Proposal baru, dosen tanpa rumpun ilmu | Fallback ke proposal cluster | ✅ |
| Proposal baru, dosen dengan rumpun ilmu | Tampil rumpun ilmu per dosen | ✅ |
| Migration rollback | `dropConstrainedForeignId` bersih | ✅ |
| Cache PDF | Flush post-deploy, regenerate otomatis | ✅ |
| Mass assignment | Audit 10 titik — semua aman (explicit field array) | ✅ |
| Deployment window | `--retry=300` + `php artisan up` | ✅ |

---

## Deploy Manual (via cPanel)

Gunakan script berikut untuk deploy ke hosting cPanel:

```bash
cd /home/simlppmi/sim-lppm

# Backup
cp -r . ../backup-$(date +%Y%m%d-%H%M%S)

# Maintenance mode ON (cegah akses selama deploy)
php artisan down --retry=300

# Pull & install
git pull origin main
composer install --no-dev --optimize-autoloader

# Migration
php artisan migrate --force

# Cache
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Flush PDF cache (regenerate otomatis)
find storage/app/public/pdf_cache -type f -name "*.pdf" -delete 2>/dev/null

# OPcache
php -r 'if(function_exists("opcache_reset")){opcache_reset();echo"OPcache cleared\n";}else{echo"OPcache not available\n";}'

# Permissions
find . -type f -print0 | xargs -0 chmod 644
find . -type d -print0 | xargs -0 chmod 755
chmod -R 775 storage bootstrap/cache
chmod 755 public
chmod 644 public/.htaccess
chmod 644 public/index.php

# Maintenance mode OFF
php artisan up
```
