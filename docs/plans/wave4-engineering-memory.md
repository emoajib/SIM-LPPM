# Wave 4 — Engineering Memory (error_signatures, dev_events, bug:capture)

Status: **rencana eksekusi** (W4-H2.2, W4-H2.3, W4-H2.7, sebagian W4-H2.5)
Acuan: `docs/plans/master-plan.md` Wave 4, `docs/regressions/README.md`, `AGENTS.md`.

## 1. Analisa kodebase (kondisi saat ini)

| Aspek | Temuan |
|---|---|
| Pola PK | UUID (`HasUuids`) untuk tabel operasional baru (mis. `notification_logs`) |
| Pola migrasi | `create_<table>_table.php`, `Schema::create`, index eksplisit, `down()` drop |
| Pola command | `app/Console/Commands/*.php` + `$signature` + test di `tests/Feature/Console/` |
| Registry bug | `docs/regressions/BC-01..BC-08-*.md` + README berisi tabel & aturan |
| Drift | README sudah menyebut `bug:capture "gejala"`, command belum ada (BC-03: dokumen ↔ kode) |
| Error handling | `bootstrap/app.php` hanya mencatat 403/405 ke `Log` — tidak ada agregasi error |
| Retention | `storage/logs/laravel.log` 363 MB, `browser.log` 46 MB — log tidak bisa jadi memori jangka panjang |

Kesimpulan: tabel + command diperlukan, dan harus aman dipasang di produksi
tanpa mengubah alur request yang sudah berjalan.

## 2. Desain skema

### `error_signatures`
Satu baris = satu **kelas error** (bukan satu kejadian). Pengulangan menambah `occurrences`.

| Kolom | Tipe | Alasan |
|---|---|---|
| `id` | uuid PK | konsisten dengan tabel lain |
| `fingerprint` | char(64) **unique** | sha256(exception class + pesan ternormalisasi + file + line) → dedupe |
| `exception_class` | string(191) | filter cepat per jenis error |
| `message` | text nullable | pesan asli (sudah dipotong) |
| `file`, `line` | string(500), uint | lokasi error |
| `module` | string(60) nullable | research / community-service / admin / auth |
| `bc_code` | string(10) nullable | hubungkan ke registry BC bila diketahui |
| `status` | string(20) default `open` | open / investigating / fixed / ignored |
| `occurrences` | uint default 1 | frekuensi |
| `first_seen_at`, `last_seen_at` | timestamp | kapan mulai & terakhir |
| `commit_fix` | string(40) nullable | SHA commit yang memperbaiki |
| `notes` | text nullable | catatan investigasi |
| `timestamps` | | |

Index: `status+last_seen_at`, `module`, `bc_code`, unique `fingerprint`.

### `dev_events`
Satu baris = satu peristiwa pengembangan (commit/CI/deploy/error/bug/decision).

| Kolom | Tipe | Alasan |
|---|---|---|
| `id` | uuid PK | konsisten |
| `type` | string(20) index | commit / ci / deploy / error / bug / decision |
| `area` | string(80) nullable index | sama dengan `history:brief <area>` |
| `title` | string(191) | ringkasan satu baris |
| `reference` | string(191) nullable | SHA commit / run id / URL |
| `bc_code` | string(10) nullable | kaitan registry |
| `status` | string(20) nullable | success / failure / info / open |
| `payload` | json nullable | detail bebas (jangan simpan PII berlebih) |
| `occurred_at` | timestamp index | urut waktu, default `now()` |
| `timestamps` | | |

## 3. Desain command

### `bug:capture`
```
php artisan bug:capture "gejala singkat" \
  [--area=umum] [--bc=BC-09] [--cause="..."] [--fix="..."] \
  [--test=path] [--no-test] [--dry-run]
```
Alur:
1. Tentukan kode BC: `--bc` bila diberikan (validasi format + belum dipakai), jika tidak nomor berikutnya dari `docs/regressions/BC-*.md`.
2. Tulis `docs/regressions/BC-09-<slug>.md` dengan template Gejala / Akar masalah / Perbaikan / Penjaga / Aturan / Kasus.
3. Buat stub test `tests/Feature/Regression/BC09Test.php` berisi marker `Regression: BC-09` dan `markTestIncomplete()` (tidak membuat CI merah sebelum fix).
4. Catat `dev_events` (type `bug`, status `open`, payload gejala/cause/fix/test).
5. Cetak langkah berikutnya (tulis assertion gagal → fix → commit `[BC-09]`).

`--dry-run` tidak menulis file/DB. `--no-test` hanya menulis entri BC.

