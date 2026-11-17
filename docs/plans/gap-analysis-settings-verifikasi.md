# Gap Analysis: Settings (Notification Config, Profile) & Verifikasi Luaran (Modern vs Classic)

**Tanggal:** 2026-11-16  
**Status:** Analisis awal untuk perencanaan Fase 7/8 lanjutan

---

## Ringkasan Eksekutif

| Fitur | Tema Klasik (Livewire) | Tema Modern (React) | Gap |
|---|---|---|---|
| **Konfigurasi Notifikasi** | ✅ Lengkap (`NotificationConfig`) | ❌ Tidak ada | **Besar** - Admin hanya bisa pakai klasik |
| **Profil Saya** | ✅ Lengkap (`ProfileForm`) | ❌ Tidak ada | **Besar** - Semua user hanya pakai klasik |
| **Verifikasi Luaran** | ✅ `IkuVerification` (hanya Research) | ✅ `VerifikasiLuaranIndex` (Research + PKM) | **Sedang** - Modern lebih kaya fitur |

---

## 1. KONFIGURASI NOTIFIKASI (`/settings → tab "Konfigurasi Notifikasi"`)

### Tema Klasik — `App\Livewire\Settings\NotificationConfig`
**Route:** `settings.notification-config` → `/notification-config` (middleware: `auth`, `verified`, `role:admin lppm|superadmin`)

**Fitur Lengkap:**
- **SMTP Email**: Host, Port, Username, Password, Enkripsi (SSL/TLS/None), From Address, From Name
- **WhatsApp**: API URL, API Key, Sender ID
- **Test Email**: Input email tujuan → kirim test via `NotificationConfigService`
- **Test WhatsApp**: Input nomor WA (format 628xxx) → kirim test via Fonnte/Wablas
- **Validasi**: `@error` blade untuk mailHost
- **Pesan feedback**: `$message` property untuk toast sukses/error

**Kode Kunci:**
```php
// mount() - cek role admin lppm/superadmin
abort_unless(Auth::user()?->activeHasAnyRole(['admin lppm', 'superadmin']), 403);

// save() - simpan ke NotificationSetting model via NotificationConfigService
// testEmail() - Mail::to()->send(new TestMail())
// testWhatsApp() - Http::post($this->waApiUrl, [...])
```

### Tema Modern — **TIDAK ADA**
- Tidak terdaftar di `config/modern-pages.php`
- Tidak ada komponen React di `resources/js/modern/`
- Tidak ada route modern untuk settings/notification

### **Gap & Risiko**
| Aspek | Dampak |
|---|---|
| Admin LPPM/Superadmin | **Wajib pakai tema klasik** untuk konfigurasi SMTP/WhatsApp |
| Konsistensi UX | Fragmentasi: settings lain ada di modern, ini tidak |
| Mobile/Responsif | Klasik sudah OK (Bootstrap), tapi modern lebih konsisten |

### **Rekomendasi**
**Prioritas: TINGGI** — Buat komponen React `NotificationConfigIndex.jsx` + daftarkan ke modern-pages.  
API sudah siap (NotificationConfigService), tinggal bungkus ke React.

---

## 2. PROFIL SAYA (`/settings/profile`)

### Tema Klasik — `App\Livewire\Settings\ProfileForm`
**Route:** `settings.profile` → `/settings/profile` (via `SettingsIndex`)

**Fitur Lengkap (378 baris):**
- **Data Dasar**: Nama, Email, Nomor WhatsApp, Foto profil (upload + crop)
- **Identitas Dosen**: NIDN, Jenis (Dosen Tetap/Tidak Tetap), SINTA ID, Scopus ID, Google Scholar ID, WoS ID, ORCID ID
- **Validasi**: Rule unik email, format nomor WA Indonesia, file image max 2MB
- **Aksi**: Simpan profil, hapus foto, sinkronisasi institusi (HandleHybridInstitution)
- **Toast**: Notifikasi sukses/error

**Kode Kunci:**
```php
// WithFileUploads trait untuk foto
// updatedPhoto() -> validasi + resize + simpan storage
// save() -> validasi + update User + ActivityLog
// deletePhoto() -> hapus file + update user->photo = null
```

