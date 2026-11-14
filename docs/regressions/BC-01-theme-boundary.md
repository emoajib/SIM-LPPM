# BC-01 — Theme boundary leak

**Gejala:** halaman modern blank (`#modern-root` tidak mount), tab bar modern muncul di tema klasik, route modern tidak redirect ke klasik.

**Akar masalah:** komponen modern (partial `module-tabs`, map `$modernPageMap` di `app.blade.php`) di-include/render tanpa guard tema; route closure memakai default `'modern'` untuk cookie; map route↔page tidak sinkron (P0-1).

**Perbaikan:**
- `ccd12a35` — fix map key `research.proposal.show.modern`
- `cd2dc2b9`, `a66159e7` — default tema `classic`
- `6e4aad9b` — guard `is_modern_theme()` di partial + 2 caller
- `551ac595` — `App\Enums\Theme` + helper
- File: `resources/views/components/layouts/app.blade.php`, `resources/views/components/partials/module-tabs.blade.php`, `routes/web.php`, `app/helpers.php`, `app/Enums/Theme.php`

**Penjaga:**
- `tests/Feature/Theme/ModuleTabVisibilityTest.php`
- `tests/Feature/ModernNavbarVisibilityTest.php`
- `php artisan theme:check` (rencana P2-A3)
- `php artisan modern:check` (rencana P2-B2)

**Aturan:**
1. Komponen modern wajib self-guard (`@if (is_modern_theme())` atau komponen `<x-modern-only>`).
2. Blade klasik dilarang memakai kelas Tailwind-only (`rounded-xl`, `bg-slate-*`, `dark:*`).
3. Setiap route modern wajib fallback redirect ke klasik.
4. Pengecekan tema hanya lewat `is_modern_theme()` / `App\Enums\Theme` — dilarang literal `'modern'`/`'classic'`.
5. Setiap halaman baru wajib didaftarkan di `config/modern-pages.php` + parity test.

**Kasus:** 2026-11-12 halaman blank; 2026-11-13 tab bar di `/research` & `/community-service`.
