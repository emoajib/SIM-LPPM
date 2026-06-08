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

## ⚡ Performance Audit Plan — 6 Juni 2026

**Target:** https://sim-lppm.itsnupekalongan.ac.id  
**Platform:** Laravel 12 + Livewire 4 + Tabler UI + Vite 7 + Tailwind v4  
**Hosting:** Cloudflare → Google Cloud Run (origin) / cPanel (manual deploy)  
**Infra:** Cloudflare CDN, HTTP/2, OPCache ✅, Redis ❌ (tidak dipakai), Session/Cache DB ❌  

---

### 📊 Scorecard Metrik

| Metrik | Artinya | Target BAIK | Perlu Perbaikan | BURUK | Prioritas |
|--------|---------|-------------|-----------------|-------|-----------|
| **LCP** | Konten terbesar muncul | <2.5s | 2.5-4s | >4s | 🔴 |
| **INP** | Responsivitas interaksi (pengganti FID) | <200ms | 200-500ms | >500ms | 🔴 |
| **CLS** | Kestabilan layout | <0.1 | 0.1-0.25 | >0.25 | 🟡 |
| **FCP** | Konten pertama muncul | <1.8s | 1.8-3s | >3s | 🔴 |
| **TTFB** | Server merespon | <800ms | 800-1800ms | >1800ms | 🔴 |
| **TBT** | JS block main thread | <200ms | 200-600ms | >600ms | 🟡 |
| **Speed Index** | Kecepatan isi visual | <3.4s | 3.4-5.8s | >5.8s | 🟡 |
| **TTI** | Siap diinteraksi | <3.8s | 3.8-7.3s | >7.3s | 🟡 |
| **CSS Bundle** | Ukuran CSS | <150KB | 150-300KB | >300KB | 🔴 |
| **Query/halaman** | Query DB per request | <15 | 15-30 | >30 | 🔴 |

---

### 🛠️ Toolkit Pengujian

