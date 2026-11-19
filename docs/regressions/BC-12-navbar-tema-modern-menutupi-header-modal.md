# BC-12 — navbar tema modern menutupi header modal

**Gejala:** navbar tema modern menutupi header modal; modal mulai di y=28 sehingga menimpa area header 133px (2 baris header)

**Akar masalah:** body.theme-modern .navbar z-index 2147483647 (agar dropdown header tidak tertutup banner) berada di atas .modal (1055) dan .modal-backdrop (1050), sehingga judul modal, tombol close, dan field atas tertutup navbar

**Perbaikan:**
- body.modal-open .navbar/.page-header (dan dropdown) z-index auto !important; modal Batas Anggaran dibuat scrollable agar footer selalu terlihat
- Offset dinamis: wrapper header diberi id="site-header"; app.js mengukur bottom header saat modal dibuka dan menyimpannya di CSS var --sllp-modal-top; app.css memakai var untuk top & height .modal sehingga dialog mulai di bawah header (161px = 133 + 28) dengan gap 28px. Jika header keluar layar (page scrolled), offset 0 → modal center penuh.

**Penjaga:**
- `tests/Feature/Regression/BC12ModalNavbarZIndexTest.php` — kontrak sumber: aturan override `body.modal-open .navbar`/`.page-header` ada di `app.css` dan termuat di bundle `public/build/assets/app-*.css`; modal Batas Anggaran memakai `scrollable`.
- Reproduksi Playwright (modern theme): sebelum fix `elementFromPoint` di judul modal = `.container-xl` (navbar); sesudah fix = `h5.modal-title`; field Tahun bisa diketik; footer terlihat (scrollable).

**Kasus tambahan:** modal Batas Anggaran Tahunan (admin lppm) tinggi 1245px > viewport 900px → footer Batal/Simpan di luar layar; `modal-dialog-scrollable` membatasi 844px dengan scroll internal.

**Aturan:**
1. Test penjaga wajib GAGAL sebelum fix dan LULUS setelah fix.
2. Setiap perubahan terkait wajib menyebut `BC-12` di body commit.
3. Bila pola ini muncul di halaman/modul lain, periksa juga di sana (bukan hanya titik yang dilaporkan).

**Kasus:** 2026-11-19 — area `settings`
