# Master Plan — SIM-LPPM (urutan eksekusi + mitigasi)

Dokumen ini mengonsolidasikan semua pekerjaan yang direncanakan/ditunda,
diurutkan dari yang paling berdampak & paling aman, dengan mitigasi per item.
Sumber: percakapan sesi, AGENTS.md, docs/plans/gap-analysis-modern-classic.md,
docs/regressions/README.md.

Status kolom: DONE / TODO / PARTIAL.

---

## Wave 0 — Sudah selesai (konteks)

- P0-1 blank modern proposal detail (map key) — DONE
- P0-2 superadmin 0 data (activeHasAnyRole + superadmin di global role) — DONE
- P1-1 Verifikasi Luaran team-member scoping — DONE
- P1-2 Verifikasi Luaran UI role gating (canVerify/isDosen dari API) — DONE
- P1-3 classic redirect untuk route modern-only verifikasi — DONE
- A LPJ–amandemen (badge, inline decide, stats, ActivityLog, transaksi) — DONE
- B Budget caps (kolom, toleransi, salin, audit command) — DONE
- D Surat (gating flag, cache menu, un-gate verify/download/view, badge TTE) — DONE
- P2-1 Pengabdian modern parity (route + module-aware apiBase) — DONE
- P2-9 amandemen React list/decide + endpoint request — DONE
- Redirect klasik untuk semua route modern — DONE
- Tema default klasik saat login — DONE
- KPI widget HTML subtitle — DONE
- Sahkan LPJ di daily note modern — DONE
- Pesan error amandemen + tombol Tolak menonjol + warning banner — DONE
- BC registry (BC-01..BC-08) + regression:check + 5 marker — DONE
- theme:check + ThemeParityTest — DONE
- Native Ajukan proposal (P1b) — DONE
- Native Setujui/Minta Revisi/Tolak (P1c) — DONE
- API daily-note module-aware pengabdian + banner LPJ (P1e) — DONE
- CI guard build asset (informational) — DONE
- Full suite 536 passed, 13 skipped, 0 failed; CI 4/4 hijau — DONE

---

## Wave 1 — Stabilisasi lokal (pematangan sebelum deploy)

| ID | Pekerjaan | Verifikasi | Mitigasi |
|---|---|---|---|
| W1-1 | `rate-limiter:clear --force`, `config:clear`, `optimize:clear` | command sukses | reset state |
| W1-2 | `npm run build` | build sukses, hash berubah | BC-07 |
| W1-3 | `php artisan test` (timeout 600s) | 0 failed | regresi |
| W1-4 | `pint --test`, `phpstan --level=5`, `theme:check`, `regression:check` | semua hijau | kualitas |
| W1-5 | `php artisan route:cache` lalu `route:clear` | sukses | closure route |
| W1-6 | Smoke test manual 3 role × 2 tema (lihat docs/plans/gap-analysis-modern-classic.md §6) | tidak ada 500/405, tab tidak muncul di klasik | BC-01 — **di-otomatiskan**: `tests/Feature/Theme/RoleThemeSmokeTest.php` (8 peran × 2 tema × 9 halaman = 144 kasus) |
| W1-7 | Cek `storage/logs/laravel.log` (grep error/exception/fatal) | kosong | observability |

Kriteria lulus: semua hijau + `git status` bersih.

---

## Wave 2 — Pencegahan berulang (kontrak tema) — sebagian selesai

