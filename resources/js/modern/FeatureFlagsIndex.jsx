// Vetted by SOSIOMEN - Manual Review Required by Senior Engineer/Manager
import React, { useEffect, useState } from "react";
import Button from "./components/Button.jsx";
import ModernPageCard from "./components/ModernPageCard.jsx";
import SettingsHeaderNav from "./components/SettingsHeaderNav.jsx";
import Skeleton from "./components/Skeleton.jsx";

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? "";

const DEFAULTS = {
    feature_roadmap_active: false,
    feature_kaprodi_validation: false,
    module_persuratan_active: false,
    surat_signature_mode: "tte",
    surat_wet_signature_bypass: false,
    feature_community_partner_required: true,
    reviewer_count_required: 1,
};

function FeatureToggleItem({ id, checked, onChange, label, description, icon = null }) {
    return (
        <div className="flex items-start justify-between gap-4 p-4 rounded-xl bg-slate-50/70 dark:bg-slate-900/40 border border-slate-200/70 dark:border-slate-700/60 hover:bg-slate-50 dark:hover:bg-slate-900/70 transition-colors">
            <div className="flex items-start gap-3 min-w-0">
                {icon && (
                    <div className="w-8 h-8 rounded-lg bg-white dark:bg-slate-800 text-[#1e3a6e] dark:text-cyan-400 flex items-center justify-center text-base shrink-0 shadow-2xs mt-0.5 border border-slate-200/50 dark:border-slate-700/50">
                        <i className={icon}></i>
                    </div>
                )}
                <div className="min-w-0">
                    <div className="flex items-center gap-2 flex-wrap">
                        <label htmlFor={id} className="text-sm font-bold text-slate-800 dark:text-slate-100 cursor-pointer">
                            {label}
                        </label>
                        <span className={`px-2 py-0.5 rounded-full text-[10px] font-bold ${
                            checked
                                ? "bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800"
                                : "bg-slate-200/70 text-slate-600 dark:bg-slate-700/70 dark:text-slate-400 border border-slate-300/60 dark:border-slate-600"
                        }`}>
                            {checked ? "Aktif" : "Nonaktif"}
                        </span>
                    </div>
                    <p className="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                        {description}
                    </p>
                </div>
            </div>

            <label htmlFor={id} className="relative inline-flex items-center cursor-pointer shrink-0 mt-0.5">
                <input
                    id={id}
                    type="checkbox"
                    checked={checked}
                    onChange={(e) => onChange(e.target.checked)}
                    className="sr-only peer"
                />
                <span className="w-11 h-6 bg-slate-400 dark:bg-slate-500 border border-slate-400 dark:border-slate-500 peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-[#1e3a6e] rounded-full peer-checked:bg-emerald-600 dark:peer-checked:bg-emerald-500 transition-colors" />
                <span className="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow-md transition-transform peer-checked:translate-x-5" />
            </label>
        </div>
    );
}

