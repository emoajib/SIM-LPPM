# Backlog Modernisasi — Item #30–#47

Tanggal triase: 2026-11-16
Asal: `/Users/salsabil/.opencode/plan/sim-lppm-modern-theme-plan.md` (Fase 2–6 + Sprint 6)
Status: **backlog terpisah, BUKAN bagian Fase 7**
Estimasi total: **±3–4 bulan kerja** (tidak realistis sebagai satu fase)

Dokumen terkait:
- `docs/plans/fase7-analisa-modernisasi.md` — analisa Fase 7 (dasar triase ini)
- `docs/plans/gap-analysis-modern-classic.md` — paritas modern ↔ klasik
- `docs/plans/master-plan.md` — status gelombang modernisasi

---

## 1. Mengapa dikeluarkan dari Fase 7

Fase 7 semula memuat satu baris "F7-09 Triase #30–#47". Setelah isinya diperiksa satu per satu:

- 16 item (bukan 4–5), dengan rentang 3 hari s/d 7 minggu.
- 5 item adalah **fitur baru**, bukan modernisasi tampilan (#37, #38, #39, #42, #44).
- 3 item bertabrakan dengan keputusan arsitektur "alur tulis kompleks tetap klasik" (#30, #43, #44).
- Total ±**3–4 bulan**; menempatkannya sebagai "fase opsional" membuat rencana menyesatkan.

Karena itu isinya dipindahkan ke dokumen ini, dengan verdict eksplisit per item agar tidak menjadi utang tak terlihat.

---

## 2. Ringkasan verdict

| Verdict | Item | Jumlah |
|---|---|---|
| **A. Kerjakan** (kandidat batch 7F, setelah 7A–7E) | #31, #32, #34, #36, #45, #47 | 6 |
| **B. Digabung** ke item Fase 7 yang sudah ada | #45 → F7-04/F7-05, #47 → F7-05 | (2) |
| **C. Terima gap** (keputusan sadar, tetap klasik) | #30, #41, #43, #44 | 4 |
| **D. Tunda** (butuh keputusan arsitektur/ops) | #35, #40, #42 | 3 |
| **E. Proyek terpisah** (domain/infra, bukan tema) | #37, #38, #39 | 3 |

Catatan: #45 dan #47 dihitung di verdict A tetapi eksekusinya melalui F7-04/F7-05 (tidak perlu batch baru).

---

## 3. Tabel triase lengkap

| # | Item (asal) | Effort asal | Verdict | Alasan berbasis kode | Aksi konkret |
|---|---|---|---|---|---|
| 30 | Proposal Create/Edit — form wizard + validasi + upload | 4 hari | **C. Terima gap** | Form usulan adalah alur tulis kompleks (validasi bertingkat, upload multi-file, RAB, tim, eligibility). Klasik sudah stabil; React akan menduplikasi logika validasi server. | Pertahankan klasik. Bila perlu, tambah tautan "Tampilan Modern" hanya untuk mode **baca**. |
| 31 | Laporan Akhir Show detail | 3 hari | **A. Kerjakan** | Pola sudah ada: `ProposalShowIndex.jsx` + API `proposals/{id}`. Laporan akhir hanya butuh view read-only (ringkasan, realisasi, luaran, riwayat). | Batch 7F: komponen read-only + route `/…/final-report/{proposal}/modern`. Wajib `LaporanPolicy` (BC-06). |
| 32 | Perbaikan Usulan Show detail | 3 hari | **A. Kerjakan** | Sama seperti #31; `Revision` sudah punya endpoint list & filter. | Batch 7F: reuse komponen #31 dengan parameter sub-modul. |
| 34 | Analisa komparasi luaran dijanjikan vs dilaporkan | 2 hari | **A. Kerjakan** | Data tersedia (`output_reports` + luaran yang dijanjikan pada proposal); tidak ada UI komparasi di kedua tema. Nilai tinggi untuk verifikasi. | Batch 7F: 1 endpoint agregat + tabel komparasi (dijanjikan / dilaporkan / selisih). |
| 35 | Monev | 4 hari | **D. Tunda** | Monev melibatkan penilaian, status, dan keputusan (tulis). Kompleksitas setara approval, tetapi frekuensi rendah. | Tunda sampai F7-04 (Approval Inbox) terbukti; bila jalan, buat read-only dulu. |
| 36 | Admin LPPM Dashboard | 3 hari | **A. Kerjakan** (setelah F7-10) | `DashboardIndex.jsx` yang lama justru duplikat + 4 tautan mati; dashboard baru harus dibangun di atas endpoint agregat, bukan komponen mati itu. | Batch 7F, setelah F7-05 menyediakan endpoint agregat. Jangan aktifkan `DashboardIndex.jsx`. |
| 37 | Modul Pengajuan Anggaran Hibah Internal | 7 minggu | **E. Proyek terpisah** | Fitur domain baru (skema anggaran, approval berjenjang, pencairan) — bukan pekerjaan tema. | Kelola sebagai proyek/roadmap sendiri dengan dokumen kebutuhan terpisah. |
| 38 | Verifikasi/ubah nilai reviewer | 3 minggu | **E. Proyek terpisah** | Menyentuh aturan penilaian, audit trail, dan kemungkinan konflik kepentingan. Perlu desain domain + persetujuan kebijakan. | Proyek terpisah; prasyarat: audit `ReviewLog` (BC-03) dan kebijakan perubahan nilai. |
| 39 | Optimasi `IdentityEligibilityAction` | 1 minggu | **E. Proyek terpisah** | Pekerjaan performa backend (query/kaching), tidak ada kaitan dengan React/Blade. | Masukkan ke backlog performa; ukur dulu dengan data produksi sebelum optimasi. |
| 40 | Halaman modern monev & laporan institusional | 2 minggu | **D. Tunda** | Bergantung pada #35 dan #42; keduanya belum diputuskan. | Tunda; tinjau ulang setelah #35/#42 diputuskan. |
| 41 | Settings | 2 minggu | **C. Terima gap** | ±4.185 baris Livewire, 20 komponen, termasuk aksi destruktif (restore DB, unduh backup) dan matriks RBAC. | Terima klasik. Bila dibutuhkan, hanya subset Profil + Appearance + Password (lihat F7-03). |
| 42 | Laporan Institusional | 2 minggu | **D. Tunda** | Kebutuhan laporan institusional belum dibakukan (penerima, periode, format). Risiko membangun UI untuk data yang belum jelas. | Tunda sampai format laporan disepakati. |
| 43 | Surat | 1 minggu | **C. Terima gap** | 3 dashboard Livewire (dosen/admin/kepala LPPM) ±1.200 baris + `HasLetterForm` + `GenerateLetterPdfJob`. Ini 3 alur tulis, bukan 1 halaman. | Terima klasik (lihat F7-02). Opsional: 1 halaman React read-only "Daftar Surat". |
| 44 | Budget Amendment | 1 minggu | **C. Terima gap** | Amandemen RAB = alur tulis + approval; sudah ada di klasik & daily-note modern (approve/reject). Menduplikasi di React menambah risiko konsistensi. | Terima klasik. |
| 45 | Dekan/Kaprodi/Rektor Dashboard | 2 minggu | **B. Digabung** | Kebutuhan intinya = daftar tugas & antrean approval per peran, yang sudah dicakup F7-04 (Approval Inbox) + F7-05 (widget tugas). | Tidak perlu halaman dashboard terpisah per peran; cukup widget + inbox. |
| 47 | Tampilan data dashboard | 3 hari | **B. Digabung** | "Tampilan data dashboard" = kartu statistik + daftar ringkas, tepat isi `TaskWidget`/dashboard agregat F7-05. | Selesaikan lewat F7-05. |

---

## 4. Urutan bila backlog ini mulai dikerjakan

Prasyarat: **batch 7A–7E selesai** (lihat `fase7-analisa-modernisasi.md` §5).

| Batch | Isi | Effort | Prasyarat |
|---|---|---|---|
| **7F-1** | #31 + #32 detail read-only (laporan akhir & perbaikan usulan) | 5–6 hari | F7-04 pola komponen |
| **7F-2** | #34 komparasi luaran dijanjikan vs dilaporkan | 2 hari | — |
| **7F-3** | #36 dashboard Admin LPPM (di atas endpoint agregat) | 3 hari | F7-05 endpoint agregat |
| **Ditunda** | #35, #40, #42 | — | keputusan produk |
| **Terima gap** | #30, #41, #43, #44 | — | keputusan sadar (dokumentasikan di gap analysis) |
| **Proyek terpisah** | #37, #38, #39 | — | dokumen kebutuhan sendiri |

Estimasi bagian yang direkomendasikan: **±10–11 hari kerja**.

---

## 5. Aturan agar backlog ini tidak "hilang"

1. Setiap item yang diputuskan **terima gap** wajib tercatat di `docs/plans/gap-analysis-modern-classic.md` dengan alasan + mitigasi.
2. Item **proyek terpisah** tidak boleh dikerjakan lewat commit tema; harus punya dokumen kebutuhan.
3. Bila ada item backlog yang mulai dikerjakan, pindahkan ke plan aktif dengan ID baru (mis. `F7F-01`) agar tidak ada dua sumber kebenaran.
4. Triase ulang setiap akhir fase (Fase 8) — verdict bisa berubah bila keputusan arsitektur berubah.

---

## 6. Gate wajib (sama seperti Fase 7)

```
php artisan test                                  # 0 failed
./vendor/bin/pint --test && ./vendor/bin/phpstan analyse --level=5
php artisan theme:check && php artisan modern:check \
  && php artisan regression:check && php artisan copy:check
php artisan route:cache && php artisan route:clear
npm run build                                     # commit public/build (BC-07)
```

Test penjaga baru wajib memuat marker `Regression: BC-01` (theme boundary) atau `BC-06` (otorisasi).