| ID | Pekerjaan | Effort | File | Mitigasi |
|---|---|---|---|---|
| W2-1 | Enum `App\Enums\Theme` + helper | S | app/Enums/Theme.php, helpers.php | DONE |
| W2-2 | Guard di partial module-tabs + superadmin | S | module-tabs.blade.php | DONE |
| W2-3 | `theme:check` command | S | CheckTheme.php | DONE |
| W2-4 | `ThemeParityTest` | S | tests/Feature/Theme/ThemeParityTest.php | DONE |
| W2-5 | Registry `config/modern-pages.php` (ganti array inline `$modernPageMap`) | M | config/modern-pages.php, app.blade.php | Pencegahan blank page (P0-1) |
| W2-6 | `modern:check` command (validasi registry ↔ React PAGES) | M | ModernCheck.php | Pencegahan key hilang |
| W2-7 | Kontrak snapshot React↔API (field yang dipakai UI wajib ada di payload) | M | tests/Feature/Api/*ContractTest.php | Pencegahan BC-03 |
| W2-8 | Ekstrak closure route modern (research & pengabdian duplikat) ke controller | M | routes/web.php, controller | Pencegahan BC-01/BC-03 |
| W2-9 | DoD di AGENTS.md + PR template | S | AGENTS.md, .github/pull_request_template.md | Proses |
| W2-10 | Arsip 5 MD root ke docs/archive/ | S | docs/archive/ | Housekeeping |
| W2-11 | Ganti duplikasi cek tema di Dashboard.php & ThemeSwitcherController | S | Dashboard.php, ThemeSwitcherController.php | BC-01 (jaga fallback) |

---

## Wave 3 — Penutupan gap paritas modern (dari gap analysis)

| ID | Pekerjaan | Effort | Mitigasi |
|---|---|---|---|
| W3-1 | Tambah filter (status, tahun akademik, role, faculty, study program) ke `ModuleSubpageIndex` + pass ke API | M | BC-01, BC-03 — **SELESAI** (termasuk `academic_year`, `report_statuses`, `apiBase` module-aware) |
| W3-2 | Tambah role tabs (ketua/anggota) di `ModuleSubpageIndex` | S | BC-01 — **SELESAI** (select peran) |
| W3-3 | Tambah status filter cards di final-report index modern | S | BC-01 — **SELESAI** (`meta.stats` + filter status laporan) |
| W3-4 | Verifikasi filter faculty/year di Verifikasi Luaran modern | S | BC-01 — **SELESAI**: klasik juga tidak punya; scoping server-side sudah ada |
| W3-5 | Perluas `ThemeParityTest` dengan assertion fitur kunci (filter ada, tombol ada) | S | **SELESAI** via `ModernFilterContractTest` (kontrak statis React↔Blade) |
| W3-6 | Native kontrak editor, surat list, tim management (opsional) | L | BC-08 (via "Aksi lanjutan" diterima) |
| W3-7 | Native scan upload, amandemen request, ekspor PDF (opsional, P3) | L | BC-08 (via klasik diterima) |
| W3-8 | "Lihat" di Perbaikan Usulan / Laporan Akhir mengarah ke halaman yang benar (bukan detail proposal) | S | BC-01 — **SELESAI** |

---

## Wave 4 — Engineering Memory (otomatis simpan history & cegah berulang)

### H1 (murah, dampak besar)
| ID | Pekerjaan | Mitigasi |
|---|---|---|
| W4-H1.1 | Konvensi commit body wajib (Why / BC / Test) | History berisi "why" |
| W4-H1.2 | Registry docs/regressions (8 BC) | DONE |
| W4-H1.3 | Marker `Regression: BC-xx` di test | DONE (5/8) |
| W4-H1.4 | `regression:check` command | DONE |
| W4-H1.5 | `history:brief <area>` (git-based) | Recall otomatis |
| W4-H1.6 | Arsip 5 MD root | Housekeeping |

### H2 (capture otomatis)
| ID | Pekerjaan | Mitigasi |
|---|---|---|
| W4-H2.1 | Aktifkan Sentry (DSN, release=commit SHA, sample rendah) | Error terkumpul |
| W4-H2.2 | Tabel `error_signatures` (fingerprint, modul, bc_code, commit fix) | Error tidak hilang |
| W4-H2.3 | Tabel `dev_events` (commit, CI, deploy, error, bug) permanen | Retensi GitHub/log habis |
| W4-H2.4 | `history:sync` (tarik commit + status CI via API) | Otomatis |
| W4-H2.5 | Hook deploy mencatat ke `dev_events` (DB, bukan file) | git status produksi bersih |
| W4-H2.6 | Rotasi log 374 MB lokal + produksi | Observability |
| W4-H2.7 | `bug:capture` command (entri BC + stub test gagal) | Bug baru otomatis tersimpan |

### H3 (pemahaman kodebase & laporan)
| ID | Pekerjaan | Mitigasi |
|---|---|---|
| W4-H3.1 | `codebase:map` → docs/history/codebase-map.md | Agent tidak eksplorasi ulang |
| W4-H3.2 | Auto-changelog dari conventional commit | History terbaca |
| W4-H3.3 | Digest mingguan (error baru, fix tanpa test, BC tanpa penjaga) | Audit berkala |
| W4-H3.4 | Dashboard kesehatan regresi (read-only) | Visibilitas |

---

## Wave 5 — Deploy produksi (P4)

| ID | Pekerjaan | Mitigasi |
|---|---|---|
| W5-1 | Pre-flight lokal: pint, phpstan, route:cache, full test, npm build + commit | BC-07 |
| W5-2 | `rate-limiter:clear --force`, `config:clear` | AGENTS.md |
| W5-3 | Push main + pantau 4 CI (Test & Q, MySQL, PostgreSQL, Security) | Semua hijau |
| W5-4 | `update_production.sh` (backup 700/600 → maintenance → migrate preview 5s → migrate → cache → OPcache → permission → rate limiter → up) | Auto-recovery |
| W5-5 | Checklist keamanan 10 item (curl .env, .git/HEAD, backup tar, phpinfo.php, database.sqlite, log, .env 600) | Keamanan |
| W5-6 | Smoke test browser: 11 halaman kunci × 2 tema | Tidak ada 500/405 |
| W5-7 | `budget-caps:audit` + verifikasi unique index (year, semester) | Read-only |
| W5-8 | Rencana rollback: `git revert <sha>` per tahap; restore tar backup | Dokumentasi |

---

## Mitigasi sistemik (berlaku untuk semua wave)

1. **Single source of truth tema**: `App\Enums\Theme` + `is_modern_theme()` — dilarang literal `'modern'`/`'classic'`.
2. **Self-guard komponen modern**: `@if (is_modern_theme())` di dalam partial, bukan hanya di caller.
3. **Gate CI**: `theme:check`, `regression:check`, `pint --test`, `phpstan --level=5`, build-verify (informational).
4. **Escape hatch**: `No-Regression-Test: <alasan>` untuk hotfix darurat, muncul di digest mingguan.
5. **Hybrid approach**: modern read-only untuk aksi kompleks + "Aksi lanjutan → klasik"; native bertahap.
6. **Feature flag** sebagai kill switch (`feature_modern_proposal_actions`, `feature_amendment_react`).
7. **Transaksi + lock** untuk state transition (submit, approve, LPJ).
8. **Otorisasi server-side** (capability flag dari API, bukan UI).
9. **Build asset** wajib commit `public/build` tiap perubahan JS/CSS.
10. **Deploy** via `update_production.sh` (bukan `git pull` langsung), dengan backup + auto-recovery.

---

## Urutan eksekusi yang benar

```
Wave 1 (stabilisasi lokal) → Wave 2 (kontrak tema) → Wave 3 (gap paritas)
→ Wave 4 (engineering memory, H1 dulu) → Wave 5 (deploy)
```

Prioritas di dalam wave: effort kecil dulu (S), lalu M, lalu L.
Item "Diterima" (hybrid) tidak dikerjakan — itu keputusan sadar, bukan bug.
