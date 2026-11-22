# BC-18 — Kartu Riwayat Tri Dharma Terakhir di dashboard modern hanya menampilkan placeholder statis

**Gejala:** Kartu Riwayat Tri Dharma Terakhir di dashboard modern hanya menampilkan placeholder statis

**Akar masalah:** Blade menampilkan teks 'Belum ada riwayat...' dan item-item dikomentari, tidak pernah merender $recentProposals.

**Perbaikan:**
- Render @forelse $recentProposals->take(5) dengan badge tipe, judul, tahun, tautan show.

**Penjaga:**
- `tests/Feature/Regression/BC18Test.php`

**Aturan:**
1. Test penjaga wajib GAGAL sebelum fix dan LULUS setelah fix.
2. Setiap perubahan terkait wajib menyebut `BC-18` di body commit.
3. Bila pola ini muncul di halaman/modul lain, periksa juga di sana (bukan hanya titik yang dilaporkan).

**Kasus:** 2026-11-22 — area `modern`
