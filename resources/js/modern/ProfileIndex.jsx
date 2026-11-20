// Vetted by SOSIOMEN - Manual Review Required by Senior Engineer/Manager
import React, { useEffect, useState } from "react";
import Button from "./components/Button.jsx";
import ModernPageCard from "./components/ModernPageCard.jsx";
import SettingsHeaderNav from "./components/SettingsHeaderNav.jsx";
import Skeleton from "./components/Skeleton.jsx";

export default function ProfileIndex() {
    const [user, setUser] = useState({ name: "", email: "", username: "", whatsapp_number: "", photo_url: null });
    const [identity, setIdentity] = useState(null);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [uploading, setUploading] = useState(false);
    const [toast, setToast] = useState(null);
    const [photoFile, setPhotoFile] = useState(null);
    const [photoPreview, setPhotoPreview] = useState(null);

    const showToast = (type, message) => {
        setToast({ type, message });
        setTimeout(() => setToast(null), 4000);
    };

    const load = async () => {
        setLoading(true);
        try {
            const res = await fetch("/api/v1/settings/profile", {
                credentials: "same-origin",
                cache: "no-store",
            });
            if (!res.ok) throw new Error("Gagal memuat profil");
            const data = await res.json();
            setUser(data.user || {});
            setIdentity(data.identity || {});
            if (data.user?.photo_url) setPhotoPreview(data.user.photo_url);
        } catch (e) {
            showToast("error", e.message);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        load();
    }, []);

    const handleSave = async () => {
        setSaving(true);
        try {
            const payload = {
                user: {
                    name: user.name,
                    email: user.email,
                    whatsapp_number: user.whatsapp_number,
                },
                identity: identity,
            };
            const res = await fetch("/api/v1/settings/profile", {
                method: "PUT",
                credentials: "same-origin",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content ?? "",
                },
                body: JSON.stringify(payload),
            });
            if (!res.ok) throw new Error("Gagal menyimpan profil");
            showToast("success", "Profil Anda berhasil diperbarui.");
            load();
        } catch (e) {
            showToast("error", e.message);
        } finally {
            setSaving(false);
        }
    };

    const handlePhotoChange = (e) => {
        const file = e.target.files?.[0];
        if (file) {
            setPhotoFile(file);
            setPhotoPreview(URL.createObjectURL(file));
        }
    };

    const handleUploadPhoto = async () => {
        if (!photoFile) return showToast("error", "Pilih file foto terlebih dahulu");
        setUploading(true);
        try {
            const formData = new FormData();
            formData.append("photo", photoFile);
            const res = await fetch("/api/v1/settings/profile/photo", {
                method: "POST",
                credentials: "same-origin",
                headers: { "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content ?? "" },
                body: formData,
            });
            if (!res.ok) throw new Error("Gagal mengunggah foto profil");
            showToast("success", "Foto profil berhasil diperbarui.");
            setPhotoFile(null);
            load();
        } catch (e) {
            showToast("error", e.message);
        } finally {
            setUploading(false);
        }
    };

    const handleDeletePhoto = async () => {
        if (!window.confirm("Apakah Anda yakin ingin menghapus foto profil?")) return;
        try {
            const res = await fetch("/api/v1/settings/profile/photo", {
                method: "DELETE",
                credentials: "same-origin",
                headers: { "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content ?? "" },
            });
            if (!res.ok) throw new Error("Gagal menghapus foto");
            showToast("success", "Foto profil berhasil dihapus.");
            setPhotoPreview(null);
            load();
        } catch (e) {
            showToast("error", e.message);
        }
    };

    const publicationIdFields = [
        { key: "sinta_id", label: "SINTA ID", icon: "ti ti-certificate", placeholder: "ID Peneliti SINTA Kemendikbud" },
        { key: "scopus_id", label: "Scopus Author ID", icon: "ti ti-book", placeholder: "Contoh: 57200000000" },
        { key: "google_scholar_id", label: "Google Scholar ID", icon: "ti ti-school", placeholder: "ID Profil Google Scholar" },
        { key: "wos_id", label: "WoS ResearcherID", icon: "ti ti-file-analytics", placeholder: "Web of Science ID" },
    ];

    if (loading) {
        return (
            <div className="space-y-6">
                <SettingsHeaderNav
                    title="Profil Saya"
                    subtitle="Kelola informasi akun pengguna, data identitas akademik, dan tautan repositori ilmiah."
                    categoryBadge="Manajemen Akun"
                    currentPath="/settings/profile/modern"
                />
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    <div className="lg:col-span-4">
                        <Skeleton className="h-64 w-full rounded-2xl" />
                    </div>
                    <div className="lg:col-span-8 space-y-6">
                        <Skeleton className="h-56 w-full rounded-2xl" />
                        <Skeleton className="h-72 w-full rounded-2xl" />
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <SettingsHeaderNav
                title="Profil Saya"
                subtitle="Kelola informasi akun pengguna, data identitas akademik, dan tautan repositori ilmiah."
                categoryBadge="Manajemen Akun"
                currentPath="/settings/profile/modern"
            />

            {toast && (
                <div
                    className={`px-4 py-3 rounded-xl border text-sm flex items-center gap-2.5 shadow-xs transition-all ${
                        toast.type === "success"
                            ? "bg-emerald-50 border-emerald-200 text-emerald-800 dark:bg-emerald-950/50 dark:border-emerald-900/50 dark:text-emerald-300"
                            : "bg-rose-50 border-rose-200 text-rose-800 dark:bg-rose-950/50 dark:border-rose-900/50 dark:text-rose-300"
                    }`}
                >
                    <i className={`text-base ${toast.type === "success" ? "ti ti-check" : "ti ti-alert-circle"}`}></i>
                    <span>{toast.message}</span>
                </div>
            )}

            <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                {/* Kolom Kiri: Kartu Identitas & Foto Pengguna (4 Kolom) */}
                <div className="lg:col-span-4 space-y-6">
                    <div className="bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700/80 rounded-2xl p-6 shadow-xs text-center">
                        {/* Avatar Showcase */}
                        <div className="relative mx-auto w-28 h-28 mb-4">
                            {photoPreview ? (
                                <img
                                    src={photoPreview}
                                    alt={user.name || "Foto Profil"}
                                    className="w-28 h-28 rounded-2xl object-cover border-2 border-slate-200 dark:border-slate-700 shadow-sm"
                                />
                            ) : (
                                <div className="w-28 h-28 rounded-2xl bg-gradient-to-tr from-[#1e3a6e] to-cyan-600 text-white flex items-center justify-center text-4xl font-extrabold shadow-sm">
                                    {user.name?.charAt(0)?.toUpperCase() || "?"}
                                </div>
                            )}
                        </div>

                        <h2 className="text-lg font-bold text-slate-800 dark:text-slate-100">
                            {user.name || "Nama Pengguna"}
                        </h2>
                        <p className="text-xs text-slate-500 dark:text-slate-400 font-mono mt-0.5">
                            {user.email}
                        </p>

                        {identity?.identity_id && (
                            <div className="mt-2.5 inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 dark:bg-slate-700/60 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-600 font-mono">
                                <i className="ti ti-id-badge text-xs"></i>
                                NIDN: {identity.identity_id}
                            </div>
                        )}

                        {/* Upload Controls */}
                        <div className="mt-5 pt-4 border-t border-slate-100 dark:border-slate-700/60 space-y-2">
                            <input
                                type="file"
                                id="photo-upload"
                                accept="image/jpeg,image/png"
                                onChange={handlePhotoChange}
                                className="hidden"
                            />

                            {!photoFile ? (
                                <label
                                    htmlFor="photo-upload"
                                    className="w-full inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 cursor-pointer transition-colors"
                                >
                                    <i className="ti ti-camera text-sm"></i>
                                    <span>Ganti Foto Profil</span>
                                </label>
                            ) : (
                                <div className="space-y-1.5">
                                    <Button onClick={handleUploadPhoto} variant="purple" size="sm" className="w-full" disabled={uploading}>
                                        {uploading ? "Mengunggah…" : "Simpan Foto Baru"}
                                    </Button>
                                    <button
                                        type="button"
                                        onClick={() => { setPhotoFile(null); setPhotoPreview(user.photo_url || null); }}
                                        className="text-[11px] text-slate-500 hover:underline"
                                    >
                                        Batal
                                    </button>
                                </div>
                            )}

                            {photoPreview && !photoFile && (
                                <button
                                    type="button"
                                    onClick={handleDeletePhoto}
                                    className="w-full inline-flex items-center justify-center gap-1 px-3 py-1.5 rounded-xl text-xs font-semibold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors"
                                >
                                    <i className="ti ti-trash text-xs"></i>
                                    <span>Hapus Foto</span>
                                </button>
                            )}

                            <p className="text-[11px] text-slate-400 dark:text-slate-500 pt-1">
                                Disarankan format JPG/PNG rasio 1:1, maks 2MB.
                            </p>
                        </div>
                    </div>

                    {/* Quick Guidance Card */}
                    <div className="bg-slate-50 dark:bg-slate-800/60 border border-slate-200/70 dark:border-slate-700/70 rounded-2xl p-5 text-xs text-slate-600 dark:text-slate-300 space-y-2.5">
                        <div className="flex items-center gap-2 font-bold text-slate-800 dark:text-slate-200">
                            <i className="ti ti-info-circle text-base text-[#1e3a6e] dark:text-cyan-400"></i>
                            <span>Sinkronisasi SINTA & Sister</span>
                        </div>
                        <p className="leading-relaxed text-slate-500 dark:text-slate-400">
                            Data NIDN, Jabatan Fungsional, dan SINTA ID digunakan otomatis sebagai acuan penilaian eligibilitas skema penelitian dan pengabdian LPPM ITSNU Pekalongan.
                        </p>
                    </div>
                </div>

                {/* Kolom Kanan: Form Data Lengkap (8 Kolom) */}
                <div className="lg:col-span-8 space-y-6">
                    {/* Seksi 1: Informasi Akun */}
                    <ModernPageCard>
                        <div className="flex items-center justify-between pb-3 mb-4 border-b border-slate-100 dark:border-slate-700/60">
                            <div className="flex items-center gap-2.5">
                                <div className="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-950/60 text-[#1e3a6e] dark:text-cyan-400 flex items-center justify-center text-base">
                                    <i className="ti ti-user"></i>
                                </div>
                                <h3 className="font-bold text-slate-800 dark:text-slate-100 text-sm">
                                    Informasi Akun & Kontak
                                </h3>
                            </div>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div className="flex flex-col gap-1.5 sm:col-span-2">
                                <label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                                    Nama Lengkap Beserta Gelar <span className="text-rose-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    value={user.name}
                                    onChange={(e) => setUser({ ...user, name: e.target.value })}
                                    className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e] dark:focus:ring-cyan-500 transition-all"
                                    required
                                />
                            </div>

                            <div className="flex flex-col gap-1.5">
                                <label className="text-xs font-semibold text-slate-700 dark:text-slate-300 flex items-center justify-between">
                                    <span>Username</span>
                                    <span className="text-[10px] text-slate-400 font-normal">Terkunci</span>
                                </label>
                                <input
                                    type="text"
                                    value={user.username}
                                    readOnly
                                    className="bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-500 dark:text-slate-400 cursor-not-allowed font-mono"
                                />
                            </div>

                            <div className="flex flex-col gap-1.5">
                                <label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                                    Alamat Email Kampus <span className="text-rose-500">*</span>
                                </label>
                                <input
                                    type="email"
                                    value={user.email}
                                    onChange={(e) => setUser({ ...user, email: e.target.value })}
                                    className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e] dark:focus:ring-cyan-500 transition-all"
                                    required
                                />
                            </div>

                            <div className="flex flex-col gap-1.5 sm:col-span-2">
                                <label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                                    Nomor WhatsApp Aktif
                                </label>
                                <div className="relative">
                                    <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-emerald-600 dark:text-emerald-400">
                                        <i className="ti ti-brand-whatsapp text-base"></i>
                                    </div>
                                    <input
                                        type="tel"
                                        value={user.whatsapp_number || ""}
                                        onChange={(e) => setUser({ ...user, whatsapp_number: e.target.value })}
                                        placeholder="Contoh: 6281234567890"
                                        className="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl pl-10 pr-3.5 py-2.5 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e] dark:focus:ring-cyan-500 transition-all font-mono"
                                    />
                                </div>
                            </div>
                        </div>
                    </ModernPageCard>

                    {/* Seksi 2: Identitas Akademik */}
                    {identity && (
                        <>
                            <ModernPageCard>
                                <div className="flex items-center justify-between pb-3 mb-4 border-b border-slate-100 dark:border-slate-700/60">
                                    <div className="flex items-center gap-2.5">
                                        <div className="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-base">
                                            <i className="ti ti-school"></i>
                                        </div>
                                        <h3 className="font-bold text-slate-800 dark:text-slate-100 text-sm">
                                            Identitas Akademik & Jabatan
                                        </h3>
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div className="flex flex-col gap-1.5">
                                        <label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                                            NIDN / NUPTK
                                        </label>
                                        <input
                                            type="text"
                                            value={identity.identity_id || ""}
                                            placeholder="Nomor Induk Dosen Nasional"
                                            onChange={(e) => setIdentity({ ...identity, identity_id: e.target.value })}
                                            className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e] dark:focus:ring-cyan-500 font-mono"
                                        />
                                    </div>

                                    <div className="flex flex-col gap-1.5">
                                        <label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                                            Tipe Sivitas
                                        </label>
                                        <select
                                            value={identity.type || ""}
                                            onChange={(e) => setIdentity({ ...identity, type: e.target.value })}
                                            className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e] dark:focus:ring-cyan-500 capitalize"
                                        >
                                            <option value="">-- Pilih Tipe --</option>
                                            <option value="dosen">Dosen</option>
                                            <option value="mahasiswa">Mahasiswa</option>
                                            <option value="reviewer">Reviewer</option>
                                        </select>
                                    </div>

                                    <div className="flex flex-col gap-1.5">
                                        <label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                                            Gelar Depan
                                        </label>
                                        <input
                                            type="text"
                                            value={identity.title_prefix || ""}
                                            placeholder="Contoh: Dr., Prof."
                                            onChange={(e) => setIdentity({ ...identity, title_prefix: e.target.value })}
                                            className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e] dark:focus:ring-cyan-500"
                                        />
                                    </div>

                                    <div className="flex flex-col gap-1.5">
                                        <label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                                            Gelar Belakang
                                        </label>
                                        <input
                                            type="text"
                                            value={identity.title_suffix || ""}
                                            placeholder="Contoh: M.Kom., Ph.D."
                                            onChange={(e) => setIdentity({ ...identity, title_suffix: e.target.value })}
                                            className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e] dark:focus:ring-cyan-500"
                                        />
                                    </div>

                                    <div className="flex flex-col gap-1.5">
                                        <label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                                            Pendidikan Terakhir
                                        </label>
                                        <input
                                            type="text"
                                            value={identity.last_education || ""}
                                            placeholder="Contoh: S3 Ilmu Komputer"
                                            onChange={(e) => setIdentity({ ...identity, last_education: e.target.value })}
                                            className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e] dark:focus:ring-cyan-500"
                                        />
                                    </div>

                                    <div className="flex flex-col gap-1.5">
                                        <label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                                            Jabatan Fungsional
                                        </label>
                                        <input
                                            type="text"
                                            value={identity.functional_position || ""}
                                            placeholder="Contoh: Lektor Kepala"
                                            onChange={(e) => setIdentity({ ...identity, functional_position: e.target.value })}
                                            className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e] dark:focus:ring-cyan-500"
                                        />
                                    </div>
                                </div>
                            </ModernPageCard>

                            {/* Seksi 3: ID Repositori Peneliti */}
                            <ModernPageCard>
                                <div className="flex items-center justify-between pb-3 mb-4 border-b border-slate-100 dark:border-slate-700/60">
                                    <div className="flex items-center gap-2.5">
                                        <div className="w-8 h-8 rounded-lg bg-cyan-50 dark:bg-cyan-950/60 text-cyan-600 dark:text-cyan-400 flex items-center justify-center text-base">
                                            <i className="ti ti-books"></i>
                                        </div>
                                        <h3 className="font-bold text-slate-800 dark:text-slate-100 text-sm">
                                            ID Publikasi & Repositori Ilmiah
                                        </h3>
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    {publicationIdFields.map((field) => (
                                        <div key={field.key} className="flex flex-col gap-1.5">
                                            <label className="text-xs font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                                                <i className={`${field.icon} text-slate-500`}></i>
                                                <span>{field.label}</span>
                                            </label>
                                            <input
                                                type="text"
                                                value={identity[field.key] || ""}
                                                placeholder={field.placeholder || ""}
                                                onChange={(e) => setIdentity({ ...identity, [field.key]: e.target.value })}
                                                className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e] dark:focus:ring-cyan-500 font-mono transition-all"
                                            />
                                        </div>
                                    ))}
                                </div>
                            </ModernPageCard>

                            {/* Seksi 4: Data Pribadi & Domisili */}
                            <ModernPageCard>
                                <div className="flex items-center justify-between pb-3 mb-4 border-b border-slate-100 dark:border-slate-700/60">
                                    <div className="flex items-center gap-2.5">
                                        <div className="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center text-base">
                                            <i className="ti ti-map-pin"></i>
                                        </div>
                                        <h3 className="font-bold text-slate-800 dark:text-slate-100 text-sm">
                                            Data Pribadi & Domisili
                                        </h3>
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div className="flex flex-col gap-1.5">
                                        <label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                                            Jenis Kelamin
                                        </label>
                                        <select
                                            value={identity.gender || ""}
                                            onChange={(e) => setIdentity({ ...identity, gender: e.target.value })}
                                            className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e] dark:focus:ring-cyan-500"
                                        >
                                            <option value="">-- Pilih Jenis Kelamin --</option>
                                            <option value="L">Laki-laki</option>
                                            <option value="P">Perempuan</option>
                                        </select>
                                    </div>

                                    <div className="flex flex-col gap-1.5">
                                        <label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                                            Tanggal Lahir
                                        </label>
                                        <input
                                            type="date"
                                            value={identity.birthdate || ""}
                                            onChange={(e) => setIdentity({ ...identity, birthdate: e.target.value })}
                                            className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e] dark:focus:ring-cyan-500"
                                        />
                                    </div>

                                    <div className="flex flex-col gap-1.5 sm:col-span-2">
                                        <label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                                            Tempat Lahir
                                        </label>
                                        <input
                                            type="text"
                                            value={identity.birthplace || ""}
                                            placeholder="Kota kelahiran"
                                            onChange={(e) => setIdentity({ ...identity, birthplace: e.target.value })}
                                            className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e] dark:focus:ring-cyan-500"
                                        />
                                    </div>

                                    <div className="flex flex-col gap-1.5 sm:col-span-2">
                                        <label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                                            Alamat Lengkap
                                        </label>
                                        <textarea
                                            value={identity.address || ""}
                                            placeholder="Alamat domisili lengkap"
                                            onChange={(e) => setIdentity({ ...identity, address: e.target.value })}
                                            rows={3}
                                            className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e] dark:focus:ring-cyan-500"
                                        />
                                    </div>
                                </div>
                            </ModernPageCard>
                        </>
                    )}

                    {/* Bottom Save Bar */}
                    <div className="bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700/80 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-4">
                        <p className="text-xs text-slate-500 dark:text-slate-400 text-center sm:text-left">
                            Perubahan profil langsung berlaku di seluruh berkas proposal Anda.
                        </p>
                        <Button onClick={handleSave} variant="purple" size="md" disabled={saving}>
                            <i className="ti ti-device-floppy mr-1.5"></i>
                            {saving ? "Menyimpan Perubahan…" : "Simpan Profil Saya"}
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    );
}