### Tema Modern — **TIDAK ADA**
- Tidak terdaftar di `config/modern-pages.php`
- Tidak ada komponen React `ProfileIndex.jsx` atau serupa

### **Gap & Risiko**
| Aspek | Dampak |
|---|---|
| Semua user (dosen, admin, dll) | **Wajib pakai tema klasik** untuk edit profil |
| Upload foto | Klasik pakai Livewire `WithFileUploads` — modern butuh implementasi baru |
| Sinkronisasi institusi | `HandleHybridInstitution` action perlu dipanggil dari React |

### **Rekomendasi**
**Prioritas: TINGGI** — Buat `ProfileIndex.jsx` dengan form yang mirip.  
Gunakan API endpoint baru atau extend `SettingsIndex` jadi API-ready.

---

## 3. VERIFIKASI LUARAN — Perbandingan Detail

### Tema Klasik — `App\Livewire\Iku\IkuVerification` (`/iku/verification`)
**Route:** `accreditation.verification` → `/iku/verification`

**Fitur:**
| Fitur | Detail |
|---|---|
| **Modul** | Hanya **Research** (MandatoryOutput, AdditionalOutput, PolicyInvolvement) |
| **Filter** | Search, Type (all/publication/hki/product/pakar), Status (all/verified/unverified) |
| **Role-based filtering** | Global (admin lppm, kepala lppm, rektor, superadmin), Dekan (by faculty), Kaprodi (by prodi), Dosen (own proposals) |
| **Verifikasi** | `verify()` / `unverify()` oleh verifier (admin lppm, kepala lppm, rektor) |
| **Rank** | Update rank SINTA/Scopus via dropdown |
| **Status Penulis (Dosen)** | `updateStatus()` → status_type (draft→published) + author_status |
| **Upload Bukti** | Modal Bootstrap + JS fetch ke `/research/api/v1/verifikasi-luaran/{id}/evidence` |
| **Policy Involvement** | Khusus klasik: verifikasi kebijakan/pakar (level, organization) |
| **Pagination** | Laravel LengthAwarePaginator (server-side) |

**Keterbatasan:**
- ❌ **Tidak support Community Service (PKM)** — hanya Research
- ❌ **Tidak ada detail edit lengkap** — hanya status_type, author_status, rank, evidence
- ❌ **UI modal Bootstrap + vanilla JS** — tidak reactive seperti React

---

### Tema Modern — `VerifikasiLuaranIndex.jsx` (`/research/verifikasi-luaran` & `/community-service/verifikasi-luaran`)
**Route:** `verifikasi-luaran.page` → ditangani `ModernPageController::verifikasiLuaran()`

**Fitur:**
| Fitur | Detail |
|---|---|
| **Modul** | **Research DAN Community Service** (detect via URL path) |
| **Filter** | Search, Status (all/verified/unverified), Type (all/mandatory/additional) |
| **ModuleTabNav** | Tab navigasi antar modul (Proposal, Revisi, Laporan, Catatan, Verifikasi) |
| **Verifikasi** | `handleVerify()` / `handleUnverify()` oleh verifier |
| **Rank** | Dropdown SINTA (S1-S6) + Scopus (Q1-Q4) |
| **Status Penulis (Dosen)** | Inline select di tabel: status_type + author_status |
| **Upload Bukti** | Modal React dengan URL + file upload |
| **Edit Detail Lengkap** | **Modal besar** dengan SEMUA field: jurnal, buku, HKI, produk, media, video, DOI, ISSN, dll |
| **Role-based API** | Server-side filtering di `VerifikasiLuaranController` (sama seperti klasik) |
| **Pagination** | Client-side via API `meta` (current_page, last_page, total) |

**Keunggulan Modern:**
- ✅ **Support PKM (Community Service)** — klasik tidak punya
- ✅ **Detail edit sangat lengkap** — semua field output (journal, book, HKI, product, media, video)
- ✅ **UI Reactive** — inline select, modal, toast, loading state
- ✅ **ModuleTabNav** — navigasi konsisten antar sub-modul
- ✅ **API terpisah** — reusable, testable, bisa dipakai mobile app

