# Gap Analysis: Modern vs Classic Theme

Analisa paritas tampilan & fitur antara tema modern (React) dan klasik (Blade/Livewire)
untuk ekosistem: **Usulan, Perbaikan Usulan, Laporan Akhir, Realisasi Anggaran, Verifikasi Luaran**,
untuk **semua role** (Dosen, Reviewer, Admin LPPM, Kepala LPPM, Dekan, Kaprodi, Rektor, Superadmin).

Status: analisa awal (belum semua gap ditutup). Setiap gap disertai mitigasi.

## 1. Usulan (Proposal)

### Modern
- **Index (ProposalsIndex.jsx)**: kaya — search, status, role (ketua/anggota), year, scheme, faculty, study program, pagination, empty state, countdown timer, eligibility modal.
- **Detail (ProposalShowIndex.jsx)**: read-only data (RAB, tim, reviewer, riwayat status, luaran, kontrak). Tombol native: Ajukan (P1b), Setujui/Minta Revisi/Tolak (P1c, Kepala LPPM). Panel Aksi lanjutan (tampilan klasik) untuk sisanya.
- **Create/Edit**: tidak ada di modern -> taut ke Blade klasik (diterima).

### Classic
- Full CRUD, workflow actions, kontrak number editor, daftar surat, kelola tim, dokumen mitra, activity timeline, PDF export/preview, status history, role tabs.

### Gap
| Gap | Role terdampak | Mitigasi | Status |
|---|---|---|---|
| Detail modern tidak punya kontrak editor, surat, tim, mitra, timeline, hapus, amandemen RAB | Admin LPPM, Kepala LPPM, Dosen | Panel "Aksi lanjutan" ke klasik (sudah) + native bertahap | Sebagian (P1a/P1b/P1c) |
| Create/Edit hanya di klasik | Dosen | Terima (form panjang, Blade lebih stabil) | Diterima |

## 2. Perbaikan Usulan (Proposal Revision)

### Modern (ModuleSubpageIndex)
- List: search, pagination, status, submitter, tanggal, "Lihat"
- Tidak ada filter: status, year, role (ketua/anggota), faculty, study program
- "Lihat" -> /proposal/{id}/modern (detail proposal, bukan revision-specific view)
- Tidak ada ekspor

### Classic
- Filter year, role tabs, review notes, form revisi, penugasan reviewer

### Gap
| Gap | Role terdampak | Mitigasi | Status |
|---|---|---|---|
| Index modern "search only" (tanpa filter status/year/role/faculty) | Semua | Tambah filter ke ModuleSubpageIndex + kirim ke API (API sudah support) | **SELESAI** (W3-1) |
| "Lihat" mengarah ke detail proposal, bukan revision view | Reviewer, Admin | `actionUrlFn` -> `/research/proposal-revision/{id}` (show klasik) | **SELESAI** |
| Tidak ada role tabs (ketua/anggota) untuk dosen | Dosen | Select peran (ketua/anggota) di ModuleSubpageIndex | **SELESAI** (W3-2) |

## 3. Laporan Akhir (Final Report)

### Modern (ModuleSubpageIndex)
- List: search, pagination, status, submitter, tanggal
- Tidak ada filter: status cards (all/approved/submitted/belum_laporan), year, faculty, study program
- Tidak ada role tabs (ketua/anggota)
- Tidak ada halaman detail modern (selalu klasik final-report/{proposal})
- Tidak ada ekspor

### Classic
- Role tabs, status filter cards, year filter, faculty filter, "belum_laporan", ekspor

### Gap
| Gap | Role terdampak | Mitigasi | Status |
|---|---|---|---|
| Index modern "search only" | Semua | Tambah filter ke ModuleSubpageIndex | **SELESAI** (W3-1) |
| Role tabs hilang | Dosen | Select peran ketua/anggota | **SELESAI** (W3-2) |
| Status filter cards hilang (approved/submitted/belum_laporan) | Admin LPPM, Kepala LPPM | Kartu status dari `meta.stats` API + filter laporan (bukan status proposal) | **SELESAI** (W3-3) |
| Tidak ada detail modern | Semua | Tetap klasik (form kompleks) - terima | Diterima |

## 4. Realisasi Anggaran (Daily Note)

