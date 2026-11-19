# BC-11 — Modal terbuka tetapi tidak bisa diklik/diedit (backdrop menutupi modal)

**Gejala:** Tombol "Edit" / "Tambah Pengaturan Anggaran" pada Batas Anggaran Tahunan membuka modal, modal terlihat (termasuk nilainya), tetapi seluruh input tidak bisa diklik/diketik dan tombol "Simpan" tidak merespons. Gejala serupa dilaporkan pada modal lain di halaman yang memakai kartu (`.card`) — termasuk modal "Tolak Amandemen" yang baru ditambahkan.

**Akar masalah:** `resources/css/app.css` memberi `.page-body` dan `.card` `position: relative; z-index: 1` agar dropdown dashboard tidak tertutup kartu. Kedua elemen itu menjadi stacking context, sehingga `.modal` (z-index 1055) yang berada di dalamnya ikut terkurung dan kalah oleh `.modal-backdrop` (z-index 1050, anak langsung `<body>`). Semua klik jatuh ke backdrop: modal terlihat, nilainya terbaca, tetapi read-only — termasuk tombol Simpan.

**Perbaikan:**
- Tambahkan di `resources/css/app.css`:
  ```css
  body.modal-open .page-body,
  body.modal-open .card,
  body.modal-open .card:hover {
    z-index: auto !important;
  }
  ```
  Bootstrap menambah `modal-open` pada `<body>` saat modal terbuka, jadi stacking context dilepas hanya selama modal aktif. `.card:hover` ikut di-override karena hover juga menciptakan stacking context baru.
- Perbaikan Alpine `moneyInputSingle` (BC-10) tetap dipertahankan — itu bukan penyebab utama bug ini, tetapi tetap diperlukan agar watcher dot-path tidak melempar error.

**Penjaga:**
- `tests/Feature/Regression/BC11ModalStackingContextTest.php` — kontrak sumber: aturan override `body.modal-open .page-body`/`.card` ada di `app.css` dan termuat di bundle `public/build/assets/app-*.css`.
- Reproduksi Playwright (lokal): `elementFromPoint` pada input modal = `modal-backdrop` sebelum fix, dan = input sesudah fix; Simpan persist ke DB.

**Aturan:**
1. Jangan beri `z-index` pada `.page-body`/`.card` tanpa mempertimbangkan modal yang dirender di dalamnya.
2. Setiap modal yang dirender di dalam elemen ber-stacking-context wajib melewati smoke test klik realistis (bukan hanya `force: true`).
3. Perubahan terkait wajib menyebut `BC-11` di body commit.

**Kasus:** 2026-11-19 — halaman Pengaturan › Anggaran & RAB › Batas Anggaran Tahunan (admin lppm) di produksi; semua modal di dalam kartu juga terdampak (142 penggunaan `x-tabler.modal`).
