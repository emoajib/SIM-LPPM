# BC-14 — maskot banner tidak berubah setelah upload karena berkas hanya tersimpan saat tombol Simpan Maskot diklik

**Gejala:** admin LPPM memilih berkas maskot, lalu memilih ukuran (mis. "Besar") dan menekan
"Simpan Ukuran". Flash "Ukuran maskot banner berhasil diperbarui." muncul, tetapi dashboard
tetap menampilkan SVG maskot bawaan, bukan berkas yang dipilih.

**Akar masalah:** `wire:model="mascot"` bersifat **deferred**. Berkas baru diunggah ke temporary
storage pada request Livewire berikutnya, dan hanya `saveMascot()` yang menuliskannya ke media
library. Menekan tombol Livewire lain (`saveMascotSize`) memicu request tersebut, tetapi berkas
temporary dibuang di akhir request tanpa pernah disimpan → media `mascot` tidak ada, sehingga
`Setting::mediaUrl('dashboard_mascot', 'mascot')` bernilai null dan blade merender SVG bawaan.

**Reproduksi (sebelum fix):**

| Skenario | Flash | Media tersimpan | Dashboard |
|---|---|---|---|
| Pilih berkas saja | — | tidak | SVG bawaan |
| Pilih berkas + "Simpan Maskot" | success | ya | gambar |
| Pilih berkas + "Simpan Ukuran" | success (ukuran) | **tidak** | **SVG bawaan** |

**Perbaikan:**
- `app/Livewire/Settings/Appearance.php`
  - Hook `updatedMascot()` → `saveMascot()`: berkas langsung disimpan begitu dipilih.
  - `saveMascotSize()` menyimpan berkas yang masih pending lebih dulu; bila validasi gagal,
    ukuran tidak disimpan agar berkas tidak hilang diam-diam.
  - `saveMascot()` tanpa berkas kini memberi error yang jelas (bukan flash sukses palsu).
  - Hook serupa untuk avatar default: `updatedAvatarMale()` / `updatedAvatarFemale()`.
- `resources/views/livewire/settings/appearance.blade.php`
  - Teks "Berkas langsung tersimpan begitu dipilih", indikator status **Maskot aktif**
    (nama berkas + tautan "Lihat gambar"), spinner saat mengunggah, dan tombol
    "Unggah Ulang / Simpan".

**Penjaga:**
- `tests/Feature/Regression/BC14Test.php` — memilih berkas langsung menyimpan media,
  "Simpan Ukuran" tidak membuang berkas pending, dan dashboard merender URL maskot baru.
- `tests/Feature/Settings/AppearanceMascotTest.php`, `tests/Feature/Settings/GenderAvatarTest.php`
  disesuaikan ke alur auto-save + kasus simpan tanpa berkas.

**Aturan:**
1. Test penjaga wajib GAGAL sebelum fix dan LULUS setelah fix.
2. Setiap perubahan terkait wajib menyebut `BC-14` di body commit.
3. Pola "upload deferred + tombol Livewire lain" harus diperiksa di form lain yang punya
   `wire:model` berkas (logo, avatar, template): pastikan berkas pending tidak pernah
   dibuang tanpa disimpan atau tanpa pesan error.

**Kasus:** 2026-11-20 — area `mascot` (Pengaturan › Tampilan), dilaporkan setelah fitur
ukuran maskot (Kecil/Sedang/Besar) ditambahkan.