### Modern (DailyNoteIndex.jsx - full editable)
- Entri CRUD + bukti transaksi (upload/hapus)
- Ringkasan RAB vs realisasi, selisih, %
- Amandemen: daftar + setujui/tolak + indikator pending
- Sahkan LPJ (approve logbook)
- Banner "LPJ dikembalikan"
- Module-aware (research/community-service) - diperbaiki
- Unggah scan LPJ (tanda tangan basah) - klasik only
- Form ajukan amandemen (edit item RAB) - klasik only (P3)
- Ekspor PDF logbook - klasik only
- Detail catatan per entri (read-only view) - klasik only

### Modern (ModuleSubpageIndex daily-note list)
- Kolom RAB/Realisasi/Selisih/%
- "search only" (tanpa filter status/year/role/faculty)

### Classic
- Full: role tabs, ringkasan, entri CRUD, bukti, amandemen (request + decide), scan upload, ekspor PDF, return notes

### Gap
| Gap | Role terdampak | Mitigasi | Status |
|---|---|---|---|
| Scan LPJ upload tidak ada di modern | Kepala LPPM, Admin LPPM | Taut "Aksi lanjutan" ke klasik (sudah) | Diterima (via klasik) |
| Form ajukan amandemen tidak ada di modern | Dosen | Taut ke klasik (P3 nanti) | Diterima |
| Ekspor PDF logbook tidak ada di modern | Semua | Taut ke klasik | Diterima |
| Index list "search only" | Semua | Tambah filter ke ModuleSubpageIndex | **SELESAI** (W3-1) |

## 5. Verifikasi Luaran

### Modern (VerifikasiLuaranIndex.jsx, 867 baris)
- Search, status, type filter
- Verify/unverify/rank/status/evidence/detail
- canVerify/isDosen dari API; blok edit owner
- Module-aware (research/community-service)
- Tombol verifier hanya untuk verifier
- Filter faculty/study program/year mungkin hilang (params: search/status/type/page/module)

### Classic (iku-verification.blade.php)
- Search, type, status filter

### Gap
| Gap | Role terdampak | Mitigasi | Status |
|---|---|---|---|
| Filter faculty/study program/year | — | **Tidak ada gap**: index klasik (`iku-verification.blade.php`) juga hanya punya search/type/status. Scoping fakultas/prodi sudah otomatis di server (dekan/kaprodi) | Terverifikasi (W3-4) |

## 6. Cross-cutting gap (paling berdampak)

### A. ModuleSubpageIndex "search only" (gap terbesar) — **SELESAI**
Eksosistem terdampak: Perbaikan Usulan, Laporan Akhir, Realisasi Anggaran (index).
Role terdampak: semua (dosen kehilangan role tabs; admin kehilangan filter faculty/study program/year/status).
Akar masalah: ModuleSubpageIndex hanya mengirim search, type, submodule, page; API index sudah mendukung status, year, faculty_id, study_program_id, scheme_id, role.
Mitigasi diterapkan: filter status, tahun akademik, skema, fakultas, prodi, dan peran (ketua/anggota) dikirim ke API; `apiBase` module-aware (penelitian vs pengabdian); kartu status Laporan Akhir dari `meta.stats`.
Penjaga: `tests/Feature/Api/ModuleSubpageFilterParityTest.php` + `tests/Feature/Theme/ModernFilterContractTest.php`.

### B. Modern detail tanpa aksi tulis (sebagian ditutup)
Mitigasi yang sudah diterapkan: panel "Aksi lanjutan ke klasik" + native Ajukan/Setujui/Tolak.
Sisa: kontrak editor, surat, tim, mitra, timeline, hapus, amandemen request, scan upload, ekspor PDF, detail laporan akhir.
Keputusan: tetap via klasik (hybrid). Native hanya untuk yang sering dipakai.

### C. Create/Edit form hanya di klasik
Keputusan: diterima - form panjang & validasi kompleks lebih stabil di Blade.

### D. Role tabs (ketua/anggota) hilang di modern sub-pages — **SELESAI**
Mitigasi: select peran ketua/anggota di ModuleSubpageIndex (konsisten dengan halaman Usulan modern).

### E. Otorisasi tidak seragam antar tema — **SELESAI**
Gejala: halaman modern hanya memeriksa role global, sehingga dosen tanpa relasi bisa membuka detail; `ProposalPolicy` klasik belum mengizinkan kaprodi; API memakai salinan aturan sendiri. Akibatnya hasil 200/403 berbeda antar tema.
Mitigasi: `ProposalPolicy` menjadi sumber tunggal (`view`, `viewDailyNote`, `manageDailyNote`) untuk `ModernPageController`, `ModernProposalController`, `DailyNoteController`, dan Livewire Daily Note. Reviewer hanya boleh membaca proposal yang ditugaskan; reviewer tanpa penugasan tetap ditolak. Kaprodi diizinkan pada prodi yang sama.
Penjaga: `tests/Feature/Security/ModernAccessBolaTest.php` (matriks lintas peran, tema, API, dan unit).