**Keterbatasan Modern:**
- ❌ **Tidak ada Policy Involvement** (kebijakan/pakar) — hanya di klasik
- ❌ **Tidak ada filter "Type: publication/hki/product/pakar"** — hanya mandatory/additional
- ❌ **Verifier role check** pakai `window.__ACTIVE_ROLE__` (client-side) — seharusnya dari API response (`can_verify`)

---

### **Gap Verifikasi Luaran — Matriks**

| Fitur | Klasik (`/iku/verification`) | Modern (`/research/verifikasi-luaran`) | Catatan |
|---|---|---|---|
| Research Output | ✅ | ✅ | |
| PKM Output | ❌ | ✅ | **Modern unggul** |
| Policy Involvement | ✅ | ❌ | **Klasik unggul** (khusus rektor/admin) |
| Filter Type (pub/hki/prod/pakar) | ✅ | ❌ | Klasik lebih granular |
| Filter Type (mandatory/additional) | ❌ | ✅ | Modern beda kategori |
| Verifikasi (verify/unverify) | ✅ | ✅ | Sama |
| Rank SINTA/Scopus | ✅ | ✅ | Sama |
| Status Penulis (dosen) | ✅ | ✅ | Modern: inline select; Klasik: updateStatus method |
| Upload Bukti | ✅ (modal BS) | ✅ (modal React) | Modern lebih UX |
| Edit Detail Lengkap | ❌ | ✅ | **Modern jauh lebih kaya** |
| Pagination | Server-side (Livewire) | Client-side (API) | |
| Role-based Filtering | ✅ (Livewire) | ✅ (API Controller) | Sama logikanya |
| Module Navigation | ❌ (standalone) | ✅ (ModuleTabNav) | Modern terintegrasi |

---

## 4. RENCANA KERJA (Work Plan)

### Fase 7A — Settings Modernisasi (Prioritas Tinggi)
**Target:** 2-3 hari

| Task | Deskripsi | Estimasi |
|---|---|---|
| 7A-1 | Buat `NotificationConfigIndex.jsx` — form SMTP + WA + test buttons | 0.5 hari |
| 7A-2 | Buat API endpoint `/api/v1/settings/notification` (GET/PUT) + test actions | 0.5 hari |
| 7A-3 | Daftarkan ke `config/modern-pages.php` + route modern | 0.5 hari |
| 7A-4 | Buat `ProfileIndex.jsx` — form profil + upload foto + identitas dosen | 1 hari |
| 7A-5 | Buat API endpoint `/api/v1/settings/profile` (GET/PUT) + upload foto | 0.5 hari |
| 7A-6 | Daftarkan ke modern-pages + route modern | 0.5 hari |
| 7A-7 | Test paritas: admin lppm & dosen di kedua tema | 0.5 hari |

**Total: ~3.5 hari**

---

### Fase 7B — Verifikasi Luaran Paritas (Prioritas Sedang)
**Target:** 2-3 hari

| Task | Deskripsi | Estimasi |
|---|---|---|
| 7B-1 | Tambah `PolicyInvolvement` ke API `VerifikasiLuaranController::index()` (module=research) | 0.5 hari |
| 7B-2 | Tambah filter `type` granular (publication/hki/product/pakar) ke modern React | 0.5 hari |
| 7B-3 | Tambah tab/filter "Kebijakan/Pakar" di modern (conditional: hanya verifier) | 0.5 hari |
| 7B-4 | Sinkronkan role-based filtering: pastikan `can_verify` dari API dipakai React | 0.5 hari |
| 7B-5 | Tambah `model_type: 'policy'` handling di verify/unverify/rank/detail/evidence API | 0.5 hari |
| 7B-6 | Test: Rektor/Admin LPPM verifikasi policy di modern | 0.5 hari |

**Total: ~3 hari**

---

