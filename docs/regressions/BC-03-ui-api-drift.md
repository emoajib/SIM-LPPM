# BC-03 — UI ↔ API drift

**Gejala:** halaman modern kosong/blank atau field hilang; `logbook_approved_at` tidak tampil; base URL salah (`/research` dipakai untuk pengabdian).

**Akar masalah:** field yang dipakai React tidak dikirim API (atau sebaliknya); API base di-hardcode per modul tanpa deteksi path; map route↔page tidak sinkron.

**Perbaikan:** tambah `logbook_approved_at` ke payload daily note; `ProposalShowIndex.jsx` memakai `apiBase` module-aware; `ModuleSubpageIndex.jsx` default action URL module-aware.

**Penjaga:** kontrak snapshot React↔API; `ModernPageParityTest`; `modern:check`.

**Aturan:**
1. Setiap field yang dipakai UI wajib ada di payload API dan sebaliknya (snapshot test).
2. Base URL/API base wajib module-aware (jangan hardcode `/research`).
3. Perubahan payload wajib memperbarui snapshot.

**Kasus:** 2026-11-13 (field hilang, base URL). 2026-11-18 (dashboard modern: `type=all` dipaksa `research` + `show_url` memakai prefix request, bukan modul baris).