### F. URL detail modern tidak kanonik — **DITERIMA (Opsi A)**
Gejala: index penelitian menautkan `/research/proposal/{id}/modern`, sementara pengabdian memakai `show_url`, sehingga URL detail tidak konsisten.
Mitigasi rilis ini: semua index memakai `p.show_url` (URL klasik kanonik) + tombol eksplisit "Tampilan Modern" saat tema modern aktif.
Rencana jangka panjang (Opsi B): URL detail theme-aware `/{module}/proposal/{id}` dengan `classic_url` untuk panel aksi; butuh audit 84 referensi `proposal.show` dan 10 `wire:navigate`.

### G. `DashboardIndex.jsx` tidak punya route sendiri — **DITERIMA (Opsi A, dokumentasi)**
Gejala: komponen `dashboard-index` ada di `resources/js/modern.jsx` tetapi tidak terdaftar di `config/modern-pages.php` sebagai halaman ber-route.
Keputusan: **dipertahankan** — komponen didaftarkan di `allowed_unrouted_components` sehingga `modern:check` tetap hijau. Dashboard klasik (`livewire/dashboard/main-dashboard.blade.php`) tetap menjadi sumber utama; komponen React ini dipakai sebagai referensi/cadangan, bukan halaman aktif.
Rencana: hapus hanya bila dashboard modern dirombak (Fase 7), bukan di rilis ini.

### H. Bulletin dashboard masih client-side — **DITERIMA (backlog)**
Gejala: bulletin hanya dibaca/ditulis lewat `localStorage['lppm_dashboard_bulletins']` di `modern-infographic.blade.php`; tidak ada migrasi/model/API.
Keputusan: tidak ada dampak produksi (data default dipakai bila localStorage kosong). Migrasi ke server-side butuh migrasi + model + endpoint + refactor view — dijadwalkan sebagai backlog opsional Fase 7, bukan blocker rilis.

## 7. Rekomendasi prioritas penutupan gap

| Prioritas | Item | Effort | BC terkait | Status |
|---|---|---|---|---|
| 1 | Tambah filter (status, tahun akademik, role, faculty, study program) ke ModuleSubpageIndex | M | BC-01, BC-03 | SELESAI |
| 2 | Tambah role tabs (ketua/anggota) di ModuleSubpageIndex | S | BC-01 | SELESAI |
| 3 | Tambah status filter cards di final-report index modern | S | BC-01 | SELESAI |
| 4 | Native: kontrak editor, surat list, tim management (opsional) | L | BC-08 | DITERIMA (via klasik) |
| 5 | Native: scan upload, amandemen request, ekspor PDF (opsional) | L | BC-08 | DITERIMA (via klasik) |
| 6 | Verifikasi filter faculty/year di Verifikasi Luaran modern | S | BC-01 | SELESAI (tidak ada gap) |
| 7 | "Lihat" di Perbaikan Usulan/Laporan Akhir mengarah ke halaman yang tepat | S | BC-01 | SELESAI |
| 8 | Satukan otorisasi modern/klasik/API di `ProposalPolicy` | M | BC-06 | SELESAI |
| 9 | URL detail modern theme-aware (Opsi B) | L | BC-01 | DITERIMA (Opsi A; Opsi B jangka panjang) |

## 8. Mitigasi sistemik (agar gap tidak berulang)

1. Parity test per halaman: ThemeParityTest sudah ada (BC-01) - perlu diperluas dengan assertion fitur kunci (filter ada, tombol ada).
2. Registry config/modern-pages.php (P2-B1): setiap halaman modern wajib punya entry + daftar fitur yang diparitaskan.
3. Checklist PR (P2-C2): "fitur baru di klasik -> apakah modern juga? jika tidak, dokumentasikan sebagai gap diterima."
4. Audit berkala: jalankan history:brief <area> sebelum mengerjakan fitur baru, untuk tahu gap yang masih terbuka.

---

Catatan: gap yang ditandai "Diterima" adalah keputusan sadar (hybrid), bukan bug. Gap yang ditandai "Belum" adalah pekerjaan yang masih perlu dikerjakan.