### Fase 7C — Polish & Testing (Prioritas Rendah)
| Task | Deskripsi |
|---|---|
| 7C-1 | RoleThemeSmokeTest untuk settings (notification-config, profile) |
| 7C-2 | Contract test API notification & profile |
| 7C-3 | Modern: aksesibilitas (ARIA, keyboard nav) |
| 7C-4 | Dokumentasi gap yang *diterima* (mis. PKM-only di modern) |

---

## 5. CATATAN TEKNIS IMPLEMENTASI

### API Settings yang Dibutuhkan
```php
// routes/api.php (atau routes/web.php dengan prefix api)
Route::middleware(['auth', 'verified'])->group(function () {
    // Notification Config (admin only)
    Route::get('settings/notification', [NotificationConfigApiController::class, 'show']);
    Route::put('settings/notification', [NotificationConfigApiController::class, 'update']);
    Route::post('settings/notification/test-email', [NotificationConfigApiController::class, 'testEmail']);
    Route::post('settings/notification/test-whatsapp', [NotificationConfigApiController::class, 'testWhatsApp']);
    
    // Profile (all authenticated users)
    Route::get('settings/profile', [ProfileApiController::class, 'show']);
    Route::put('settings/profile', [ProfileApiController::class, 'update']);
    Route::post('settings/profile/photo', [ProfileApiController::class, 'uploadPhoto']);
    Route::delete('settings/profile/photo', [ProfileApiController::class, 'deletePhoto']);
});
```

### Modern Pages Registry Update
```php
// config/modern-pages.php
'pages' => [
    // ... existing ...
    'settings.notification-config' => 'notification-config-index',
    'settings.profile' => 'profile-index',
],
```

### React Components Structure
```
resources/js/modern/
├── NotificationConfigIndex.jsx    # NEW
├── ProfileIndex.jsx               # NEW
├── VerifikasiLuaranIndex.jsx      # EXISTING (extend untuk policy)
└── components/
    ├── SettingsForm.jsx           # Shared form components
    ├── FileUpload.jsx             # Reusable upload
    └── ...
```

---

## 6. KESIMPULAN & REKOMENDASI UTAMA

### **Segera Kerjakan (High Impact)**
1. **NotificationConfig modern** — Admin LPPM tidak bisa kelola SMTP/WA di modern
2. **Profile modern** — Semua user tidak bisa edit profil di modern

### **Perbaikan Verifikasi Luaran (Medium Impact)**
3. **Tambah Policy Involvement ke modern** — agar Rektor/Admin bisa verifikasi kebijakan di modern
4. **Sinkronkan filter type** — modern pakai mandatory/additional, klasik pakai publication/hki/product/pakar

### **Diterima Sebagai Desain (Bukan Bug)**
- Modern **hanya** verifikasi output (mandatory/additional) — PKM + Research
- Klasik **hanya** verifikasi IKU — Research + Policy
- Perbedaan ini **by design** karena modern terintegrasi dengan sub-modul research/pkm

---

## 7. PERINTAH VERIFIKASI SETELAH IMPLEMENTASI

```bash
# Test settings modern
php artisan test --filter="NotificationConfig"
php artisan test --filter="Profile"

# Test verifikasi luaran paritas
php artisan test --filter="VerifikasiLuaran"

# Theme checks
php artisan theme:check
php artisan modern:check
php artisan regression:check

# Full test suite
php artisan test
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --level=5
```

---

## 8. STATUS IMPLEMENTASI ZERO-GAP (2026-11-17)

**Prinsip:** tidak ada fitur pengaturan yang *hilang* di tema Modern. Yang belum punya
halaman React tetap **dapat dijangkau** lewat tautan fallback ke halaman Klasik (badge
"Klasik"), dan seluruhnya terdaftar di **Pusat Pengaturan** (`/settings/modern`).

### 8.1 Single Source of Truth

| Komponen | Peran |
|---|---|
| `GET /api/v1/settings/hub` | Daftar section + item pengaturan yang boleh diakses user, lengkap dengan `href` |
| `resources/js/modern/SettingsIndex.jsx` | Render hub; item non-React diberi badge "Klasik" |
| `app/View/Composers/MenuComposer.php` | Child "Pusat Pengaturan" pada menu Pengaturan (tema Modern) |
| `resources/views/components/layouts/header.blade.php` | Dropdown user → profil/pengaturan versi Modern |

