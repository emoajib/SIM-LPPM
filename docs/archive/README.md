# Arsip Dokumen

Dokumen di folder ini **historis** — disimpan untuk jejak keputusan, bukan acuan kerja.
Acuan kerja yang berlaku:

| Kebutuhan | Dokumen aktif |
|---|---|
| Aturan workflow & DoD | `AGENTS.md` |
| Rencana kerja berurutan + mitigasi | `docs/plans/master-plan.md` |
| Analisa gap tema modern vs klasik | `docs/plans/gap-analysis-modern-classic.md` |
| Bug class registry (agar tidak berulang) | `docs/regressions/README.md` |
| Keputusan arsitektur | `docs/decisions/` |

| File arsip | Isi | Alasan diarsipkan |
|---|---|---|
| `IMPLEMENTATION_PLAN.md` | Rencana implementasi awal | Sudah digantikan `docs/plans/master-plan.md` |
| `EXECUTION_SUMMARY.md` | Ringkasan progres lama | Status sudah tidak akurat |
| `FIX_IMPLEMENTATION_SUMMARY.md` | Ringkasan satu batch perbaikan | Sudah tercatat di commit + registry |
| `CLASSIC_THEME_ICON_FIX.md` | Catatan perbaikan ikon tema klasik | Selesai, tercatat di commit |
| `TROUBLESHOOTING_GUIDE.md` | Panduan troubleshooting awal | Sebagian sudah masuk `docs/regressions/` |

Aturan: dokumen baru **jangan** ditaruh di root proyek. Taruh di `docs/plans/`,
`docs/decisions/`, atau `docs/regressions/`.