export default function FeatureFlagsIndex() {
    const [flags, setFlags] = useState(DEFAULTS);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [toast, setToast] = useState(null);

    const showToast = (type, message) => {
        setToast({ type, message });
        setTimeout(() => setToast(null), 4000);
    };

    useEffect(() => {
        let alive = true;
        (async () => {
            try {
                const res = await fetch("/api/v1/settings/feature-flags", {
                    credentials: "same-origin",
                    cache: "no-store",
                    headers: { Accept: "application/json" },
                });
                if (!res.ok) throw new Error("Gagal memuat feature flags.");
                const data = await res.json();
                if (alive) setFlags({ ...DEFAULTS, ...data });
            } catch (e) {
                if (alive) showToast("error", e.message);
            } finally {
                if (alive) setLoading(false);
            }
        })();
        return () => {
            alive = false;
        };
    }, []);

    const set = (key) => (value) => setFlags((f) => ({ ...f, [key]: value }));

    const handleSave = async () => {
        setSaving(true);
        try {
            const res = await fetch("/api/v1/settings/feature-flags", {
                method: "PUT",
                credentials: "same-origin",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrf(),
                },
                body: JSON.stringify(flags),
            });
            if (!res.ok) throw new Error("Gagal menyimpan feature flags.");
            const data = await res.json();
            if (data.data) setFlags({ ...DEFAULTS, ...data.data });
            showToast("success", data.message || "Feature Flags berhasil diperbarui.");
        } catch (e) {
            showToast("error", e.message);
        } finally {
            setSaving(false);
        }
    };

    if (loading) {
        return (
            <div className="space-y-6">
                <SettingsHeaderNav
                    title="Feature Flags"
                    subtitle="Aktifkan atau nonaktifkan modul opsional, alur validasi, dan integrasi persuratan."
                    categoryBadge="Kebijakan & Fitur Sistem"
                    currentPath="/settings/feature-flags/modern"
                />
                <div className="grid grid-cols-1 gap-6">
                    <Skeleton className="h-64 w-full rounded-2xl" />
                    <Skeleton className="h-64 w-full rounded-2xl" />
                </div>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <SettingsHeaderNav
                title="Feature Flags"
                subtitle="Aktifkan atau nonaktifkan modul opsional, alur validasi, dan integrasi persuratan."
                categoryBadge="Kebijakan & Fitur Sistem"
                currentPath="/settings/feature-flags/modern"
            />

            {toast && (
                <div
                    role="status"
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

            {/* Warning Alert Note */}
            <div className="p-4 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900/60 text-xs text-amber-900 dark:text-amber-200 flex items-start gap-3">
                <i className="ti ti-alert-triangle text-lg shrink-0 mt-0.5 text-amber-600 dark:text-amber-400"></i>
                <div className="leading-relaxed">
                    <strong className="font-bold">Perhatian Kebijakan:</strong> Perubahan konfigurasi feature flags langsung memengaruhi alur kerja, validasi, dan menu navigasi pengguna saat disimpan. Pastikan kebijakan akademik kampus telah disetujui pimpinan sebelum mengaktifkan.
                </div>
            </div>

            {/* Section 1: Hierarki Riset & Validasi Kaprodi */}
            <ModernPageCard>
                <div className="flex items-center justify-between pb-3 mb-4 border-b border-slate-100 dark:border-slate-700/60">
                    <div className="flex items-center gap-2.5">
                        <div className="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-[#1e3a6e] dark:text-cyan-400 flex items-center justify-center text-xl shadow-xs">
                            <i className="ti ti-school"></i>
                        </div>
                        <div>
                            <h2 className="text-base font-bold text-slate-800 dark:text-slate-100">
                                Hierarki Riset, Peta Jalan & Validasi
                            </h2>
                            <p className="text-xs text-slate-500 dark:text-slate-400">
                                Mengatur pohon keilmuan, alur pengesahan berjenjang, dan ketentuan mitra.
                            </p>
                        </div>
                    </div>
                </div>

                <div className="space-y-3.5 mb-2">
                    <FeatureToggleItem
                        id="feature_roadmap_active"
                        checked={flags.feature_roadmap_active}
                        onChange={set("feature_roadmap_active")}
                        icon="ti ti-chart-dots"
                        label="Pohon Penelitian & Peta Jalan (Roadmap)"
                        description="Mewajibkan Dosen memilih cabang keilmuan Prodi saat pengajuan proposal, serta mengaktifkan Dasbor Roadmap di level Prodi dan Fakultas."
                    />

                    <FeatureToggleItem
                        id="feature_kaprodi_validation"
                        checked={flags.feature_kaprodi_validation}
                        onChange={set("feature_kaprodi_validation")}
                        icon="ti ti-shield-check"
                        label="Validasi Berjenjang: Kaprodi"
                        description="Dekan tidak dapat menyetujui proposal sebelum Kaprodi memberikan verifikasi awal pada usulan yang diajukan dosen."
                    />

                    <FeatureToggleItem
                        id="feature_community_partner_required"
                        checked={flags.feature_community_partner_required}
                        onChange={set("feature_community_partner_required")}
                        icon="ti ti-users-group"
                        label="Mitra Wajib (Pengabdian Masyarakat)"
                        description="Mewajibkan dosen pengusul menambahkan minimal 1 mitra sasaran terverifikasi pada usulan Pengabdian Masyarakat."
                    />

                    {/* Reviewer Count Field */}
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 rounded-xl bg-slate-50/70 dark:bg-slate-900/40 border border-slate-200/70 dark:border-slate-700/60">
                        <div className="flex items-start gap-3 min-w-0">
                            <div className="w-8 h-8 rounded-lg bg-white dark:bg-slate-800 text-[#1e3a6e] dark:text-cyan-400 flex items-center justify-center text-base shrink-0 shadow-2xs mt-0.5 border border-slate-200/50 dark:border-slate-700/50">
                                <i className="ti ti-user-check"></i>
                            </div>
                            <div>
                                <label htmlFor="reviewer_count_select" className="text-sm font-bold text-slate-800 dark:text-slate-100">
                                    Jumlah Reviewer per Proposal
                                </label>
                                <p className="text-xs text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed">
                                    Jumlah minimal reviewer eksternal/internal yang wajib ditugaskan ke setiap usulan.
                                </p>
                            </div>
                        </div>

                        <select
                            id="reviewer_count_select"
                            value={flags.reviewer_count_required}
                            onChange={(e) => set("reviewer_count_required")(Number(e.target.value))}
                            className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-sm text-slate-800 dark:text-slate-100 font-bold focus:outline-none focus:ring-2 focus:ring-[#1e3a6e] dark:focus:ring-cyan-500 shrink-0 self-end sm:self-auto"
                            aria-label="Jumlah reviewer per proposal"
                        >
                            <option value={1}>1 Reviewer</option>
                            <option value={2}>2 Reviewer</option>
                            <option value={3}>3 Reviewer</option>
                        </select>
                    </div>
                </div>
            </ModernPageCard>

            {/* Section 2: Modul Persuratan Terintegrasi */}
            <ModernPageCard>
                <div className="flex items-center justify-between pb-3 mb-4 border-b border-slate-100 dark:border-slate-700/60">
                    <div className="flex items-center gap-2.5">
                        <div className="w-9 h-9 rounded-xl bg-cyan-50 dark:bg-cyan-950/60 text-[#1e3a6e] dark:text-cyan-400 flex items-center justify-center text-xl shadow-xs">
                            <i className="ti ti-file-text"></i>
                        </div>
                        <div>
                            <h2 className="text-base font-bold text-slate-800 dark:text-slate-100">
                                Modul Persuratan & E-Signature
                            </h2>
                            <p className="text-xs text-slate-500 dark:text-slate-400">
                                Pengelolaan surat tugas, surat keputusan, dan pengesahan tanda tangan elektronik.
                            </p>
                        </div>
                    </div>
                </div>

                <div className="space-y-3.5 mb-2">
                    <FeatureToggleItem
                        id="module_persuratan_active"
                        checked={flags.module_persuratan_active}
                        onChange={set("module_persuratan_active")}
                        icon="ti ti-mail-forward"
                        label="Status Modul Persuratan"
                        description="Aktifkan modul untuk menampilkan menu persuratan bagi Dosen, Kepala LPPM, dan Administrator."
                    />

                    {/* Metode Tanda Tangan Select */}
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 rounded-xl bg-slate-50/70 dark:bg-slate-900/40 border border-slate-200/70 dark:border-slate-700/60">
                        <div className="flex items-start gap-3 min-w-0">
                            <div className="w-8 h-8 rounded-lg bg-white dark:bg-slate-800 text-[#1e3a6e] dark:text-cyan-400 flex items-center justify-center text-base shrink-0 shadow-2xs mt-0.5 border border-slate-200/50 dark:border-slate-700/50">
                                <i className="ti ti-writing"></i>
                            </div>
                            <div>
                                <label htmlFor="surat_signature_select" className="text-sm font-bold text-slate-800 dark:text-slate-100">
                                    Metode Tanda Tangan Dokumen
                                </label>
                                <p className="text-xs text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed">
                                    Pilih antara verifikasi digital QR code atau ruang tanda tangan basah pada lembar cetak.
                                </p>
                            </div>
                        </div>

                        <select
                            id="surat_signature_select"
                            value={flags.surat_signature_mode}
                            onChange={set("surat_signature_mode")}
                            className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-sm text-slate-800 dark:text-slate-100 font-bold focus:outline-none focus:ring-2 focus:ring-[#1e3a6e] dark:focus:ring-cyan-500 shrink-0 self-end sm:self-auto"
                            aria-label="Metode tanda tangan utama"
                        >
                            <option value="tte">TTE Digital (QR Code)</option>
                            <option value="manual">Tanda Tangan Basah (Cetak Manual)</option>
                        </select>
                    </div>

                    {flags.surat_signature_mode === "manual" && (
                        <FeatureToggleItem
                            id="surat_wet_signature_bypass"
                            checked={flags.surat_wet_signature_bypass}
                            onChange={set("surat_wet_signature_bypass")}
                            icon="ti ti-file-certificate"
                            label="Lewati Persetujuan Kepala LPPM"
                            description={
                                flags.surat_wet_signature_bypass
                                    ? "Dosen dapat mengunduh dan mencetak lembar surat langsung tanpa menunggu persetujuan digital pimpinan."
                                    : "Dosen tetap wajib menunggu verifikasi persetujuan Kepala LPPM sebelum berkas dapat dicetak."
                            }
                        />
                    )}
                </div>
            </ModernPageCard>

            {/* Bottom Single Unified Save Action */}
            <div className="bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700/80 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-4">
                <p className="text-xs text-slate-500 dark:text-slate-400 text-center sm:text-left">
                    Perubahan langsung memengaruhi workflow dan validasi sistem seketika.
                </p>
                <Button onClick={handleSave} variant="purple" size="md" disabled={saving}>
                    <i className="ti ti-device-floppy mr-1.5"></i>
                    {saving ? "Menyimpan Perubahan…" : "Simpan Feature Flags"}
                </Button>
            </div>
        </div>
    );
}
