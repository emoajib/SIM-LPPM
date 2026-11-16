# Catatan Deploy Produksi — 2026-11-16

Status: **SUKSES** (deploy kode + migrasi + cache + smoke test)
Situs: https://sim-lppm.itsnupekalongan.ac.id
Host: cPanel `simlppmi@modica`, `/home/simlppmi/sim-lppm`

---

## 1. Ringkasan

| Item | Nilai |
|---|---|
| Commit sebelum | `9b8d9724` (2026-11-12) |
| Commit sesudah | `6a53ca53` (2026-11-16) |
| Rentang | 61 commit, 368 file, +10.027 / −983 |
| Migrasi | batch 37 → **38** (`error_signatures`, `dev_events`) |
| Composer | `composer.lock` tidak berubah → `Nothing to install` |
| Snapshot DB | `pre-migrate-20261116_135905.sql.gz` (1,5 MB) |
| Backup app | `backup-20261116-135749.tar.gz` (±559 MB) |
| Smoke test | 6/6 (homepage 302, login 200, dashboard 302, research 302, community-service 302, health 200) |
| Maintenance | OFF setelah deploy |

---

## 2. Lingkungan produksi (terverifikasi)

| Item | Nilai |
|---|---|
| Laravel | 12.70.2 |
| PHP | 8.4.27 |
| Composer | 2.8.10 |
| `APP_ENV` | production |
| `APP_DEBUG` | OFF |
| Timezone | Asia/Jakarta |
| Locale | id |
| `mysqldump` | `/bin/mysqldump` |
| Disk | 243 GB tersedia (70% terpakai) |

---

## 3. Jalur deploy

Script yang dipakai: `update_production.sh` versi `6a53ca53`, dijalankan dari
home (`~/update_production_hardened.sh`) karena file di repo masih versi lama
saat itu.

Urutan yang berjalan:

1. Preflight `mysqldump` (sebelum maintenance)
2. `php artisan down --retry=300`
3. Backup app + retensi 3 (`backup-*.tar.gz`)
4. Guard tree kotor → `git pull --ff-only origin main`
5. `composer install --no-dev --optimize-autoloader`
6. `migrate --pretend` + jeda 5 detik
7. **`db:snapshot --label=pre-migrate`**
8. `migrate --force` (2 migrasi, batch 38)
9. `queue:restart`, `media:migrate-paths`, `patch:software-tkt`
10. `optimize:clear`, `config:cache`, `route:cache`, `view:cache`
11. `logs:prune`, OPcache reset (tidak tersedia di CLI), PDF cache, permission
12. `php artisan up` → smoke test 6 URL → checklist keamanan

---

## 4. Hasil checklist keamanan

| Cek | Hasil |
|---|---|
| `.env` | HTTP 000 (bukan 200) ✅ |
| `.git/HEAD` | HTTP 000 ✅ |
| `storage/app/backup/` | HTTP 404 ✅ |
| `phpinfo.php` | HTTP 404 ✅ |
| `database/database.sqlite` | HTTP 404 ✅ |
| `.env` permission | `600` ✅ |
| `storage/app/backup` permission | `700` ✅ |
| File `.sql`/`.zip` di root | **awalnya ada** `backup-20261027.sql` → dibersihkan |
| Error baru di log | tidak ada (warning dari log 11 Nov, pra-deploy) |

---

## 5. Temuan & tindakan

### 5.1 `backup-20261027.sql` (120 byte)

Bukan backup, melainkan output gagal `mysqldump`:

```
mysqldump: Got error: 1045: "Access denied for user 'simlppm'@'localhost'"
```

File ini memicu `SECURITY_FAIL` sehingga script keluar dengan `exit 1` **setelah**
situs dinyalakan. Tindakan: file dipindahkan lalu dihapus; root sudah bersih.

**Pelajaran:** kegagalan `mysqldump` yang di-redirect ke file (mis. `> backup.sql`)
meninggalkan file kecil berisi pesan error. File seperti ini harus dibersihkan,
dan gerbang keamanan memang benar menangkapnya.

### 5.2 Error log 11 Nov 13:20

```
SQLSTATE[42S22]: Unknown column 'is_active' in 'WHERE'
select * from research_schemes where is_active = 1
```

Penyebab: halaman memakai kolom `is_active` sebelum migrasi
`2026_11_12_000001_add_is_active_to_research_and_community_service_schemes_table`
dijalankan. Migrasi sudah `Ran` (batch 37). Log diarsipkan ke
`laravel-2026-11-11.log`; `laravel.log` baru dibuat.

### 5.3 Media 28 record tanpa file

`media:migrate-paths` melaporkan 744 sudah di path baru, **28 tidak ditemukan**
(ID 31, 33, 410–423, 482, 564–591, 735). Ini kondisi pra-deploy; tidak dihapus.
Tindak lanjut: `php artisan media:cleanup-orphans --dry-run` sebelum membersihkan.

### 5.4 OPcache CLI tidak tersedia

`OPcache not available` pada CLI — normal bila `opcache.enable_cli=0`.
OPcache web (PHP-FPM) tetap menjadi tanggung jawab runtime.

---

## 6. Verifikasi pasca-deploy

| Cek | Hasil |
|---|---|
| `git log -1` | `6a53ca53` (= `origin/main`) ✅ |
| `git status --short` | kosong ✅ |
| `migrate:status` | batch 38 `Ran` ✅ |
| `health-check` | HTTP 200 ✅ |
| `APP_ENV` / `APP_DEBUG` | production / OFF ✅ |
| `.env` permission | `600` ✅ |
| `storage/app/backup` | `700`, semua file `600` ✅ |
| Root `.sql`/`.zip` | bersih ✅ |

### Verifikasi manual yang masih disarankan

1. `/research/verifikasi-luaran` — tabel tampil di tema modern (bukan putih)
2. `/community-service/verifikasi-luaran` — sama
3. `/research/daily-note/{id}` — klasik, tanpa `/modern`
4. Menu "IKU" → `/iku`, judul "IKU"

---

## 7. Sisa pekerjaan pasca-deploy

| Item | Status |
|---|---|
| `php artisan budget-caps:audit` di produksi | belum dijalankan |
| `SENTRY_DSN` produksi | belum diisi |
| `GITHUB_TOKEN` produksi | belum diisi (agar `history:sync` terjadwal tidak gagal) |
| Verifikasi manual 4 halaman | menunggu browser |
| `composer install --no-dev` memastikan Boost/`browser.log` tidak aktif | perlu cek `storage/logs` |

---

## 8. Rollback

Snapshot pra-migrasi: `storage/app/backup/pre-migrate-20261116_135905.sql.gz`

```bash
gunzip -c pre-migrate-20261116_135905.sql.gz > /tmp/restore.sql
php artisan app:restore-backup --sql=/tmp/restore.sql --force
```

Rollback kode:

```bash
git revert <sha>        # per commit, lalu jalankan ulang update_production.sh
```

Backup app: `storage/app/backup/backup-20261116-135749.tar.gz` (jangan unduh
lewat URL; hanya via terminal, direktori 700).

---

## 9. Catatan operasional

- Retensi backup hanya mencakup pola `backup-*.tar.gz` (3 terbaru). File lama
  bernama `app-*.tar.gz` tidak ikut terhitung (warisan script lama).
- `db_20261114_021210.sql` (23 MB) dan `storage_20261114_021217.zip` (536 MB)
  berasal dari fitur backup Settings, bukan deploy. Keduanya `600`.
- Deploy berikutnya cukup `bash update_production.sh` dari repo karena
  `git pull` sudah memperbarui file script menjadi versi hardened.