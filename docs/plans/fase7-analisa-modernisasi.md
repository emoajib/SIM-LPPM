# Analisa Fase 7 — Modernisasi Lanjutan (berbasis kode)

Tanggal: 2026-11-16
Status: **analisa, belum ada perubahan kode**
Cakupan: `resources/js/modern/`, `config/modern-pages.php`, `app/Livewire/**`, `routes/web.php`, `resources/css/modern.css`, `tests/e2e/`, `.env` (queue)

Dokumen terkait:
- `docs/plans/master-plan.md` (status gelombang modernisasi)
- `docs/plans/gap-analysis-modern-classic.md` (paritas modern ↔ klasik)
- `docs/plans/backlog-modernisasi-30-47.md` (backlog terpisah hasil triase #30–#47)

---

## 1. Ringkasan eksekutif

Fase 7 **tidak siap dieksekusi apa adanya**. Dari 10 item:

| Klasifikasi | Item | Alasan |
|---|---|---|
| **Layak & direkomendasikan** | F7-01 (revisi), F7-04, F7-05, F7-06, F7-08, F7-10 | Murah–sedang, memakai pola yang sudah ada, risiko terkendali |
| **Ditunda (butuh keputusan arsitektur/ops)** | F7-02, F7-03 (penuh), F7-07 | Bertabrakan dengan prinsip "alur tulis kompleks tetap klasik" / butuh worker produksi |
| **Dikeluarkan dari fase** | F7-09 (#30–#47) | Total ±3–4 bulan; bukan pekerjaan satu fase |

Dua koreksi penting terhadap rencana lama:
1. **Ikon: Tabler, bukan `lucide-react`.** Repo sudah memakai `@tabler/icons-webfont` dan kelas `ti ti-*`; menambah library ikon kedua hanya menambah bundle dan inkonsistensi.
2. **`DashboardIndex.jsx` dihapus, bukan diaktifkan.** Komponen ini duplikat `ProposalsIndex`, punya 4 tautan mati, dan tidak pernah dirutekan.

---

## 2. Fakta arsitektur (hasil pembacaan kode)

| Aspek | Kondisi nyata | Bukti |
|---|---|---|
| Halaman React | 14 route terdaftar → 12 komponen | `config/modern-pages.php`, `resources/js/modern.jsx` |
| Ukuran komponen modern | 15 file di `modern/` + 12 file di `modern/components/` | — |
| Ikon | Tabler webfont terpasang; **44 emoji di 7 file**; **tidak ada `lucide-react`** | `package.json`, `app.css:2`, grep emoji |
| Design token | Sudah ada (`--color-primary`, `--radius-*`, `--shadow-*`) | `resources/css/modern.css:34-55` |
| Dark mode | `@custom-variant dark (&:where(.theme-dark, .theme-dark *))` benar; **594 pemakaian `dark:`** | `modern.css:8`, grep |
| A11y | `aria-label` 33, `aria-hidden` 4; **`focus-visible:` = 0**; `focus:ring-N` = 66; tanpa test a11y | grep `resources/js/modern` |
| Queue | `.env` `QUEUE_CONNECTION=sync`; hanya `GenerateLetterPdfJob` yang `ShouldQueue` | `.env`, `app/Jobs` |
| PDF | `ProposalExportController::download()` memanggil `pdfService->export()` **sinkron** (ada cache file) | `ProposalExportController.php:76-95` |
| E2E | Playwright aktif, **3 test, tanpa auth state** | `tests/e2e/smoke.spec.ts`, `playwright.config.ts` |
| Persuratan | 3 dashboard Livewire (dosen, admin, kepala lppm) ±1.200 baris; **0 halaman React** | `app/Livewire/**/Letter*`, `AdminLppm/Letter` |
| Settings | **±4.185 baris** Livewire (20 komponen: Settings, Tabs, TwoFactor) | `app/Livewire/Settings` |
| Approval/monitoring | ±10 halaman Livewire (Dekan 3, Kaprodi 2, Kepala LPPM 5, Admin LPPM report-approval) | `app/Livewire/{Dekan,Kaprodi,KepalaLppm}` |
| Notifikasi | Sudah ada `NotificationCenter` (Livewire), **belum ada endpoint JSON** | `app/Livewire/Notifications/NotificationCenter.php` |
| "Tugas Anda" klasik | Ada di 5 blade (dekan, kaprodi, kepala lppm, admin) | grep `Tugas Anda` |

---

## 3. Analisa per item

### F7-01 — Design tokens + ikon + audit dark mode
**Kondisi:** token warna/radius/shadow sudah ada; ikon masih emoji (44 kemunculan di `ProposalsIndex`, `PengabdianIndex`, `ProposalShowIndex`, `DailyNoteIndex`, `VerifikasiLuaranIndex`, `DashboardIndex`, `constants/themeColors.js`).

**Revisi rencana:**
- Ganti emoji → `<i className="ti ti-…" aria-hidden="true" />` (pola Blade yang sudah dipakai), **bukan** `lucide-react`.
- Audit dark mode difokuskan ke kontras (`STATUS_COLORS` di `themeColors.js`) dan ikon yang kehilangan makna di latar gelap.

**Effort:** S (2–3 hari) · **Risiko:** rendah · **Test:** snapshot kelas ikon, `theme:check`, tinjauan visual 2 tema.

### F7-02 — Persuratan modern
**Kondisi:** 3 dashboard Livewire berbeda peran + `HasLetterForm` + approval + `GenerateLetterPdfJob`. Tidak ada satu pun halaman React.

**Analisa:** ini penulisan ulang **3 alur kerja tulis** (buat surat, pilih template, approval), bukan sekadar modernisasi tampilan. Bertentangan dengan keputusan rilis: alur tulis kompleks tetap klasik.

**Rekomendasi:** tolak untuk sekarang. Bila perlu, hanya 1 halaman React **read-only** "Daftar Surat".
**Effort penuh:** L (2–3 minggu) · **Risiko:** tinggi (approval + PDF).

### F7-03 — Settings & profil modern
**Kondisi:** 4.185 baris, 20 komponen, termasuk aksi destruktif (restore DB, unduh backup) dan matriks RBAC.

**Rekomendasi:** batasi ke subset **Profil + Appearance + Password** (±400–600 baris). Sisanya tetap klasik dan didokumentasikan sebagai gap yang diterima.
**Effort:** M (4–5 hari) untuk subset; XL (2 minggu) untuk penuh · **Risiko:** sedang.

### F7-04 — Monitoring/approval modern
**Kondisi:** ±10 halaman Livewire untuk 4 peran; pola komponen (`ModuleSubpageIndex`, `ReportApproval`, API `proposals?submodule=final-report`) sudah tersedia.

**Rekomendasi:** bangun **satu** komponen generik "Approval Inbox" yang diparametrisasi per peran, bukan 10 halaman terpisah. Wajib lewat `ProposalPolicy`/`LaporanPolicy` (BC-06).
**Effort:** M (5–7 hari) · **Risiko:** sedang (otorisasi) · **Test:** matriks peran × status, marker `Regression: BC-06`.

### F7-05 — Widget "Tugas Anda" + badge notifikasi
**Kondisi:** data notifikasi & tugas tersedia di Livewire, belum ada endpoint JSON untuk React.

**Rekomendasi:** 1 endpoint `/api/v1/my-tasks` + komponen `TaskWidget` + badge notifikasi di dalam `#modern-root` (bukan header Blade, agar tidak berkonflik dengan Livewire).
**Effort:** S–M (3–4 hari) · **Risiko:** rendah · **Test:** endpoint 200 + isolasi data per user (BOLA).

### F7-06 — A11y lanjutan
**Kondisi:** `focus-visible` = 0; `aria-label` terbatas; `ModuleTabNav` sudah `role="tablist"` tetapi belum `aria-controls`/navigasi panah; `ModernTable` belum `aria-sort`.

**Rekomendasi:** fokus ke 4 komponen inti: `Button`, `ModuleTabNav`, `ModernTable`, `Pagination`.
**Effort:** S (2–3 hari) · **Risiko:** rendah · **Test:** keyboard test Playwright + tinjauan manual.

### F7-07 — Queue PDF proposal/laporan
**Kondisi:** export berjalan sinkron; `QUEUE_CONNECTION=sync`; belum ada worker di produksi.

**Prasyarat sebelum dikerjakan:** driver `database`/redis, worker via cron/supervisor di cPanel, endpoint status job, UI polling, **fallback sinkron** bila worker mati, dan penyesuaian `update_production.sh` (`queue:restart`).
**Effort:** M (4–5 hari) + ops · **Risiko:** tinggi bila tanpa fallback (PDF gagal senyap).

### F7-08 — Perluas E2E Playwright
**Kondisi:** 3 test tanpa login; halaman ber-auth tidak teruji sama sekali.

**Rekomendasi:** mulai dari `storageState` (seed user per peran), lalu smoke 8 peran × 2 tema (paritas `RoleThemeSmokeTest`) + 3 skenario regresi terbaru: Verifikasi Luaran, daily-note kanonik, menu IKU.
**Effort:** M (4–5 hari) · **Risiko:** rendah.

### F7-09 — Triase #30–#47
**Kondisi:** 15 item; sebagian multi-minggu (#37 = 7 minggu, #38 = 3 minggu, #40–#45 = 1–2 minggu). Total ±3–4 bulan.

**Rekomendasi:** keluarkan dari Fase 7 → `docs/plans/backlog-modernisasi-30-47.md` dengan status "kerjakan / terima gap / proyek terpisah".
**Effort:** bukan satu fase.

### F7-10 — `DashboardIndex.jsx`
**Kondisi (331 baris):**
- Duplikasi fungsi `ProposalsIndex` (stats + tabel + filter).
- **Tautan mati:** `/prototype`, `/kki`, `/research/contract`, `/dashboard/dosen/surat/dashboard` — `route:list` mengonfirmasi tidak ada route kki/prototype; dashboard surat dosen sebenarnya `/dosen/persuratan`.
- `loadStats()` memakai `/research/api/v1/proposals?type=community-service` (base tidak konsisten) dan `surat`/`prototipe` di-hardcode `0`.
- Terdaftar sebagai `allowed_unrouted_components` → tidak pernah dirender.

**Rekomendasi:** **hapus** + perbarui registry `modern:check`. Bila kelak butuh dashboard React, bangun ulang di atas `ProposalsIndex` + endpoint agregat.
**Effort:** XS (1 jam) · **Risiko:** sangat rendah.

---

## 4. Temuan yang mengubah rencana

1. **Lucide → Tabler** (hindari dependency & sistem ikon ganda).
2. **DashboardIndex dihapus**, bukan diaktifkan.
3. **Queue PDF mustahil tanpa worker produksi**; `QUEUE_CONNECTION=sync` membuat job berjalan inline.
4. **Persuratan (1.200 baris) & Settings (4.185 baris)** terlalu besar untuk satu item; keduanya melanggar prinsip "tulis kompleks tetap klasik".
5. **F7-09 bukan fase** — totalnya ±3–4 bulan.
6. **E2E tanpa auth = nol nilai** untuk halaman modern.

---

## 5. Urutan eksekusi yang direkomendasikan

| Batch | Isi | Effort | Gate tambahan |
|---|---|---|---|
| **7A** | F7-10 hapus `DashboardIndex` + F7-01 emoji→Tabler + audit kontras | 3–4 hari | `modern:check`, `theme:check`, `npm run build` |
| **7B** | F7-06 a11y (focus-visible, tablist keyboard, `aria-sort`) | 2–3 hari | keyboard test |
| **7C** | F7-05 widget tugas + badge notifikasi | 3–4 hari | test BOLA endpoint |
| **7D** | F7-04 Approval Inbox generik 4 peran | 5–7 hari | `Regression: BC-06` |
| **7E** | F7-08 E2E auth state + 8 peran × 2 tema + 3 skenario regresi | 4–5 hari | Playwright stabil |
| **Ditunda** | F7-02, F7-03 (penuh), F7-07 | — | keputusan arsitektur/ops |
| **Backlog** | #30–#47 | ±3–4 bulan | dokumen terpisah |

Total yang direkomendasikan: **±17–23 hari kerja**.

---

## 6. Gate wajib per batch (AGENTS.md)

```
php artisan rate-limiter:clear --force && php artisan config:clear
php artisan test                                  # 0 failed
./vendor/bin/pint --test && ./vendor/bin/phpstan analyse --level=5
php artisan theme:check && php artisan modern:check \
  && php artisan regression:check && php artisan copy:check
php artisan route:cache && php artisan route:clear
npm run build                                     # commit public/build (BC-07)
```

Test penjaga baru wajib memuat marker `Regression: BC-01` (theme boundary) atau `BC-06` (otorisasi) sesuai area.

---

## 7. Risiko & mitigasi

| Risiko | Mitigasi |
|---|---|
| Dua sistem ikon (emoji + Tabler + lucide) | Standarkan Tabler; larang penambahan library ikon di review |
| Approval Inbox melonggarkan akses | Semua lewat policy; test matriks peran (BC-06) |
| Widget tugas membocorkan data lintas user | Endpoint selalu `Auth::id()` + test BOLA |
| Queue PDF gagal senyap | Fallback sinkron + status job + alert log |
| E2E rapuh (flaky) | Seed deterministik, `storageState`, retry minimal |
| Scope creep Fase 7 | Batas per batch di dokumen ini; item "ditunda" tidak dikerjakan tanpa keputusan baru |

---

## 8. Kesimpulan

Fase 7 yang layak: **F7-10 (hapus), F7-01 (Tabler), F7-06 (a11y), F7-05 (widget), F7-04 (approval inbox), F7-08 (E2E)**.
Yang ditunda: **F7-02, F7-03 penuh, F7-07**.
Yang dikeluarkan: **F7-09 (#30–#47)** → backlog terpisah.
