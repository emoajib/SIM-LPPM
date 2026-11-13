// Vetted by AI - Manual Review Required by Senior Engineer/Manager
import React, { useEffect, useState, useRef } from "react";
import Button from "./components/Button.jsx";
import Modal from "./components/Modal.jsx";
import ModuleTabNav from "./components/ModuleTabNav.jsx";
import ModernPageCard from "./components/ModernPageCard.jsx";
import ModernTable from "./components/ModernTable.jsx";
import { MODULE_TABS, getVerifierTab } from "./constants/themeColors.js";

const VERIFIER_ROLES = ["admin lppm", "kepala lppm", "rektor", "dosen"];

function formatRupiah(value) {
    const num = value == null ? 0 : Number(value);
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(num);
}

export default function DailyNoteIndex() {
    const [proposal, setProposal] = useState(null);
    const [module, setModule] = useState("research");
    const [budgetGroups, setBudgetGroups] = useState([]);
    const [notes, setNotes] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [formMode, setFormMode] = useState("add"); // 'add' | 'edit'
    const [editingId, setEditingId] = useState(null);
    const [form, setForm] = useState({
        activity_date: "",
        activity_description: "",
        progress_percentage: 0,
        notes: "",
        budget_group_id: "",
        amount: 0,
        evidence: [],
    });
    const [evidenceFiles, setEvidenceFiles] = useState([]);
    const [showForm, setShowForm] = useState(false);
    const [toast, setToast] = useState(null);
    const [pendingAmendments, setPendingAmendments] = useState(0);

    const userRoles = window.__USER_ROLES__ || [];
    const userId = window.__USER_ID__ || "";
    const isVerifier = userRoles.some((r) => VERIFIER_ROLES.includes(r));
    const canManage = window.__CAN_MANAGE__ === "true";
    const isEditable = window.__IS_EDITABLE__ === "true";

    const tabs = [
        ...(MODULE_TABS[module] || MODULE_TABS.research),
        ...(isVerifier ? [getVerifierTab(module)] : []),
    ];
    const currentPath = window.location.pathname.replace(/\/$/, "");

    const load = async () => {
        setLoading(true);
        setError(null);
        try {
            const params = new URLSearchParams({ proposal_id: window.__PROPOSAL_ID__ });
            const res = await fetch(`/research/api/v1/daily-notes?${params.toString()}`, {
                credentials: "same-origin",
                cache: "no-store",
            });
            if (!res.ok) throw new Error("HTTP " + res.status);
            const json = await res.json();
            setProposal(json.proposal);
            if (json.proposal?.detailable_type?.includes("CommunityService")) {
                setModule("communityService");
            } else {
                setModule("research");
            }
            setBudgetGroups(json.budget_groups || []);
            setNotes(json.notes || []);
            setPendingAmendments(Number(json.pending_amendments || 0));
        } catch (e) {
            console.error(e);
            setError("Gagal memuat catatan harian. Silakan coba lagi.");
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        load();
    }, []);

    const showToast = (type, message) => {
        setToast({ type, message });
        setTimeout(() => setToast(null), 4000);
    };

    const openForm = (mode = "add", note = null) => {
        setFormMode(mode);
        setEditingId(note ? note.id : null);
        setForm({
            activity_date: note ? note.activity_date : new Date().toISOString().split("T")[0],
            activity_description: note ? note.activity_description : "",
            progress_percentage: note ? note.progress_percentage : 0,
            notes: note ? note.notes : "",
            budget_group_id: note ? note.budget_group_id : "",
            amount: note ? note.amount : 0,
            evidence: [],
        });
        setEvidenceFiles([]);
        setShowForm(true);
    };

    const closeForm = () => {
        setShowForm(false);
        setEvidenceFiles([]);
    };

    const handleEvidenceChange = (e) => {
        const files = Array.from(e.target.files || []);
        setEvidenceFiles(files);
    };

    const removeEvidenceFile = (index) => {
        setEvidenceFiles(evidenceFiles.filter((_, i) => i !== index));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!canManage) {
            showToast("error", "Anda tidak memiliki akses untuk mengedit catatan harian.");
            return;
        }
        if (!isEditable) {
            showToast("error", "LPJ sudah disahkan dan terkunci.");
            return;
        }

        const formData = new FormData();
        formData.append("proposal_id", window.__PROPOSAL_ID__);
        formData.append("activity_date", form.activity_date);
        formData.append("activity_description", form.activity_description);
        formData.append("progress_percentage", String(form.progress_percentage));
        formData.append("notes", form.notes);
        formData.append("budget_group_id", form.budget_group_id || "");
        formData.append("amount", String(form.amount));
        evidenceFiles.forEach((file) => formData.append("evidence[]", file));

        const url = formMode === "edit" && editingId
            ? `/research/api/v1/daily-notes/${editingId}`
            : "/research/api/v1/daily-notes";
        const method = formMode === "edit" && editingId ? "PUT" : "POST";

        try {
            const res = await fetch(url, {
                method,
                credentials: "same-origin",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content ?? "",
                },
                body: formData,
            });
            if (!res.ok) {
                const err = await res.json();
                throw new Error(err.message || "Gagal menyimpan catatan harian.");
            }
            showToast("success", formMode === "edit" ? "Catatan harian diperbarui." : "Catatan harian disimpan.");
            closeForm();
            load();
        } catch (e) {
            showToast("error", e.message);
        }
    };

    const handleDelete = async (id) => {
        if (!window.confirm("Hapus catatan harian ini?")) return;
        if (!canManage) {
            showToast("error", "Anda tidak memiliki akses untuk menghapus catatan harian.");
            return;
        }
        try {
            const res = await fetch(`/research/api/v1/daily-notes/${id}`, {
                method: "DELETE",
                credentials: "same-origin",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content ?? "",
                },
            });
            if (!res.ok) throw new Error("Gagal menghapus");
            showToast("info", "Catatan harian dihapus.");
            load();
        } catch (e) {
            showToast("error", e.message);
        }
    };

    const handleDeleteEvidence = async (noteId, mediaId) => {
        if (!window.confirm("Hapus file bukti?")) return;
        try {
            const res = await fetch(`/research/api/v1/daily-notes/${noteId}/evidence/${mediaId}`, {
                method: "DELETE",
                credentials: "same-origin",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content ?? "",
                },
            });
            if (!res.ok) throw new Error("Gagal menghapus file bukti");
            showToast("info", "File bukti dihapus.");
            load();
        } catch (e) {
            showToast("error", e.message);
        }
    };

    const totalBudget = budgetGroups.reduce((sum, bg) => sum + (bg.allocated || 0), 0);
    const totalActual = notes.reduce((sum, n) => sum + (n.amount || 0), 0);
    const realizationPct = totalBudget > 0 ? ((totalActual / totalBudget) * 100).toFixed(1) : "0";

    const columns = [
        {
            key: "activity_date",
            label: "Tanggal",
            cellClassName: "text-slate-500 dark:text-slate-400 whitespace-nowrap",
            render: (p) => p.activity_date,
        },
        {
            key: "activity_description",
            label: "Aktivitas",
            render: (p) => (
                <div>
                    <div className="font-medium text-slate-800 dark:text-slate-100">{p.activity_description}</div>
                    {p.notes && <div className="text-xs text-slate-500 dark:text-slate-400">{p.notes}</div>}
                </div>
            ),
        },
        {
            key: "budget_group_name",
            label: "Kelompok Anggaran",
            cellClassName: "text-slate-500 dark:text-slate-400 whitespace-nowrap",
            render: (p) => (p.budget_group_name || "-") + (p.budget_group_code ? ` (${p.budget_group_code})` : ""),
        },
        {
            key: "amount",
            label: "Nominal (Rp)",
            cellClassName: "text-right text-slate-500 dark:text-slate-400 whitespace-nowrap",
            render: (p) => formatRupiah(p.amount),
        },
        {
            key: "progress_percentage",
            label: "Progres (%)",
            cellClassName: "text-center whitespace-nowrap",
            render: (p) => `${p.progress_percentage}%`,
        },
        {
            key: "evidence",
            label: "Bukti",
            cellClassName: "text-center whitespace-nowrap",
            render: (p) => (
                <div className="flex items-center gap-1">
                    {p.evidence && p.evidence.length > 0 ? (
                        p.evidence.map((f) => (
                            <a
                                key={f.id}
                                href={f.url}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="text-xs text-[#1e3a6e] dark:text-cyan-400 hover:underline"
                            >
                                📎 {f.name}
                            </a>
                        ))
                    ) : (
                        <span className="text-xs text-slate-400">-</span>
                    )}
                </div>
            ),
        },
        {
            key: "actions",
            label: "Aksi",
            align: "center",
            cellClassName: "whitespace-nowrap",
            render: (p) => {
                const isOwner = String(p.submitter_id || "") === String(userId);
                return (
                    <div className="flex items-center gap-1">
                        {canManage && isOwner && isEditable && (
                            <Button onClick={() => openForm("edit", p)} variant="secondary" size="sm">
                                Edit
                            </Button>
                        )}
                        {canManage && isOwner && isEditable && (
                            <Button onClick={() => handleDelete(p.id)} variant="ghost" size="sm">
                                Hapus
                            </Button>
                        )}
                    </div>
                );
            },
        },
    ];

    return (
        <div className="space-y-4">
            {toast && (
                <div
                    className={`px-4 py-3 rounded-lg border text-sm flex items-center gap-2 ${
                        toast.type === "success"
                            ? "bg-emerald-50 border-emerald-200 text-emerald-800 dark:bg-emerald-950/50 dark:border-emerald-900/50 dark:text-emerald-300"
                            : toast.type === "error"
                            ? "bg-rose-50 border-rose-200 text-rose-800 dark:bg-rose-950/50 dark:border-rose-900/50 dark:text-rose-300"
                            : "bg-sky-50 border-sky-200 text-sky-800 dark:bg-sky-950/50 dark:border-sky-900/50 dark:text-sky-300"
                    }`}
                >
                    {toast.type === "success" ? "✓" : toast.type === "error" ? "✕" : "ℹ"}
                    {toast.message}
                </div>
            )}
            <nav aria-label="Breadcrumb" className="text-xs text-slate-500 dark:text-slate-400 mb-1">
                <a href="/dashboard" className="hover:underline">Dashboard</a>
                <span className="mx-1.5 opacity-60">/</span>
                <span className="font-semibold text-slate-700 dark:text-slate-200">Realisasi Anggaran - {proposal?.title || "Memuat..."}</span>
            </nav>

            <ModuleTabNav tabs={tabs} currentPath={currentPath} />

            <div className="text-xs text-slate-500 dark:text-slate-400">
                Pengelolaan amandemen RAB dilakukan di{" "}
                <a href={`/research/daily-note/${window.__PROPOSAL_ID__}`} className="underline">
                    halaman klasik
                </a>
                .
            </div>

            {pendingAmendments > 0 && (
                <div className="px-4 py-3 rounded-lg border border-amber-200 bg-amber-50 text-amber-800 dark:bg-amber-950/40 dark:border-amber-900/50 dark:text-amber-300 text-sm flex items-center gap-2">
                    <span>⚠</span>
                    <span>
                        Ada {pendingAmendments} amandemen RAB menunggu keputusan.{" "}
                        <a href={`/research/daily-note/${window.__PROPOSAL_ID__}`} className="underline font-semibold">
                            Buka halaman klasik untuk memutuskan
                        </a>
                    </span>
                </div>
            )}

            {/* Budget vs Actual Summary Cards */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div className="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
                    <div className="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold">Pagu Anggaran (RAB)</div>
                    <div className="text-2xl font-bold text-slate-800 dark:text-slate-100">{formatRupiah(totalBudget)}</div>
                </div>
                <div className="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
                    <div className="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold">Realisasi Riil</div>
                    <div className="text-2xl font-bold text-[#1e3a6e] dark:text-cyan-400">{formatRupiah(totalActual)}</div>
                </div>
                <div className="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
                    <div className="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold">Terkunci</div>
                    <div className="text-2xl font-bold text-slate-800 dark:text-slate-100">
                        {!isEditable ? "Ya" : "Tidak"}
                    </div>
                </div>
            </div>

            <ModernPageCard>
                <div className="flex flex-col md:flex-row gap-4 items-end mb-4">
                    <div className="flex-1 min-w-0">
                        <h2 className="text-lg font-bold text-slate-800 dark:text-slate-100">
                            Catatan Harian Pengeluaran
                        </h2>
                        <p className="text-sm text-slate-500 dark:text-slate-400">
                            Catat aktivitas harian dan pengeluaran dana. Unggah bukti transaksi (nota, kuitansi, invoice) untuk setiap entri yang membutuhkan biaya.
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <a
                            href={`/financial-reports/${window.__PROPOSAL_ID__}/export-pdf?preview=1`}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex items-center gap-1 px-3 py-2 rounded-lg text-sm font-bold text-[#1e3a6e] dark:text-cyan-400 border border-[#1e3a6e]/30 dark:border-cyan-500/30 hover:bg-slate-100 dark:hover:bg-slate-700/60 transition"
                        >
                            👁️ Preview PDF
                        </a>
                        <a
                            href={`/financial-reports/${window.__PROPOSAL_ID__}/export-pdf?download=true`}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex items-center gap-1 px-3 py-2 rounded-lg text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 transition"
                        >
                            ⬇️ Download PDF
                        </a>
                        {canManage && isEditable && (
                            <Button onClick={() => openForm("add")} variant="primary">
                                + Tambah Entri
                            </Button>
                        )}
                    </div>
                </div>

                <ModernTable
                    columns={columns}
                    data={notes}
                    loading={loading}
                    emptyIcon={error ? "ti ti-alert-triangle" : "ti ti-receipt"}
                    emptyTitle={error ? "Gagal memuat data" : "Belum ada catatan harian"}
                    emptyDescription={
                        error
                            ? error
                            : "Mulai mencatat aktivitas harian dan pengeluaran anggaran dengan menekan tombol Tambah Entri."
                    }
                    emptyAction={error ? <Button onClick={load}>Coba Lagi</Button> : null}
                    ariaLabel="Daftar Catatan Harian Realisasi Anggaran"
                />
            </ModernPageCard>

            {/* Entry Form Modal */}
            <Modal open={showForm} onClose={closeForm} title={formMode === "add" ? "Tambah Catatan Harian" : "Edit Catatan Harian"}>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div className="flex flex-col gap-1">
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Tanggal Aktivitas *</label>
                            <input
                                type="date"
                                value={form.activity_date}
                                onChange={(e) => setForm({ ...form, activity_date: e.target.value })}
                                className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e]"
                                required
                            />
                        </div>
                        <div className="flex flex-col gap-1">
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Progres (%)</label>
                            <input
                                type="number"
                                min="0"
                                max="100"
                                value={form.progress_percentage}
                                onChange={(e) => setForm({ ...form, progress_percentage: parseInt(e.target.value) || 0 })}
                                className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e]"
                            />
                        </div>
                    </div>
                    <div className="flex flex-col gap-1">
                        <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Deskripsi Aktivitas *</label>
                        <textarea
                            value={form.activity_description}
                            onChange={(e) => setForm({ ...form, activity_description: e.target.value })}
                            rows={3}
                            className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e]"
                            required
                        />
                    </div>
                    <div className="flex flex-col gap-1">
                        <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Keterangan Tambahan</label>
                        <input
                            type="text"
                            value={form.notes}
                            onChange={(e) => setForm({ ...form, notes: e.target.value })}
                            className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e]"
                            placeholder="Catatan tambahan..."
                        />
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div className="flex flex-col gap-1">
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Kelompok Anggaran</label>
                            <select
                                value={form.budget_group_id}
                                onChange={(e) => setForm({ ...form, budget_group_id: e.target.value })}
                                className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e]"
                            >
                                <option value="">Pilih kelompok anggaran</option>
                                {budgetGroups.map((bg) => (
                                    <option key={bg.id} value={bg.id}>
                                        {bg.name} ({bg.code}) - Sisa: {formatRupiah(bg.remaining)}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="flex flex-col gap-1">
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Nominal Pengeluaran (Rp)</label>
                            <input
                                type="number"
                                min="0"
                                value={form.amount}
                                onChange={(e) => setForm({ ...form, amount: parseFloat(e.target.value) || 0 })}
                                className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e]"
                                placeholder="0"
                            />
                        </div>
                    </div>
                    <div className="flex flex-col gap-1">
                        <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Bukti Transaksi (Nota/Kuitansi/Invoice)</label>
                        <div className="flex flex-col gap-2">
                            <input
                                type="file"
                                multiple
                                onChange={handleEvidenceChange}
                                className="block w-full text-sm text-slate-500 dark:text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 dark:file:bg-emerald-900/50 dark:file:text-emerald-300"
                                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                            />
                            {evidenceFiles.length > 0 && (
                                <div className="flex flex-wrap gap-2">
                                    {evidenceFiles.map((file, idx) => (
                                        <div key={idx} className="flex items-center gap-2 px-2 py-1 bg-slate-100 dark:bg-slate-700 rounded text-xs text-slate-700 dark:text-slate-300">
                                            {file.name}
                                            <button
                                                type="button"
                                                onClick={() => removeEvidenceFile(idx)}
                                                className="text-slate-500 hover:text-rose-500"
                                            >
                                                ×
                                            </button>
                                        </div>
                                    ))}
                                </div>
                            )}
                            <p className="text-xs text-slate-400">Format: PDF, DOC, JPG, PNG. Maksimal 5MB per file.</p>
                        </div>
                    </div>
                    <div className="flex justify-end gap-2 pt-4">
                        <Button type="button" variant="ghost" onClick={closeForm}>
                            Batal
                        </Button>
                        <Button type="submit" variant="primary">
                            {formMode === "edit" ? "Perbarui" : "Simpan"}
                        </Button>
                    </div>
                </form>
            </Modal>
        </div>
    );
}
