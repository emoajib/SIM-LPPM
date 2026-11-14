# BC-04 — State-machine UI leak

**Gejala:** tombol "Simpan Draft"/"Ajukan" tetap muncul saat status SUBMITTED/APPROVED; LPJ bisa disahkan padahal amandemen pending; tombol aksi terkunci tidak konsisten.

**Akar masalah:** gating tombol di blade hanya berdasarkan role, bukan status dokumen; properti `canEdit` tidak dilindungi dari tampering; tidak ada lock `#[Locked]`.

**Perbaikan:** H-Phase5 — kunci tombol otomatis berdasarkan status; `#[Locked]` pada `$canEdit` trait `ReportAccess`; `approveLpj` tolak bila ada amandemen pending.

**Penjaga:** `tests/Feature/ReportSecurityAndLifecycleTest.php`; `FinancialApprovalTest`.

**Aturan:**
1. Tombol aksi wajib mempertimbangkan status dokumen, bukan hanya role.
2. Properti kontrol akses wajib `#[Locked]` di Livewire.
3. State transition wajib guard di server, bukan hanya UI.

**Kasus:** 2026-11-12 (bocor tombol); 2026-11-13 (LPJ terblokir amandemen).
