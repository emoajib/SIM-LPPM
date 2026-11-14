## Ringkasan

<!-- Apa yang berubah dan mengapa. Sertakan konteks bug/fitur. -->

## Jenis perubahan

- [ ] Bugfix
- [ ] Fitur baru
- [ ] Refactor (tanpa perubahan perilaku)
- [ ] Dokumentasi / konfigurasi
- [ ] Keamanan

## Bug class terkait

<!-- Isi kode BC dari docs/regressions/README.md, mis. BC-01, BC-03. Kosongkan bila tidak ada. -->

- BC:

## Checklist wajib (Definition of Done)

- [ ] `php artisan test` — 0 failed
- [ ] `./vendor/bin/pint --test` — lolos
- [ ] `./vendor/bin/phpstan analyse --level=5` — 0 error
- [ ] `php artisan theme:check` — lolos
- [ ] `php artisan regression:check` — lolos
- [ ] `php artisan route:cache` lalu `route:clear` — lolos
- [ ] Jika mengubah JS/CSS: `npm run build` dijalankan dan `public/build` ikut di-commit (BC-07)
- [ ] Jika ada migrasi: `php artisan migrate --pretend` sudah ditinjau
- [ ] Test regresi baru ditambahkan/diperbarui (`Regression: BC-xx` di docblock)

## Checklist paritas tema (BC-01)

- [ ] Fitur baru di tema klasik juga tersedia di tema modern, **atau** didokumentasikan sebagai gap yang diterima di `docs/plans/gap-analysis-modern-classic.md`
- [ ] Tidak ada literal `'modern'`/`'classic'` di view — gunakan `is_modern_theme()`
- [ ] Tidak ada `->hasRole(` di view — gunakan `->activeHasRole(`
- [ ] Partial modern tetap punya self-guard `@if (is_modern_theme())`
- [ ] Halaman modern punya fallback/redirect ke klasik saat tema klasik aktif

## Dampak & rollback

<!-- Peran terdampak, data yang berubah, cara rollback bila bermasalah. -->
