# BC-10 — Pagu anggaran per-skema tidak bisa diedit/disimpan (modal read-only)

**Gejala:** Admin LPPM menekan "Edit" pada Batas Anggaran Tahunan; modal terbuka tetapi kolom pagu per-skema kosong dan form tidak bisa disimpan. Tombol "Tambah Pengaturan Anggaran" juga ikut mati (modal yang sama).

**Akar masalah:** `resources/js/app.js` — komponen Alpine `moneyInputSingle` memakai
`this.$watch(\`$wire.${wireModelName}\`, ...)`. Model pagu per-skema ber-titik
(mis. `research_scheme_caps.12`), sehingga Alpine meng-compile
`$wire.research_scheme_caps.12` → **`SyntaxError: Unexpected number`**.
Alpine menjalankan seluruh directive satu halaman dalam satu antrean
`flushHandlers()`; satu handler yang melempar error menghentikan antrean, jadi
semua directive sesudahnya (input Rupiah lain, `wire:model` toleransi,
`wire:submit="save"`) tidak pernah terpasang. Server selalu sehat — `edit()`
mengisi prop dan `save()` menyimpan dengan benar.

**Perbaikan:**
- Watcher diganti menjadi getter dot-path aware:
  `this.$watch(() => this.$wire.get(wireModelName), (value) => this.updateDisplay(value))`
  dibungkus `try/catch` agar satu kegagalan tidak mematikan antrean directive.
- `handleInput()` cabang kosong memakai `this.$wire.set(wireModelName, null, false)`
  (tanpa request server per ketukan).
- Grup tab Anggaran & RAB di `settings/master-data` dikunci untuk
  `admin lppm`/`superadmin`, dengan guard `mount()` yang sama di
  `BudgetGroupManager` dan `BudgetComponentManager` (sebelumnya tanpa guard).

**Penjaga:**
- `tests/Feature/Regression/BC10AlpineDotPathWatchTest.php` — kontrak sumber
  (`$wire.get` dipakai, bentuk `$watch(\`$wire.${...}\`)` hilang, dot-path tetap
  dirender view).
- `tests/Feature/Settings/BudgetCapManagerTest.php` — `edit()` mengisi prop
  per-skema, `save()` menyimpan, duplikat ditolak, dosen/dekan 403,
  superadmin boleh.

**Aturan:**
1. Jangan pernah memakai string `$watch(\`$wire.${nama}\`)` untuk model ber-titik;
   gunakan getter + `$wire.get()`.
2. Setiap komponen Alpine pembungkus input wajib `try/catch` pada inisialisasi
   agar kegagalan satu elemen tidak mematikan directive lain di halaman yang sama.
3. Master data anggaran hanya untuk `admin lppm`/`superadmin` (gunakan
   `activeHasRole()`, bukan `hasRole()`).
4. Setiap perubahan terkait wajib menyebut `BC-10` di body commit.

**Kasus:** 2026-11-18 — `settings/master-data?group=budget&tab=budget-caps`
(30 error `SyntaxError` identik di `storage/logs/browser.log`).
