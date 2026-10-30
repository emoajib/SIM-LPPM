# Plan: Samakan Tata Letak Modul ke Pola Surat (Modern Theme)

> Disimpan agar bisa dibaca semua model AI (OpenCode/Claude/etc). Update progres di sini.

## Tujuan
Menyelaraskan semua modul CRUD/approval ke pola Tabler standar yang dipakai modul Surat,
agar konsisten dengan `body.theme-modern` global di `app.css`.

## Prinsip (aman & minimal)
1. JANGAN ubah markup modul yang sudah Tabler standar — hanya konsistensikan yang menyimpang.
2. JANGAN tambah komponen baru kecuali diperlukan; reuse pola Surat.
3. Perubahan per modul kecil, satu commit per modul, test setelah tiap langkah.
4. Dummy/fallback data harus dihapus (ganti empty state).
5. Role gating tetap via `MenuComposer` + `active_has_any_role`.

## Langkah bertahap (prioritas)
1. Penelitian (Proposal) index → card/tabs/table/badge + empty state + pagination.
2. Pengabdian index → sama.
3. Laporan (Reports) → sama.
4. Approval modules (Dekan/Kaprodi/Kepala LPPM/Admin) → batch + modal + reject reason.
5. Settings & Master Data → form standar + `@error`.

## Mitigasi risiko
- Regresi UI: satu modul per commit; jalankan `php artisan test` + smoke test browser per modul.
- Role access: verifikasi route middleware cocok dengan daftar `roles` di MenuComposer.
- Cache stale: setelah perubahan blade, `php artisan view:clear` + hard refresh.
- Performa: jangan tambah inline CSS/JS besar; reuse `app.css` global.
- Aksesibilitas: aria-label pada tombol ikon; font min 0.75rem.
- Data dummy: ganti dengan empty state.

## Acceptance criteria
- Semua modul CRUD menampilkan card/tabs/table/badge/modal/form Tabler standar.
- Tidak ada data dummy/fallback di view.
- Role gating tetap berfungsi (test lulus).
- `php artisan test` + Pint + PHPStan lulus.
- Tidak ada error 500/405 di browser.

## Progres (terakhir: 30 Okt 2026)
- [x] Analisis selesai
- [x] Font kecil 0.55/0.65rem → 0.75rem (19 view)
- [x] Dummy data di dashboard dihapus
- [x] MenuComposer di-cache per user & role
- [x] Default theme disamakan ke modern
- [x] userActiveTheme diset di MenuComposer
- [x] filemtime(logo.png) diguard
- [x] Realisasi anggaran di infographic dari data nyata
- [x] Bulletin modal hanya admin
- [x] Kontras input form diperbaiki
- [x] Dropdown header (profil/role/notifikasi) diperbaiki (fixed position)
- [x] Page header dihapus dari dashboard modern
- [x] Background modern → #FCFFFA
- [x] Modul 1: Penelitian (Proposal) index — card/tabs/table/badge + empty state + pagination
- [x] Modul 2: Pengabdian index — card/tabs/table/badge + empty state + pagination
- [x] Modul 3: Laporan (Reports) — final-report index (research & PKM) + dekan report-index
- [x] Modul 4: Approval modules — badge consistency (dekan proposal-index, kaprodi proposal-validation, kepala-lppm initial-approval/final-decision, report-approval partial)
- [x] Modul 5: Settings & Master Data — @error feedback (pdf-export-settings, notification-config)
- [x] Tambah ikon menu (MenuComposer: semua item diberi ikon Tabler)
- [x] Komponen Tampilan (UI Halaman) — layout shell Tabler standar; palet modern theme diselaraskan ke prototype (navy #1e3a6e, gradient, rounded-xl)
- [x] Infrastruktur React+Tailwind (Fase 1) — vite config, entry resources/js/modern.jsx, build OK
- [x] Modern theme shell (Fase 2) — React banner (navy gradient) + Blade content tetap (hybrid, fungsionalitas aman)
- [x] Migrasi modul Penelitian (Proposal) index ke React (modern theme) + API endpoint /research/api/proposals
- [ ] Bulletin pindah ke server-side (fitur tersisa: migrasi + model + API + refactor view)
- [x] Aksesibilitas aria-label (sweep icon buttons di modul-modul utama)

## File kunci
- resources/views/components/dashboard/modern-infographic.blade.php
- resources/views/livewire/dashboard/main-dashboard.blade.php
- app/Livewire/Dashboard.php
- app/View/Composers/MenuComposer.php
- resources/views/components/layouts/header.blade.php
- resources/css/app.css
- app/Http/Controllers/ThemeSwitcherController.php