### `observability:prune`
```
php artisan observability:prune [--days=365] [--dry-run]
```
- Hapus `dev_events` lebih lama dari `--days`.
- Hapus `error_signatures` berstatus `fixed`/`ignored` yang `last_seen_at` lebih lama dari retensi (default 180 hari).
- `open`/`investigating` **tidak** dihapus (masih butuh perhatian).

## 4. Mitigasi (wajib)

| Risiko | Mitigasi |
|---|---|
| Pencatatan error membuat request gagal saat DB down | `ErrorSignatureRecorder` membungkus semuanya `try/catch`, mengembalikan `null`, tidak pernah melempar |
| Error 4xx normal (403/404/422) membanjiri tabel | Kelas `HttpException`, `ValidationException`, `AuthenticationException`, `AuthorizationException`, `ModelNotFoundException`, `TokenMismatchException` di-ignore lewat config |
| Fingerprint meledak karena pesan mengandung UUID/angka | Pesan dinormalisasi (UUID → `{uuid}`, angka panjang → `{n}`) sebelum hash |
| Duplikasi baris karena request bersamaan | `fingerprint` unique + transaksi + retry sekali saat `QueryException` |
| Tabel belum ada (deploy belum migrate) | Recorder mengecek `Schema::hasTable` (cache per proses) dan berhenti diam-diam |
| Test suite melambat / bocor data | Recorder nonaktif saat unit test kecuali `ERROR_SIGNATURES_ALLOW_IN_TESTS=true` |
| `bug:capture` menimpa entri BC yang ada | Validasi kode BC + tolak bila file sudah ada |
| Stub test membuat CI merah | Stub memakai `markTestIncomplete()`, bukan assertion gagal |
| `dev_events`/`error_signatures` tumbuh tanpa batas | `observability:prune` + retensi di `config/observability.php` |
| PII di payload | Payload hanya metadata (area, kode BC, path test); tidak menyimpan isi request/body |
| Migrasi mengunci tabel lama | Hanya `Schema::create` tabel baru — tidak menyentuh tabel existing |
| Rollback | `down()` menghapus tabel baru; tidak ada kode existing yang bergantung padanya (recorder aman saat tabel hilang) |

## 5. Urutan kerja

| # | Pekerjaan | Bukti |
|---|---|---|
| 1 | Migrasi `error_signatures` + `dev_events` | `php artisan migrate --pretend` |
| 2 | Model `ErrorSignature`, `DevEvent` | casts, scope |
| 3 | `config/observability.php` + `ErrorSignatureRecorder` + `DevEventRecorder` | unit test |
| 4 | Hook `report()` di `bootstrap/app.php` | error nyata tercatat, 4xx tidak |
| 5 | `bug:capture` | test: file BC + stub + dev_event |
| 6 | `observability:prune` | test: hanya baris lama/resolved terhapus |
| 7 | Dokumentasi: master-plan status, README registry, AGENTS | — |
| 8 | Verifikasi: pint, phpstan, full suite, theme/modern/regression, route:cache, build | CI hijau |

## 6. Di luar lingkup (tetap tertunda)
- `history:sync` (tarik commit + status CI via API) — butuh token GitHub.
- Aktivasi Sentry (`SENTRY_DSN`) — butuh DSN produksi.
- Hook deploy mencatat `dev_events` (W4-H2.5) — setelah script deploy stabil.

## 7. Status lanjutan (W4-H2.4/H2.5/H2.6)

| Item | Status | Cara pakai |
|---|---|---|
| `history:sync` | DONE | `php artisan history:sync --days=7` (butuh `GITHUB_TOKEN` atau `gh auth token`); terjadwal Senin 04:30 |
| `dev:record` + hook deploy | DONE | `update_production.sh` mencatat sukses/gagal; manual: `php artisan dev:record deploy "..." --ref=<sha> --status=success` |
| Rotasi log | DONE | `php artisan logs:prune` (harian 04:00); `LOG_MAX_SIZE_MB=25`, `LOG_KEEP_DAYS=14` |

### Aktivasi Sentry (langkah manual, butuh akun)
1. Buat project di Sentry, salin DSN.
2. Set di `.env` produksi: `SENTRY_DSN=https://...`, `SENTRY_ENVIRONMENT=production`.
3. `SENTRY_RELEASE` diisi otomatis oleh `update_production.sh` (commit SHA) saat `config:cache`.
4. Verifikasi: picu error 500 → cek Sentry Issues; `php artisan logs:prune` dan `observability:prune` tetap jalan.
5. Sampling: `SENTRY_TRACES_SAMPLE_RATE=0.05` (bukan 1.0) agar kuota tidak habis.

### Verifikasi setelah deploy
```bash
php artisan dev:record deploy "manual check" --area=produksi --status=success --ref=$(git rev-parse --short HEAD)
php artisan history:sync --days=1 --dry-run
php artisan observability:prune --dry-run
php artisan logs:prune --dry-run
```
