# Bug Class Registry (BC-xx)

Registry ini adalah **memori jangka panjang** bug yang pernah terjadi di SIM-LPPM.
Tujuannya bukan mencatat setiap typo, tetapi **kelas bug** yang bisa berulang di
halaman/modul baru. Tiap kelas memiliki:
- **Gejala** yang terlihat user,
- **Akar masalah** di kode,
- **Perbaikan** (commit + file),
- **Penjaga** (test/lint command yang akan gagal bila bug kembali),
- **Aturan** yang mencegah pengulangan,
- **Kasus** (tanggal & lokasi).

## Cara pakai
- Sebelum mengerjakan fitur baru: `php artisan history:brief <area>` (lihat BC relevan).
- Saat menemukan bug: `php artisan bug:capture "gejala" --area=<area>` → buat entri baru + stub test + `dev_events`.
- Retensi memori: `php artisan observability:prune --dry-run` (terjadwal harian 03:30; hanya menghapus `dev_events` lama dan `error_signatures` berstatus fixed/ignored).
- Saat memperbaiki: commit dengan suffix `[BC-xx]` dan body berisi *Why / BC / Test*.
- Saat menambah test: tandai docblock `Regression: BC-xx`.
- CI menjalankan `php artisan regression:check` (dua arah registry ↔ test).

## Kelas bug yang sudah terbukti berulang

| Kode | Kelas | Penjaga utama |
|---|---|---|
| BC-01 | Theme boundary leak (halaman blank, tab modern di klasik, redirect hilang) | `theme:check`, `ModernPageParityTest` |
| BC-02 | Active role vs stored role (`hasRole` bypass, superadmin 0 data) | `activeHasRole` lint, `ActiveRoleAuthorizationTest` |
| BC-03 | UI ↔ API drift (field hilang → UI kosong/blank) | kontrak snapshot React↔API |
| BC-04 | State-machine UI leak (tombol/aksi muncul di status yang salah) | `ReportSecurityAndLifecycleTest` |
| BC-05 | Mass assignment / silent data loss | `BC05MassAssignmentTest`, `$fillable` audit |
| BC-06 | IDOR / BOLA (akses data milik orang lain) | `LetterLinkedRequestBolaTest`, `ModernAccessBolaTest`, policy test |
| BC-07 | Stale build asset (deploy tanpa `npm run build`) | `BC07StaleBuildTest`, BC-07 commit-level di CI |
| BC-08 | Workflow dead-end (LPJ–amandemen, surat saat modul off) | `FinancialApprovalTest`, `LetterModuleGatingTest` |
| BC-09 | Artefak copy/placeholder AI (teks liar di UI, marker generik) | `copy:check`, `ModernBreadcrumbTest`, `CopyCheckTest` |
| BC-10 | Alpine `$watch` string dot-path (directive queue mati, modal read-only) | `BC10AlpineDotPathWatchTest`, `BudgetCapManagerTest` |

Detail tiap kelas: lihat file `BC-0x-*.md`.

Status kesehatan (BC × penjaga × commit terakhir):
`php artisan regression:health` (tabel) atau `--json` untuk tooling.