| Tool | Link | Cara | Fungsi |
|------|------|------|--------|
| **PageSpeed Insights** | [pagespeed.web.dev](https://pagespeed.web.dev) | Online gratis | LCP, CLS, TBT, FCP, INP |
| **Lighthouse CLI** | `npm i -g lighthouse` | CLI: `lighthouse https://... --view` | Audit + CI/CD |
| **WebPageTest** | [webpagetest.org](https://webpagetest.org) | Online gratis | Waterfall, filmstrip, TTFB detail |
| **GTmetrix** | [gtmetrix.com](https://gtmetrix.com) | Online gratis | Benchmark cepat |
| **Chrome DevTools** | F12 > Performance/Network | Bawaan Chrome | Waterfall, JS profiling, layout shift |
| **web-vitals JS** | `npm i web-vitals` | Library JS | RUM (Real User Monitoring) |
| **DebugBear** | [debugbear.com](https://debugbear.com) | Berbayar $23/bln | INP, LCP breakdown |

---

### 🧪 Skenario Pengujian

| Skenario | Tool | Setup | ✅ Lulus | ❌ Gagal |
|----------|------|-------|---------|---------|
| **1. Cold Load** (first visit) | WebPageTest SG + DevTools | Incognito, disable cache, 4G throttle | LCP <4s, TTFB <1.5s, request <50 | LCP >6s, TTFB >3s |
| **2. Warm Load** (repeat) | WebPageTest Repeat View | Cache on, sudah pernah login | LCP <2s, cache hit >80% | LCP >4s, hit <50% |
| **3. Mobile Indonesia 4G** | DevTools Moto G4 | CPU 4x, RTT 150ms, 9 Mbps | Score >60, LCP <5s, TBT <600ms | Score <40, LCP >8s |
| **4. Peak Hour** | DevTools + Telescope | 4G + CPU 4x + 5 interaksi | Setiap interaksi <1s, query <20 | Interaksi >3s, query >40 |
| **5. Worst Case** (sinyal lemah) | WebPageTest Mumbai 3G | RTT 400ms, 1.5 Mbps, packet loss 3% | Muat <15s | Timeout, JS error |

---

### ✅ Checklist Audit Sistematis

**Persiapan:**
- [ ] Tutup ekstensi Chrome (adblock, VPN)
- [ ] Incognito window
- [ ] DevTools: Disable cache (cold test)
- [ ] DevTools: set network + CPU throttling
- [ ] Siapkan spreadsheet hasil

**Eksekusi (urutan):**
- [ ] 1. PageSpeed Insights → catat score
- [ ] 2. Lighthouse CLI → `lighthouse https://sim-lppm.itsnupekalongan.ac.id`
- [ ] 3. Chrome DevTools Network tab → waterfall
- [ ] 4. Chrome DevTools Performance tab → record interaksi
- [ ] 5. WebPageTest (Singapore, 4G) → filmstrip

**Data dikumpulkan:**
- [ ] Screenshot PageSpeed
- [ ] Lighthouse report (HTML/JSON)
- [ ] HAR file
- [ ] Filmstrip + waterfall screenshot
- [ ] Console log (cek error JS/404)

**Cara Baca Waterfall:**
- DNS <10ms ✅ → TCP <30ms ✅ → TLS <50ms ✅ → TTFB <800ms → Download secepatnya
- Garis merah = DOM Content Loaded, Biru = Load event
- Bar lebar biru tua = download besar (CSS/JS/images)
- Bar hijau = TTFB (jika panjang = masalah server)
- Bar kosong panjang = render blocking

---

### 🎯 Benchmark Target

| Metrik | Target Ideal | Minimum Acceptable | Jika Gagal |
|--------|-------------|-------------------|------------|
| LCP | <2.0s | <3.5s | Dosen tunggu 5-10s, kredibilitas turun |
| FCP | <1.5s | <2.5s | Persepsi "website lambat" |
| TTFB | <500ms | <1.5s | Setiap klik delay, frustrasi akumulatif |
| INP | <200ms | <350ms | Filter dropdown lambat |
| CLS | <0.1 | <0.25 | Minor (sistem form-based) |
| TBT | <200ms | <500ms | Navigasi Livewire berat |
| Cold load | <3s | <5s | Pengguna pikir website error |
| Query/halaman | <15 | <30 | DB CPU 100% jam sibuk (pengumuman hibah) |
| CSS Bundle | <150KB | <300KB | Saat ini **658KB** 🔴 |
| DB Session query | 0 | 0 | Saat ini **2 query/request** 🔴 |

**Dampak Riil:** Setiap 1s TTFB tambahan = 10-15% penurunan kepuasan. CSS 658KB = 3-5s parse di Moto G4.

---

### 🚨 Rencana Perbaikan Detail

#### 1. Database Session/Cache → File atau Redis
- 🔴 **Dampak:** Besar — 2+ query DB tambahan per request
- ⚡ **Effort:** Mudah (edit `.env`)
- 🛠️ **Solusi:** Ubah `.env`:
  ```
  SESSION_DRIVER=file       # hapus 2 query/request
  CACHE_STORE=file          # kurangi beban MySQL
  ```
- 📦 **Tools:** File bawaan Laravel (cek `df -T` dulu, jangan pakai file di NFS!)
- ⚠️ **Syarat:** `df -T /home/simlppmi/sim-lppm/storage/framework/sessions` — jika `nfs4`, request Redis dari hosting
- 📈 **Estimasi gain:** -50% s.d. -70% query DB, TTFB turun 200-600ms

#### 2. CSS Bundle 658KB → Purge & Code Split
- 🔴 **Dampak:** Besar — bundle terbesar penyebab FCP/LCP lambat
- ⚡ **Effort:** Sedang
- 🛠️ **Solusi:**
  1. Hapus di `resources/css/app.css`:
     ```
     // HAPUS 5 baris ini:
     @import '@tabler/core/dist/css/tabler-flags.min.css';
     @import '@tabler/core/dist/css/tabler-payments.min.css';
     @import '@tabler/core/dist/css/tabler-socials.min.css';
     @import '@tabler/core/dist/css/tabler-vendors.min.css';
     @import '@tabler/core/dist/css/tabler-marketing.min.css';
     @import '@tabler/core/dist/css/tabler-themes.min.css';
     ```
  2. Verifikasi: `rg 'flag-icon\|ti-flag\|payment\|ti-brand\|social-icon' resources/views/` — jika 0 match, aman
- 📈 **Estimasi gain:** CSS 658KB → ~150KB, FCP turun 1-3s di mobile

#### 3. Hapus Third-Party CDN Duplikasi & Bundle via Vite
- 🔴 **Dampak:** Sedang-besar
- ⚡ **Effort:** Mudah
- 🛠️ **Solusi:**
  1. **Inter font:** `npm install @fontsource/inter` → `@import '@fontsource/inter'` di `app.css` → hapus `<link href="https://rsms.me/inter/inter.css">`
  2. **SweetAlert2:** `npm install sweetalert2` → `import Swal from 'sweetalert2'; window.Swal = Swal` di `app.js` → hapus `<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11">`
  3. **Tabler Icons:** `npm install @tabler/icons-webfont` → `@import` di `app.css` → hapus CDN
- ⚠️ **Urutan:** Bundle dulu via Vite, **baru** hapus CDN. Ikon akan break jika terbalik!
- 📈 **Estimasi gain:** -3 HTTP request, -100KB, -3 DNS lookups

#### 4. Cache Dashboard Stats
- 🔴 **Dampak:** Besar — 15 query per load dashboard
- ⚡ **Effort:** Mudah
- 🛠️ **Solusi:**
  ```php
  $stats = Cache::remember("dashboard.admin.stats.{$year}", 180, function () {
      // query existing
  });
  ```
  Tambahkan event listener bust cache:
  ```php
  ProposalStatusLog::created(fn($log) => Cache::forget("dashboard.admin.stats.{$log->proposal->start_year}"));
  ```
- ⚠️ TTL 180s, bukan 900s — cegah data basi terlalu lama
- 📈 **Estimasi gain:** 15 query → 1 query, dashboard muat 2-5x lebih cepat

#### 5. Tambah Database Composite Indexes
- 🟡 **Dampak:** Sedang — query scan → seek
- ⚡ **Effort:** Mudah
- 🛠️ **Solusi:** Migration dengan `ALGORITHM=INPLACE LOCK=NONE` (zero downtime):
  ```php
  DB::statement('ALTER TABLE proposals ADD INDEX idx_proposals_status (status), ALGORITHM=INPLACE, LOCK=NONE');
  DB::statement('ALTER TABLE proposals ADD INDEX idx_proposals_detailable_type (detailable_type), ALGORITHM=INPLACE, LOCK=NONE');
  DB::statement('ALTER TABLE proposals ADD INDEX idx_proposals_created_at (created_at), ALGORITHM=INPLACE, LOCK=NONE');
  DB::statement('ALTER TABLE proposals ADD INDEX idx_proposals_submitter_status (submitter_id, status), ALGORITHM=INPLACE, LOCK=NONE');
  DB::statement('ALTER TABLE proposal_user ADD INDEX idx_proposal_user_status (status), ALGORITHM=INPLACE, LOCK=NONE');
  ```
- 📈 **Estimasi gain:** Query 500ms → 20ms

#### 6. JavaScript Code Splitting
- 🟡 **Dampak:** Sedang
- ⚡ **Effort:** Sulit
- 🛠️ **Solusi:**
  1. Pisahkan Vite entry: `dashboard.js`, `proposals.js`, `app-core.js`
  2. Dynamic `import()` untuk komponen Livewire jarang dipakai
  3. Hapus unused JS dependencies
- 📦 **Tools:** Vite rollupOptions.input, dynamic imports
- 📈 **Estimasi gain:** JS parse time turun 40-60%

#### 7. Resource Hints
- 🟢 **Dampak:** Kecil
- ⚡ **Effort:** Mudah
- 🛠️ **Solusi:**
  ```html
  <link rel="preconnect" href="https://api.sinta.kemdikbud.go.id">
  ```
- 📈 **Estimasi gain:** 50-150ms per koneksi baru

#### 8. TTFB & Server Optimization
- 🔴 **Dampak:** Besar
- ⚡ **Effort:** Variatif
- 🛠️ **Solusi:**
  1. ✅ OPCache enabled
  2. ✅ `config:cache`, `route:cache`, `view:cache` di deploy
  3. ✅ HTTP/2 via Cloudflare
  4. ❌ Pindahkan CACHE_STORE dari database
  5. ❌ Uncomment `trustProxies(at: '*')` — perbaiki URL generation
  6. ❌ Pertimbangkan Laravel Octane untuk Cloud Run
- 📈 **Estimasi gain:** TTFB turun 200-1000ms

---

### ⚡ Quick Wins (< 2 Jam)

| # | Perbaikan | Waktu | Dampak | Syarat |
|---|-----------|-------|--------|--------|
| 1 | Tambah database indexes (5 index) | 10 menit | 🔴 Tinggi | Zero downtime |
| 2 | Uncomment `trustProxies(at: '*')` | 2 menit | 🟡 Sedang | Zero risk |
| 3 | Hapus 5 import CSS Tabler unused | 15 menit | 🔴 Tinggi | Verifikasi dengan rg |
| 4 | Bundle Inter font via Vite | 15 menit | 🟡 Sedang | npm install |
| 5 | Bundle SweetAlert2 via Vite | 15 menit | 🔴 Tinggi | npm install |
| 6 | Bundle Tabler Icons via Vite | 15 menit | 🔴 Tinggi | npm install |
| 7 | Cache dashboard TTL 180s + listener | 30 menit | 🔴 Tinggi | Event listener |
| 8 | `CACHE_STORE=file` | 10 menit | 🟡 Sedang | Backup DB dulu |
| 9 | Verifikasi OPCache config | 5 menit | 🟡 Sedang | Read-only |
| 10 | Cek QUEUE_CONNECTION production | 2 menit | 🟡 Sedang | Read-only |

---

### 🔴 Analisis Keamanan — Fase Eksekusi

| Perubahan | Risiko | Dampak Data | Mitigasi |
|-----------|--------|-------------|----------|
| **`SESSION_DRIVER=file`** | 🔴 HIGH — 200+ user logout paksa, draft Livewire hilang, form 419 | Data proposal AMAN (di DB), sesi user HILANG | Maintenance mode WAJIB + pengumuman 24 jam |
| **`CACHE_STORE=file`** | 🟡 MEDIUM — Spatie cache cold, `@can()` query DB | Data AMAN, performa turun sementara | `php artisan permission:cache-reset` setelahnya |
| **Hapus Tabler Icons CDN** | 🔴 HIGH — SEMUA icon break jika belum bundle via Vite | Data AMAN, UI RUSAK TOTAL | Bundle via Vite DULU, baru hapus CDN |
| **Hapus SweetAlert2 CDN** | 🔴 HIGH — Semua notifikasi/konfirmasi gagal | Data AMAN, UX RUSAK TOTAL | Bundle via Vite DULU, baru hapus CDN |
| **Tambah DB indexes** | 🟢 LOW — Online DDL, zero downtime | Data AMAN | ✅ Langsung eksekusi |
| **trustProxies** | 🟢 LOW — Perbaiki URL generator | Data AMAN | ✅ Langsung eksekusi |
| **Dashboard caching** | 🟡 MEDIUM — Data basi 3 menit | Data AMAN, tampilan bisa basi | Event listener bust cache |

---

### 📈 Monitoring Jangka Panjang

| Tool | Fungsi | Frekuensi | Biaya |
|------|--------|-----------|-------|
| **PageSpeed Insights API** | Skor harian | 1x/hari via cron | Gratis |
| **web-vitals JS** (RUM) | Real user metrics | Setiap kunjungan | Gratis |
| **UptimeRobot** | Uptime + response time | Every 5 menit | Gratis |
| **Lighthouse CI** | Regression check | Setiap push CI/CD | Gratis |
| **DebugBear** | INP, LCP breakdown | Mingguan | $23/bln |

**Alert WAJIB:**
- 🔴 LCP >4s → Slack/Telegram
- 🔴 TTFB >2s → kemungkinan server/database overload
- 🟡 Error rate >1% → 500/502/504
- 🟡 Queue backlog >100 → worker stuck

**CI/CD Integration (GitHub Actions):**
```yaml
- name: Lighthouse CI Audit
  run: |
    npm i -g @lhci/cli
    lhci autorun --config=lighthouserc.js
```
```js
// lighthouserc.js
module.exports = {
  ci: {
    collect: { url: ['https://sim-lppm.itsnupekalongan.ac.id'] },
    assert: {
      assertions: {
        'categories:performance': ['error', {minScore: 0.6}],
        'lcp': ['error', {maxNumericValue: 4000}],
        'ttfb': ['error', {maxNumericValue: 1500}],
      }
    }
  }
};
```

---

### 📋 Ringkasan Eksekutif

**"SIM LPPM ITSNU sudah punya arsitektur yang benar (Laravel 12, Vite 7, Tailwind). Tapi production membunuh performa sendiri dengan 3 kesalahan fundamental:"**

| # | Masalah | Solusi | Gain |
|---|---------|--------|------|
| **🔴 1** | **Database sebagai Session, Cache, Queue** — 2-5 query DB tambahan per request | `SESSION_DRIVER=file`, `CACHE_STORE=file`. **5 menit, tanpa kode.** | TTFB turun 200-600ms, query DB turun 70% |
| **🔴 2** | **CSS bundle 658KB** — import Tabler flags/payments/socials/marketing/themes yang tidak dipakai + font/icons dari CDN duplikasi | Hapus 5 import CSS, bundle font/icons/Swal2 via Vite. **45 menit.** | FCP turun 1-3s, download 500KB lebih sedikit |
| **🔴 3** | **Dashboard 15 query tanpa caching** — setiap load + interaksi filter query ulang semua aggregate | `Cache::remember()` TTL 180s + event listener. **30 menit.** | Dashboard muat 2-5x lebih cepat |

---

### ✅ Prioritas Eksekusi

```
HARI INI (Fase 1 — Zero Risk):
  [ ] 1. Tambah database indexes (5 index, zero downtime)
  [ ] 2. Uncomment trustProxies(at: '*')
  [ ] 3. Hapus CSS Tabler unused (flags, payments, socials, marketing, themes)
  [ ] 4. Bundle Inter font via Vite
  [ ] 5. Bundle SweetAlert2 via Vite
  [ ] 6. Bundle Tabler Icons via Vite
  [ ] 7. Cache dashboard TTL 180s + event listener

MINGGU INI (Fase 2 — Deploy Window):
  [ ] 8. CACHE_STORE=file + php artisan permission:cache-reset
  [ ] 9. Hapus CDN Tabler Icons (setelah #6)
  [ ] 10. Hapus CDN SweetAlert2 (setelah #5)

DIRENCANAKAN (Fase 3 — Maintenance Mode + Pengumuman):
  [ ] 11. SESSION_DRIVER=file (backup DB + php artisan down + pengumuman 24 jam)
```

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

## fix: lecturer signed_at proposal PDF & Pekalongan date

> Dikerjakan: 6 Juni 2026
> Status: ✅ Phase 1a Selesai, 🔄 Phase 1b Siap Eksekusi

### Ringkasan — 3 Phase

| Phase | Fokus | Status |
|-------|-------|--------|
| **1a** | Fix `signed_at` timestamp + "Pekalongan, date" di proposal PDF | ✅ Selesai |
| **1b** | Fix "Pekalongan, date" di report-export + daily-notes, cleanup | 🟡 Siap eksekusi |
| **1c** | Code quality (dead code, redundant query, .gitignore, env template) | 🟢 Opsional |

---

### ✅ Phase 1a — Selesai (commit `b034cfe`)

| # | File | Perubahan | Prioritas |
|---|------|-----------|-----------|
| 1 | `app/Services/ProposalPdfService.php:554` | `->latest('at')` di query `ProposalStatusLog` — ambil submission log terakhir | 🔴 WAJIB |
| 2 | `resources/views/pdf/proposal-export.blade.php:757` | "Pekalongan, {date}" pakai `$lecturerSig->signed_at` (submission timestamp) bukan `date('d F Y')` | 🔴 WAJIB |
| 3 | `resources/views/pdf/proposal-export.blade.php:134-135` | Pindah definisi `$lecturerSig` + `$statusValue` ke header `@php` block (biar bisa dipakai di line 757) | 🔴 WAJIB |
| 4 | `resources/views/pdf/proposal-export.blade.php:777` | Hapus duplikasi definisi `$lecturerSig` di footer | ✅ NICE |
| 5 | `tests/Feature/ProposalSignatureTest.php` | 3 test baru: status log timestamp, fallback created_at, latest submission log | 🔴 WAJIB |

### Root Cause

`ProposalPdfService.php:554` menggunakan `$proposal->created_at` (waktu draft dibuat) sebagai `signed_at` di `DocumentSignature`. Harusnya pakai timestamp dari `ProposalStatusLog` ketika proposal di-submit (`status_after = submitted`).

### 🔄 Phase 1b — Siap Eksekusi

| # | File | Perubahan | Prioritas |
|---|------|-----------|-----------|
| 1 | `resources/views/pdf/report-export.blade.php:747` | Ganti `$report->updated_at` → `$lecturer_signed_at` (sudah di-pass dari `ProposalPdfService.php:795`) | 🔴 WAJIB |
| 2 | `resources/views/pdf/daily-notes.blade.php:320` | Ganti `date('d F Y')` → `$proposal->logbook_signed_at ?? now()->format('d F Y')` | 🔴 WAJIB |
| 3 | `app/Http/Controllers/ProposalExportController.php:248-327` | Hapus dead code `upsertProposalSignatures()` — method tidak pernah dipanggil | 🟡 BAIK |
| 4 | `app/Services/ProposalPdfService.php:300-321` | Tambah `'sdgs'` ke eager loading di `export()` — cegah N+1 | 🟡 BAIK |
| 5 | `app/Services/ProposalPdfService.php:770-781` | Tambah `'sdgs'` + `'outputs'` ke eager loading di `exportReport()` | 🟡 BAIK |
| 6 | `storage/framework/cache/data/.gitignore` | Buat file agar file cache tidak ter-commit ke git | 🟡 BAIK |
| 7 | `.env.cpanel.example` | Tambah `TELESCOPE_ENABLED=false`, `DEBUGBAR_ENABLED=false`, `INSTALLER_ENABLED=false` | 🟡 BAIK |

### 🟢 Phase 1c — Opsional

| # | File | Perubahan | Prioritas |
|---|------|-----------|-----------|
| 1 | `app/Services/ProposalPdfService.php:261` | Hapus `$lecturerSignedAt` redundant — duplikasi query dari `createProposalSignatures()` line 554 | 🟢 NICE |
| 2 | `app/Services/ProposalPdfService.php:332-334` | Hapus dead view variables `dean_signed_at`, `lppm_signed_at`, `lecturer_signed_at` — tidak dipakai di blade | 🟢 NICE |
| 3 | `tests/Feature/ProposalSignatureTest.php` | Tambah test untuk report-export date + daily-notes date | 🟢 NICE |

### 🔵 Phase 2-4 — Optimasi Performa (Ditunda)

| Phase | Fokus | Detail |
|-------|-------|--------|
| **2** | Queue async | `QUEUE_CONNECTION=database` + cron `queue:work --queue=default,media --stop-when-empty --timeout=120` — bebaskan email dari blocking request |
| **3** | PDF queue | Job `GenerateProposalPdf` + `GenerateReportPdf` + `set_time_limit(300)` + `memory_limit(512M)` — cegah timeout PDF |
| **4** | Cache/Session file | `CACHE_STORE=file`, `SESSION_DRIVER=file` — kurangi beban MySQL |

### Analisis Keamanan

| Skenario | Dampak | Aman? |
|----------|--------|-------|
| Proposal dengan multiple submission | `->latest('at')` ambil log terakhir | ✅ |
| Proposal tanpa status log (draft) | Fallback `$proposal->created_at` | ✅ |
| Report tanpa `submitted_at` | Fallback `$report->created_at` → `now()` | ✅ |
| Daily notes belum di-sign | Fallback `now()->format('d F Y')` | ✅ |
| Dead code `upsertProposalSignatures` | Tidak dipanggil — aman dihapus ✅ | ✅ |
| Eager loading `sdgs`/`outputs` | Tambah query join — kurangi N+1 ✅ | ✅ |
| .gitignore cache/data | Cegah file cache ter-commit ✅ | ✅ |
| Env template production | Defense-in-depth matikan debug tools ✅ | ✅ |

---

## fix: audit log filters live updates

> Dikerjakan: 8 Juni 2026
> Status: ✅ Selesai

### Ringkasan Perubahan — 2 File

| # | File | Perubahan | Prioritas |
|---|------|-----------|-----------|
| 1 | `app/Livewire/Settings/AuditLog.php` | Menambahkan komentar kepatuhan vetting AI. | 🟢 INFO |
| 2 | `resources/views/livewire/settings/audit-log.blade.php` | Mengubah deferred `wire:model` menjadi `wire:model.live` agar filter berjalan instan pada UI. | 🔴 WAJIB |

---

## fix: validasi RAB (budget) wajib sebelum submit proposal

> Dikerjakan: 8 Juni 2026
> Status: ✅ Selesai

### Root Cause

`SubmitProposalAction::execute()` tidak memvalidasi keberadaan RAB/budget items. Proposal bisa di-submit ke status `submitted` tanpa memiliki satupun `budget_items`. Validasi budget_items hanya ada di wizard step 3 form create, yang bisa dilewati user.

### Ringkasan Perubahan — 5 File

| # | File | Perubahan | Prioritas |
|---|------|-----------|-----------|
| 1 | `app/Livewire/Actions/SubmitProposalAction.php:112` | Tambah validasi `budgetItems()->count() === 0` — blokir submit jika RAB kosong | 🔴 WAJIB |
| 2 | `app/Livewire/Research/Proposal/SubmitButton.php:62` | Tambah `&& budgetItems()->count() > 0` di `canSubmit()` — disable tombol UI | 🔴 WAJIB |
| 3 | `app/Livewire/CommunityService/Proposal/SubmitButton.php:62` | Sama untuk Community Service | 🔴 WAJIB |
| 4 | `tests/Feature/ProposalWorkflowTest.php` | Tambah helper `addBudgetItem()` + panggil di 7 test yang submit | 🔴 WAJIB |
| 5 | `tests/Feature/KaprodiApprovalTest.php:454` | Tambah budget items di 1 test yang submit | 🔴 WAJIB |

### Analisis Keamanan Data Existing

| Skenario | Dampak | Aman? |
|----------|--------|-------|
| Draft tanpa RAB → submit | Diblokir validasi baru | ✅ |
| Draft dengan RAB → submit | Lolos seperti biasa | ✅ |
| NEED_ASSIGNMENT tanpa RAB → submit | Diblokir, tapi user bisa edit & tambah RAB (Policy sudah allow edit) | ✅ |
| NEED_ASSIGNMENT dengan RAB → submit | Lolos | ✅ |
| REVISION_NEEDED tanpa RAB → submit | Diblokir, user bisa edit & tambah RAB | ✅ |
| Proposal SUBMITTED/APPROVED existing | Tidak tersentuh (tidak lewat SubmitProposalAction) | ✅ |
| Dekan approve/reject | Pakai `DekanApprovalAction` (beda class) — tidak kena dampak | ✅ |

---

## fix: perbaiki tata letak filter dashboard rektor/dekan

> Dikerjakan: 8 Juni 2026
> Status: ✅ Selesai — commit `1419e4f`

### Root Cause

Filter section di `exec-dashboard.blade.php` menampilkan 7 dropdown sejajar dalam satu baris tanpa hirarki visual, tidak konsisten dengan pola `page-header` yang digunakan di `monev-dashboard.blade.php`, serta tidak ada indikator filter aktif atau tombol reset.

### Ringkasan Perubahan — 2 File

| # | File | Perubahan | Prioritas |
|---|------|-----------|-----------| 
| 1 | `app/Livewire/Dashboard/ExecDashboard.php` | Tambah computed property `getActiveFilterCountProperty()` + method `resetFilters()` | 🔴 WAJIB |
| 2 | `resources/views/livewire/dashboard/exec-dashboard.blade.php` | Refactor filter ke 2-tier: filter primer (Tahun) di page-header + filter lanjutan dalam collapsible panel | 🔴 WAJIB |

### Perubahan UX

| Aspek | Sebelum | Sesudah |
|-------|---------|---------|
| Layout | 7 dropdown berjejer satu baris, bisa meluap | Page-header terstruktur + collapsible panel |
| Filter primer | Setara semua | Tahun selalu tampil di header |
| Filter lanjutan | Semua tampil sekaligus | Semester, Status, Fakultas, Prodi, Skema dalam panel collapse |
| Indikator aktif | ❌ Tidak ada | ✅ Badge angka di tombol toggle |
| Reset filter | ❌ Tidak ada | ✅ Tombol merah muncul otomatis jika ada filter aktif |
| Auto-expand | ❌ Tidak ada | ✅ Panel terbuka otomatis jika ada filter aktif |

---

## fix: perbaiki tata letak laporan kerjasama mitra

> Dikerjakan: 8 Juni 2026
> Status: ✅ Selesai — commit `d4fec73`

### Root Cause

`partner-collaboration.blade.php` memiliki 7 masalah layout yang berbeda dibanding `research.blade.php` sebagai referensi: filter bar di posisi salah, KPI cards desain tidak seragam, typo class icon, tombol reset tidak konsisten, dan penggunaan `card-header` yang salah konteks.

### Ringkasan Perubahan — 1 File (7 Perbaikan)

| # | File | Perubahan | Prioritas |
|---|------|-----------|-----------| 
| 1 | `resources/views/livewire/reports/partner-collaboration.blade.php` | Restrukturisasi penuh — 7 perbaikan layout & UX | 🔴 WAJIB |

### Detail 7 Perbaikan

| # | Masalah | Perbaikan |
|---|---------|-----------|
| ① | Filter bar di bawah kartu validasi (urutan tidak logis) | Filter dipindahkan ke atas, sebelum kartu validasi |
| ② | Summary cards & filter tidak dalam `container-xl` | Dibungkus `container-xl` bersama kartu validasi |
| ③ | KPI cards desain tidak seragam (border indigo semua, tanpa progress bar) | Adopsi desain `research.blade.php`: progress bar berwarna, ikon 56×56px |
| ④ | Bug typo class icon: `ti-chart-dotsfs-2` (spasi hilang) | Fix: `ti ti-chart-dots fs-1` |
| ⑤ | Reset button di `col-md-1` bersyarat → layout grid tidak simetris | Ubah ke `col-auto ms-auto`, selalu tampil |
| ⑥ | `card-header` dipakai sebagai section separator (salah konteks) | Ganti dengan `div.border-top.bg-light-lt` yang semantis |
| ⑦ | Tombol tutup panel `[X]` tanpa `title` tooltip | Tambah `title="Tutup panel detail"` |

---

## Deploy Manual (via cPanel)

Gunakan script berikut untuk deploy ke hosting cPanel:

```bash
cd /home/simlppmi/sim-lppm

# === SAFETY SETUP ===
set -euo pipefail
# Jika terjadi error di pertengahan → pastikan sistem kembali menyala
trap 'echo "❌ Deploy GAGAL! Menjalankan php artisan up..."; php artisan up; exit 1' ERR

# 1. Backup
BACKUP_DIR="storage/app/backup"
mkdir -p "$BACKUP_DIR"
echo "📦 Membuat backup..."
tar czf "$BACKUP_DIR/backup-$(date +%Y%m%d-%H%M%S).tar.gz" \
  --exclude="./storage/app/backup" \
  --exclude="./node_modules" \
  --exclude="./.git" \
  --exclude="./vendor" . || echo "⚠️ Backup selesai dengan beberapa warning (diabaikan)."

# 2. Maintenance mode ON (cegah akses selama deploy)
php artisan down --retry=300

# 3. Pull & install
git pull origin main
composer install --no-dev --optimize-autoloader

# 4. Preview & Run Migration
echo "📋 Pending migrations yang akan dijalankan:"
php artisan migrate --pretend --force
echo "⏳ Lanjut dalam 5 detik... (Ctrl+C untuk batal)"
sleep 5
php artisan migrate --force

# 5. Cache & OPcache
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php -r 'if (function_exists("opcache_reset")) { opcache_reset(); echo "OPcache cleared\n"; } else { echo "OPcache tidak tersedia\n"; }'

# 6. Flush PDF cache (regenerate otomatis)
find storage/app/public/pdf_cache -type f -name "*.pdf" -delete 2>/dev/null || true

# 7. Permissions (Sesuai standar Zero Trust & Keamanan File cPanel)
find . -type f -print0 | xargs -0 chmod 644
find . -type d -print0 | xargs -0 chmod 755
chmod -R 775 storage bootstrap/cache
chmod 755 public
chmod 644 public/.htaccess
chmod 644 public/index.php
chmod 600 .env                    # Kritis: .env hanya boleh dibaca owner

# 8. Maintenance mode OFF
trap - ERR
php artisan up
echo "✅ Deploy selesai!"
```
