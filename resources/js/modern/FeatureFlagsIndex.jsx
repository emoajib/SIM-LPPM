// Vetted by SOSIOMEN - Manual Review Required by Senior Engineer/Manager
import React, { useEffect, useState } from "react";
import Button from "./components/Button.jsx";
import ModernPageCard from "./components/ModernPageCard.jsx";

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

function Toggle({ id, checked, onChange, label, description }) {
    return (
        <div className="flex items-start justify-between gap-4 py-3 border-b border-slate-100 dark:border-slate-700/60 last:border-b-0">
            <div className="min-w-0">
                <p className="text-sm font-medium text-slate-800 dark:text-slate-100">{label}</p>
                <p className="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{description}</p>
            </div>
            <label htmlFor={id} className="relative inline-flex items-center cursor-pointer shrink-0">
                <input
                    id={id}
                    type="checkbox"
                    checked={checked}
                    onChange={(e) => onChange(e.target.checked)}
                    className="sr-only peer"
                />
                <span className="w-11 h-6 bg-slate-300 dark:bg-slate-600 peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-[#1e3a6e] rounded-full peer-checked:bg-[#1e3a6e] dark:peer-checked:bg-[#4a7bc8] transition-colors" />
                <span className="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform peer-checked:translate-x-5" />
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
        return <div className="flex items-center justify-center h-64 text-slate-500 dark:text-slate-400">Memuat…</div>;
    }

    return (
        <div className="space-y-4">
            {toast && (
                <div
                    role="status"
                    className={`px-4 py-3 rounded-lg border text-sm ${
                        toast.type === "success"
                            ? "bg-emerald-50 border-emerald-200 text-emerald-800 dark:bg-emerald-950/50 dark:border-emerald-900/50 dark:text-emerald-300"
                            : "bg-rose-50 border-rose-200 text-rose-800 dark:bg-rose-950/50 dark:border-rose-900/50 dark:text-rose-300"
                    }`}
                >
                    {toast.type === "success" ? "✓" : "✕"} {toast.message}
                </div>
            )}

            <nav aria-label="Breadcrumb" className="text-xs text-slate-500 dark:text-slate-400">
                <a href="/dashboard" className="hover:underline">Dashboard</a>
                <span className="mx-1.5 opacity-60">/</span>
                <a href="/settings/modern" className="hover:underline">Pengaturan</a>
                <span className="mx-1.5 opacity-60">/</span>
                <span className="font-semibold text-slate-700 dark:text-slate-200">Feature Flags</span>
            </nav>

            <ModernPageCard>
                <div className="mb-4">
                    <h2 className="text-xl font-bold text-slate-800 dark:text-slate-100 mb-1">Feature Flags</h2>
                    <p className="text-slate-500 dark:text-slate-400">
                        Pengaturan ini mengubah alur kerja aplikasi secara langsung. Pastikan kebijakan sudah disetujui sebelum mengaktifkan.
                    </p>
                </div>

                <h3 className="text-sm font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-1">
                    Hierarki Riset & Validasi Kaprodi
                </h3>
                <Toggle
                    id="feature_roadmap_active"
                    checked={flags.feature_roadmap_active}
                    onChange={set("feature_roadmap_active")}
                    label="Pohon Penelitian & Peta Jalan (Roadmap)"
                    description="Mewajibkan Dosen memilih cabang keilmuan Prodi saat pengajuan proposal, serta mengaktifkan Dasbor Roadmap di level Prodi dan Fakultas."
                />
                <Toggle
                    id="feature_kaprodi_validation"
                    checked={flags.feature_kaprodi_validation}
                    onChange={set("feature_kaprodi_validation")}
                    label="Validasi Berjenjang: Kaprodi"
                    description="Dekan tidak dapat menyetujui sebelum Kaprodi memberikan validasi pada proposal yang diajukan."
                />
                <Toggle
                    id="feature_community_partner_required"
                    checked={flags.feature_community_partner_required}
                    onChange={set("feature_community_partner_required")}
                    label="Mitra Wajib (Pengabdian Masyarakat)"
                    description="Mewajibkan dosen menambahkan minimal 1 mitra pada proposal Pengabdian Masyarakat."
                />

                <div className="flex items-center justify-between gap-4 py-3 border-b border-slate-100 dark:border-slate-700/60">
                    <div className="min-w-0">
                        <p className="text-sm font-medium text-slate-800 dark:text-slate-100">Jumlah Reviewer per Proposal</p>
                        <p className="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Jumlah minimal reviewer yang wajib ditugaskan ke setiap usulan proposal.
                        </p>
                    </div>
                    <select
                        value={flags.reviewer_count_required}
                        onChange={(e) => set("reviewer_count_required")(Number(e.target.value))}
                        className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm shrink-0"
                        aria-label="Jumlah reviewer per proposal"
                    >
                        <option value={1}>1 Reviewer</option>
                        <option value={2}>2 Reviewer</option>
                        <option value={3}>3 Reviewer</option>
                    </select>
                </div>

                <h3 className="text-sm font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400 mt-5 mb-1">
                    Modul Persuratan Terintegrasi
                </h3>
                <Toggle
                    id="module_persuratan_active"
                    checked={flags.module_persuratan_active}
                    onChange={set("module_persuratan_active")}
                    label="Status Modul Persuratan"
                    description="Aktifkan untuk memunculkan menu persuratan bagi Dosen dan Kepala LPPM."
                />

                <div className="flex items-center justify-between gap-4 py-3">
                    <div className="min-w-0">
                        <p className="text-sm font-medium text-slate-800 dark:text-slate-100">Metode Tanda Tangan Utama</p>
                        <p className="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Tanda tangan digital (QR) atau ruang kosong untuk tanda tangan basah.
                        </p>
                    </div>
                    <select
                        value={flags.surat_signature_mode}
                        onChange={set("surat_signature_mode")}
                        className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm shrink-0"
                        aria-label="Metode tanda tangan utama"
                    >
                        <option value="tte">TTE (QR Code / Barcode)</option>
                        <option value="manual">Tanda Tangan Basah (Cetak)</option>
                    </select>
                </div>

                {flags.surat_signature_mode === "manual" && (
                    <Toggle
                        id="surat_wet_signature_bypass"
                        checked={flags.surat_wet_signature_bypass}
                        onChange={set("surat_wet_signature_bypass")}
                        label="Lewati Persetujuan Kepala LPPM"
                        description={
                            flags.surat_wet_signature_bypass
                                ? "Dosen dapat mencetak surat langsung tanpa persetujuan."
                                : "Dosen tetap harus menunggu persetujuan Kepala LPPM sebelum mencetak."
                        }
                    />
                )}

                <div className="flex justify-end mt-5">
                    <Button onClick={handleSave} variant="primary" size="md" disabled={saving}>
                        {saving ? "Menyimpan…" : "Simpan Feature Flags"}
                    </Button>
                </div>
            </ModernPageCard>
        </div>
    );
}