### 8.2 Sudah Punya Halaman Modern (React)

| Pengaturan | Route Modern | API | Akses |
|---|---|---|---|
| Pusat Pengaturan | `settings.index.page` | `GET /api/v1/settings/hub` | semua user |
| Profil Saya | `settings.profile.page` | `GET/PUT /api/v1/settings/profile` (+ foto) | semua user |
| Ubah Kata Sandi | `settings.password.page` | `PUT /api/v1/settings/password` | semua user |
| Konfigurasi Notifikasi | `settings.notification-config.page` | `GET/PUT /api/v1/settings/notification` (+ test email/WA) | admin LPPM, superadmin |
| Feature Flags | `settings.feature-flags.page` | `GET/PUT /api/v1/settings/feature-flags` | admin LPPM, superadmin |
| Keamanan CAPTCHA | `settings.captcha.page` | `GET/PUT /api/v1/settings/captcha` | admin LPPM, superadmin |

### 8.3 Fallback Klasik (Fungsional, Belum React)

Semua item di bawah **tetap dapat diakses** dari Pusat Pengaturan Modern (badge
"Klasik") dan dari menu sidebar. Tidak ada fitur yang hilang — hanya tampilan yang
masih Blade/Livewire.

| Pengaturan | Route Klasik | Alasan belum React |
|---|---|---|
| Master Data (23 manager) | `settings.master-data` | 23 sub-manajer CRUD; perlu port bertahap |
| Jadwal Proposal | `settings.proposal-schedule` | Form jadwal + validasi tanggal |
| Template Proposal | `settings.proposal-template` | Upload/MediaLibrary |
| Nomor Kontrak Usulan | `settings.contracts` | CRUD penomoran + preview |
| Manual Book | `settings.manual-books` | Upload PDF per peran |
| Tampilan (Appearance) | `settings.appearance` | Logo upload + tema |
| PDF & Ekspor | tab `pdf-export` | 445 baris preferensi per-modul |
| Manajemen TKT | tab `tkt-manager` | CRUD TKT |
| Audit Log | `audit-log` | Tabel paginasi besar |
| Sinkronisasi Data | tab `sync` | Proses job SINTA |
| Backup Data | tab `backup` | Stream download arsip |
| Pulihkan Data | tab `restore` | Upload + restore DB (berisiko) |
| Autentikasi Dua Faktor | `two-factor.show` | Alur Fortify 2FA |
| Log Keamanan | tab `security` | Riwayat sesi/aktivitas |

**Catatan migrasi bertahap:** setiap item yang dipindah ke React harus (a) menambah
entry di `config/modern-pages.php`, (b) menambah komponen di `resources/js/modern.jsx`,
(c) mengubah `modern: true` pada `SettingsHubApiController`, dan (d) menambah test
kontrak API. Guard `php artisan modern:check` memastikan registry tidak pernah
mengarah ke komponen yang tidak ada.

### 8.4 Jaminan Ekosistem Tidak Berubah

API Modern memanggil **service/model yang sama** dengan Livewire Klasik — tidak ada
duplikasi logika:

| Integrasi | Titik Sentuh |
|---|---|
| SMTP override runtime | `NotificationConfigService` → `AppServiceProvider` (config mail) |
| WhatsApp fallback | `HybridNotificationChannel` |
| PDF export | `PdfExportSettings` + helper `get_pdf_*` |
| Feature flags → menu | `menu_cache_version` di-bump oleh API |
| CAPTCHA audio | `CaptchaAudioService` (voices + voice validation) |
| Password policy | `PasswordRule::defaults()` + `ActivityLog` |
| Profil/identitas | `HandleHybridInstitution` (sama dengan Livewire) |

Tema Klasik **tidak berubah**: route & komponen Livewire lama tetap utuh, dan halaman
modern melakukan redirect ke Klasik bila `is_modern_theme()` false.