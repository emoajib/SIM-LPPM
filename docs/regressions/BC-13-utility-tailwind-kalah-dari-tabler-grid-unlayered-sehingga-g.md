# BC-13 — utility Tailwind kalah dari Tabler .grid unlayered sehingga grid-cols-* diabaikan, form gepeng, overflow horizontal

**Gejala:** utility Tailwind kalah dari Tabler .grid unlayered sehingga grid-cols-* diabaikan, form gepeng, overflow horizontal

**Akar masalah:** Tabler mendefinisikan .grid (12 kolom, unlayered). Tailwind v4 meletakkan utility di @layer utilities; unlayered selalu menang, sehingga grid-cols-* diabaikan dan col-span-* memecah konten jadi 12 kolom sempit

**Perbaikan:**
- Netralkan `#modern-root .grid` (grid-template-columns/rows: none) lalu tegakkan kembali utility grid yang dipakai (grid-cols-1/2, sm/md/lg variant) di `resources/css/modern.css`.
- Grid form Profil & Konfigurasi Notifikasi diubah ke `sm:grid-cols-2` agar `sm:col-span-2` punya 2 track (bukan 12).
- Bonus temuan saat verifikasi: menu horizontal header (`#site-header .navbar-collapse .navbar-nav`) memakai `flex-nowrap` Tabler sehingga mendorong lebar dokumen di 768–1440px. Diubah menjadi `flex-wrap: wrap` (resources/css/app.css).
- Bonus temuan saat verifikasi: baris berkas + tombol "Unduh" di `/settings/proposal-template`
  memakai `d-flex justify-content-between` tanpa `flex-wrap`/`min-width:0`, sehingga tombol
  meluber keluar kartu pada lebar <=1024px. Ditambahkan `flex-wrap`, `gap-2`, `min-width:0`,
  dan `text-break` pada nama berkas (resources/views/livewire/settings/proposal-template.blade.php).

**Penjaga:**
- `BC13TailwindGridLayerTest` — memastikan netralisasi `#modern-root .grid`, mirror utility grid, grid form 2 kolom, dan bundle CSS produksi memuat aturan tersebut.
- `DashboardMascotRenderTest` + `AppearanceMascotTest` — memastikan upload maskot banner tidak merusak render dashboard.

**Aturan:**
1. Test penjaga wajib GAGAL sebelum fix dan LULUS setelah fix.
2. Setiap perubahan terkait wajib menyebut `BC-13` di body commit.
3. Bila pola ini muncul di halaman/modul lain, periksa juga di sana (bukan hanya titik yang dilaporkan).

**Kasus:** 2026-11-20 — area `modern`
