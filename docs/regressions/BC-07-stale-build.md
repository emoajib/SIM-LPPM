# BC-07 — Stale build asset

**Gejala:** halaman modern kosong/blank setelah deploy; perubahan React tidak tercermin di produksi; `public/build` lama.

**Akar masalah:** deploy script tidak menjalankan `npm run build`; developer lupa commit `public/build`; produksi menyajikan aset lama.

**Perbaikan:** build + commit `public/build` tiap perubahan JS/CSS; nama asset hash berubah diverifikasi.

**Penjaga:** guard manifest di CI (`public/build/manifest.json` harus berubah bila `resources/js|css` berubah); langkah verifikasi di `update_production.sh`.

**Aturan:**
1. Setiap perubahan `resources/js` atau `resources/css` wajib `npm run build` + commit `public/build` di commit yang sama.
2. Deploy script wajib memverifikasi manifest terbaru.
3. Smoke test aset setelah deploy.

**Kasus:** 2026-11-12 (blank setelah deploy, sebelum build commit).
