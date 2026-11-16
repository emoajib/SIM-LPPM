# BC-06 — IDOR / BOLA

**Gejala:** user bisa melihat/mengunduh data milik orang lain (proposal, surat, verifikasi luaran); dekan/kaprodi melihat lintas unit.

**Akar masalah:** endpoint tidak memeriksa kepemilikan/lingkup unit; parameter id dari URL dipakai langsung tanpa policy.

**Perbaikan:** IDOR fix `ProposalExportController` (dekan per fakultas, kaprodi per prodi); `VerifikasiLuaranController` filter submitter OR anggota tim; `LetterLinkedRequestBolaTest`. Otorisasi proposal modern/API/realisasi anggaran dipusatkan di `ProposalPolicy` (`view`, `viewDailyNote`, `manageDailyNote`) sehingga klasik, modern, dan API memakai aturan yang sama; kaprodi diizinkan pada prodi yang sama dan reviewer hanya bila ditugaskan.

**Penjaga:** `tests/Feature/Security/LetterLinkedRequestBolaTest.php`, `tests/Feature/Security/ModernAccessBolaTest.php`, `tests/Feature/ReportSecurityAndLifecycleTest.php`, policy test per modul.

**Aturan:**
1. Setiap endpoint yang akses data user wajib cek kepemilikan di controller/policy.
2. Dekan diisolasi per fakultas, kaprodi per prodi.
3. Test wajib mencakup kasus lintas unit (cross-faculty/prodi).
4. Halaman modern, API, dan Livewire wajib memakai policy yang sama; dilarang menyalin aturan peran ke controller.

**Kasus:** 2026-11-12 (export proposal); 2026-11-13 (verifikasi luaran); 2026-11-16 (paritas otorisasi modern/klasik/API).
