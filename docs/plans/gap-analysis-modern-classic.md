# Gap Analysis — Tema Modern vs Klasik

Dokumen ini mencatat paritas fitur antara tema modern (React) dan klasik (Tabler/Blade).

## Halaman Modern (terdaftar di config/modern-pages.php)

| Route | Komponen React | Status |
|---|---|---|
| research.proposal.index | proposals-index | Modern |
| research.proposal-revision.index | research-revision-index | Modern |
| research.final-report.index | research-final-report-index | Modern |
| research.daily-note.index | research-daily-note-index | Modern |
| community-service.proposal.index | pengabdian-index | Modern |
| community-service.proposal-revision.index | pengabdian-revision-index | Modern |
| community-service.final-report.index | pengabdian-final-report-index | Modern |
| community-service.daily-note.index | pengabdian-daily-note-index | Modern |
| research.verifikasi-luaran.page | verifikasi-luaran-index | Modern (kedua tema) |
| community-service.verifikasi-luaran.page | verifikasi-luaran-index | Modern (kedua tema) |
| settings.*.page | *-index | Modern |

## Halaman Klasik (belum dimigrasi)

- Dashboard semua peran
- Reviewer, IKU, Monev, Laporan
- Admin LPPM, Kepala LPPM, Dekan, Kaprodi, Rektor
- Manajemen pengguna, settings non-modern
- Export/PDF

## Gap yang disengaja

- Modern hanya mencakup modul proposal, revisi, laporan akhir, catatan harian, verifikasi luaran, dan settings.
- Halaman dashboard dan administratif tetap klasik karena kepadatan informasi dan risiko regresi.

## Kriteria migrasi berikutnya

1. Paritas filter dan status card.
2. Uji smoke 8 peran × 2 tema.
3. Kontrak React ↔ API.
4. Dokumentasi gap diperbarui.

## Status

Belum diverifikasi visual; dokumentasi ini bersifat perencanaan.