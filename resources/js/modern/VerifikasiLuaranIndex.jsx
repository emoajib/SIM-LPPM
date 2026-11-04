// Vetted by AI - Manual Review Required by Senior Engineer/Manager
import React, { useEffect, useState } from "react";
import Button from "./components/Button.jsx";
import Pagination from "./components/Pagination.jsx";
import ModuleTabNav from "./components/ModuleTabNav.jsx";
import ModernPageCard from "./components/ModernPageCard.jsx";
import ModernTable from "./components/ModernTable.jsx";
import { MODULE_TABS, getVerifierTab } from "./constants/themeColors.js";

const VERIFIER_ROLES = ["admin lppm", "kepala lppm", "rektor"];

export default function VerifikasiLuaranIndex() {
    const [outputs, setOutputs] = useState([]);
    const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0 });
    const [search, setSearch] = useState("");
    const [status, setStatus] = useState("all");
    const [type, setType] = useState("all");
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [toast, setToast] = useState(null);
    const [showEvidenceModal, setShowEvidenceModal] = useState(false);
    const [evidenceTarget, setEvidenceTarget] = useState(null);
    const [evidenceUrl, setEvidenceUrl] = useState("");
    const [evidenceFile, setEvidenceFile] = useState(null);
    const [showDetailModal, setShowDetailModal] = useState(false);
    const [detailTarget, setDetailTarget] = useState(null);
    const [detailForm, setDetailForm] = useState({
        status_type: "",
        author_status: "",
        journal_title: "",
        article_title: "",
        book_title: "",
        issn: "",
        eissn: "",
        indexing_body: "",
        journal_url: "",
        publication_year: "",
        volume: "",
        issue_number: "",
        page_start: "",
        page_end: "",
        article_url: "",
        doi: "",
        book_url: "",
        publisher: "",
        isbn: "",
        total_pages: "",
        hki_type: "",
        registration_number: "",
        inventors: "",
        product_name: "",
        description: "",
        readiness_level: "",
        implementation_location: "",
        media_name: "",
        media_url: "",
        publication_date: "",
        video_url: "",
        platform: "",
        document_file: "",
    });

    const userRoles = window.__USER_ROLES__ || [];
    const isVerifier = userRoles.some((r) => VERIFIER_ROLES.includes(r));
    const isDosen = userRoles.includes("dosen");

    const isCommunityService = window.location.pathname.includes("community-service");
    const module = isCommunityService ? "communityService" : "research";
    const apiBase = isCommunityService ? "/community-service" : "/research";

    const tabs = [
        ...MODULE_TABS[module],
        ...(isVerifier ? [getVerifierTab(module)] : []),
    ];
    const currentPath = window.location.pathname.replace(/\/$/, "");

    const load = async (p = 1) => {
        setLoading(true);
        setError(null);
        try {
            const params = new URLSearchParams({ search, status, type, page: p, module });
            const res = await fetch(`${apiBase}/api/v1/verifikasi-luaran?${params.toString()}`, {
                credentials: "same-origin",
                cache: "no-store",
            });
            if (!res.ok) throw new Error("HTTP " + res.status);
            const json = await res.json();
            setOutputs(json.data || []);
            setMeta(json.meta || meta);
        } catch (e) {
            console.error(e);
            setError("Gagal memuat data verifikasi luaran. Silakan coba lagi.");
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        load(1);
    }, [search, status, type]);

    const showToast = (type, message) => {
        setToast({ type, message });
        setTimeout(() => setToast(null), 4000);
    };

    const handleVerify = async (id, modelType) => {
        try {
            const res = await fetch(`${apiBase}/api/v1/verifikasi-luaran/${id}/verify`, {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content ?? "",
                },
                body: JSON.stringify({ model_type: modelType }),
            });
            if (!res.ok) throw new Error("Gagal verifikasi");
            showToast("success", "Data berhasil diverifikasi.");
            load(1);
        } catch (e) {
            showToast("error", e.message);
        }
    };

    const handleUnverify = async (id, modelType) => {
        if (!window.confirm("Batalkan verifikasi data ini?")) return;
        try {
            const res = await fetch(`${apiBase}/api/v1/verifikasi-luaran/${id}/unverify`, {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content ?? "",
                },
                body: JSON.stringify({ model_type: modelType }),
            });
            if (!res.ok) throw new Error("Gagal membatalkan verifikasi");
            showToast("info", "Verifikasi dibatalkan.");
            load(1);
        } catch (e) {
            showToast("error", e.message);
        }
    };

    const handleUpdateRank = async (id, modelType, rank) => {
        try {
            const res = await fetch(`${apiBase}/api/v1/verifikasi-luaran/${id}/rank`, {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content ?? "",
                },
                body: JSON.stringify({ model_type: modelType, rank }),
            });
            if (!res.ok) throw new Error("Gagal memperbarui peringkat");
            showToast("success", "Peringkat diperbarui.");
            load(1);
        } catch (e) {
            showToast("error", e.message);
        }
    };

    const handleUpdateStatus = async (id, modelType, statusType, authorStatus) => {
        try {
            const res = await fetch(`${apiBase}/api/v1/verifikasi-luaran/${id}/status`, {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content ?? "",
                },
                body: JSON.stringify({ model_type: modelType, status_type: statusType, author_status: authorStatus }),
            });
            if (!res.ok) throw new Error("Gagal memperbarui status");
            showToast("success", "Status output diperbarui.");
            load(1);
        } catch (e) {
            showToast("error", e.message);
        }
    };

    const openEvidenceModal = (output) => {
        setEvidenceTarget(output);
        setEvidenceUrl(output.article_url || output.journal_url || output.media_url || output.video_url || "");
        setEvidenceFile(null);
        setShowEvidenceModal(true);
    };

    const openDetailModal = (output) => {
        setDetailTarget(output);
        setDetailForm({
            status_type: output.status_type || "",
            author_status: output.author_status || "",
            journal_title: output.journal_title || "",
            article_title: output.article_title || "",
            book_title: output.book_title || "",
            issn: output.issn || "",
            eissn: output.eissn || "",
            indexing_body: output.indexing_body || "",
            journal_url: output.journal_url || "",
            publication_year: output.publication_year || "",
            volume: output.volume || "",
            issue_number: output.issue_number || "",
            page_start: output.page_start || "",
            page_end: output.page_end || "",
            article_url: output.article_url || "",
            doi: output.doi || "",
            book_url: output.book_url || "",
            publisher: output.publisher || "",
            isbn: output.isbn || "",
            total_pages: output.total_pages || "",
            hki_type: output.hki_type || "",
            registration_number: output.registration_number || "",
            inventors: output.inventors || "",
            product_name: output.product_name || "",
            description: output.description || "",
            readiness_level: output.readiness_level || "",
            implementation_location: output.implementation_location || "",
            media_name: output.media_name || "",
            media_url: output.media_url || "",
            publication_date: output.publication_date || "",
            video_url: output.video_url || "",
            platform: output.platform || "",
            document_file: "",
        });
        setShowDetailModal(true);
    };

    const handleUpdateDetail = async () => {
        if (!detailTarget) return;
        const formData = new FormData();
        formData.append("model_type", detailTarget.model_type);
        // Only send non-empty fields
        Object.entries(detailForm).forEach(([key, value]) => {
            if (value !== "" && value !== null && value !== undefined) {
                formData.append(key, value);
            }
        });
        if (detailForm.document_file) {
            formData.append("document_file", detailForm.document_file);
        }

        try {
            const res = await fetch(`${apiBase}/api/v1/verifikasi-luaran/${detailTarget.id}/detail`, {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content ?? "",
                },
                body: formData,
            });
            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                throw new Error(err.message || "Gagal memperbarui detail output");
            }
            showToast("success", "Detail output berhasil diperbarui.");
            setShowDetailModal(false);
            load(1);
        } catch (e) {
            showToast("error", e.message);
        }
    };

    const handleUploadEvidence = async (e) => {
        e.preventDefault();
        if (!evidenceTarget) return;
        const formData = new FormData();
        formData.append("model_type", evidenceTarget.model_type);
        if (evidenceUrl) formData.append("url", evidenceUrl);
        if (evidenceFile) formData.append("file", evidenceFile);

        try {
            const res = await fetch(`${apiBase}/api/v1/verifikasi-luaran/${evidenceTarget.id}/evidence`, {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content ?? "",
                },
                body: formData,
            });
            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                throw new Error(err.message || "Gagal mengunggah bukti");
            }
            showToast("success", "Bukti luaran berhasil diperbarui.");
            setShowEvidenceModal(false);
            load(1);
        } catch (e) {
            showToast("error", e.message);
        }
    };

    const columns = [
        {
            key: "display_title",
            label: "Judul Output",
            render: (p) => (
                <div>
                    <div className="font-bold text-slate-800 dark:text-slate-100">{p.display_title}</div>
                    {p.proposal_title && (
                        <div className="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            {p.proposal_title}
                        </div>
                    )}
                </div>
            ),
        },
        {
            key: "category",
            label: "Kategori",
            cellClassName: "text-slate-500 dark:text-slate-400 whitespace-nowrap",
            render: (p) => p.category || "-",
        },
        {
            key: "submitter_name",
            label: "Pengaju",
            cellClassName: "text-slate-500 dark:text-slate-400 whitespace-nowrap",
            render: (p) => p.submitter_name,
        },
        {
            key: "status_type",
            label: "Status (Penulis)",
            cellClassName: "whitespace-nowrap",
            render: (p) => {
                if (!isDosen) return p.status_type || "-";
                const statusOptions = [
                    "draft",
                    "submitted",
                    "published",
                    "accepted",
                    "under_review",
                    "rejected",
                ];
                return (
                    <select
                        value={p.status_type || ""}
                        onChange={(e) => {
                            handleUpdateStatus(p.id, p.model_type, e.target.value, p.author_status);
                        }}
                        className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded px-2 py-1 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e]"
                    >
                        {statusOptions.map((s) => (
                            <option key={s} value={s}>
                                {s}
                            </option>
                        ))}
                    </select>
                );
            },
        },
        {
            key: "author_status",
            label: "Posisi Penulis",
            cellClassName: "whitespace-nowrap",
            render: (p) => {
                if (!isDosen) return p.author_status || "-";
                const authorOptions = ["first_author", "co_author", "corresponding_author"];
                return (
                    <select
                        value={p.author_status || ""}
                        onChange={(e) => {
                            handleUpdateStatus(p.id, p.model_type, p.status_type, e.target.value);
                        }}
                        className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded px-2 py-1 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e]"
                    >
                        {authorOptions.map((s) => (
                            <option key={s} value={s}>
                                {s.replace("_", " ")}
                            </option>
                        ))}
                    </select>
                );
            },
        },
        {
            key: "is_verified",
            label: "Terverifikasi",
            cellClassName: "text-center whitespace-nowrap",
            render: (p) => (
                <span className={`inline-block px-2 py-0.5 rounded-full text-xs font-bold ${
                    p.is_verified
                        ? "bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800"
                        : "bg-rose-100 text-rose-800 border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800"
                }`}>
                    {p.is_verified ? "Ya" : "Belum"}
                </span>
            ),
        },
        {
            key: "evidence",
            label: "Bukti",
            cellClassName: "text-center whitespace-nowrap",
            render: (p) => (
                <div className="flex flex-col gap-1">
                    {p.evidence && p.evidence.length > 0 && p.evidence.map((f) => (
                        <a key={f.id} href={f.url} target="_blank" rel="noopener noreferrer" className="text-xs text-[#1e3a6e] dark:text-cyan-400 hover:underline">
                            📎 {f.name}
                        </a>
                    ))}
                    {p.article_url && (
                        <a href={p.article_url} target="_blank" rel="noopener noreferrer" className="text-xs text-[#1e3a6e] dark:text-cyan-400 hover:underline">
                            🔗 URL
                        </a>
                    )}
                    {(!p.evidence || p.evidence.length === 0) && !p.article_url && (
                        <span className="text-xs text-slate-400">-</span>
                    )}
                </div>
            ),
        },
        {
            key: "actions",
            label: "Aksi",
            align: "center",
            render: (p) => {
                const isOwner = isDosen && (String(p.submitter_id) === String(window.__USER_ID__ || "") || p.is_team_member);
                return (
                    <div className="flex items-center gap-1">
                        {isVerifier && (
                            <>
                                <Button
                                    onClick={() => handleVerify(p.id, p.model_type)}
                                    variant={p.is_verified ? "ghost" : "primary"}
                                    size="sm"
                                    disabled={p.is_verified}
                                >
                                    {p.is_verified ? "Sudah" : "Verifikasi"}
                                </Button>
                                <Button
                                    onClick={() => handleUnverify(p.id, p.model_type)}
                                    variant="ghost"
                                    size="sm"
                                    disabled={!p.is_verified}
                                >
                                    Batal
                                </Button>
                                <select
                                    value={p.rank || ""}
                                    onChange={(e) => handleUpdateRank(p.id, p.model_type, e.target.value)}
                                    className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded px-2 py-1 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e] w-24"
                                >
                                    <option value="">- Rank -</option>
                                    <optgroup label="SINTA">
                                        {["S1", "S2", "S3", "S4", "S5", "S6"].map((r) => (
                                            <option key={r} value={r}>{r}</option>
                                        ))}
                                    </optgroup>
                                    <optgroup label="SCOPUS">
                                        {["Q1", "Q2", "Q3", "Q4"].map((r) => (
                                            <option key={r} value={r}>{r}</option>
                                        ))}
                                    </optgroup>
                                </select>
                            </>
                        )}
                        {isDosen && isOwner && !isVerifier && (
                            <>
                                <Button
                                    onClick={() => openDetailModal(p)}
                                    variant="ghost"
                                    size="sm"
                                >
                                    📝 Detail
                                </Button>
                                <Button
                                    onClick={() => openEvidenceModal(p)}
                                    variant="ghost"
                                    size="sm"
                                >
                                    📎 Bukti
                                </Button>
                                <Button
                                    onClick={() => {
                                        handleUpdateStatus(p.id, p.model_type, p.status_type, p.author_status);
                                    }}
                                    variant="secondary"
                                    size="sm"
                                >
                                    Simpan
                                </Button>
                            </>
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
                <span className="font-semibold text-slate-700 dark:text-slate-200">Verifikasi Luaran IKU</span>
            </nav>

            <ModuleTabNav tabs={tabs} currentPath={currentPath} />

            <ModernPageCard>
                <div className="flex flex-col md:flex-row gap-3 mb-4">
                    <div className="flex-1 min-w-0">
                        <input
                            type="text"
                            value={search}
                            aria-label="Cari verifikasi luaran"
                            onChange={(e) => setSearch(e.target.value)}
                            onKeyDown={(e) => e.key === "Enter" && load(1)}
                            placeholder="Cari judul output atau pengaju..."
                            className="flex-1 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e]"
                        />
                    </div>
                    <select
                        value={status}
                        onChange={(e) => setStatus(e.target.value)}
                        className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e]"
                    >
                        <option value="all">Semua Verifikasi</option>
                        <option value="verified">Terverifikasi</option>
                        <option value="unverified">Belum Terveifikasi</option>
                    </select>
                    <select
                        value={type}
                        onChange={(e) => setType(e.target.value)}
                        className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e]"
                    >
                        <option value="all">Semua Tipe</option>
                        <option value="mandatory">Output Wajib</option>
                        <option value="additional">Output Tambahan</option>
                    </select>
                    <Button onClick={() => load(1)} variant="primary" size="md">
                        Cari
                    </Button>
                </div>

                <ModernTable
                    columns={columns}
                    data={outputs}
                    loading={loading}
                    emptyIcon={error ? "ti ti-alert-triangle" : "ti ti-shield-check"}
                    emptyTitle={error ? "Gagal memuat data" : "Belum ada data verifikasi luaran"}
                    emptyDescription={
                        error
                            ? error
                            : isDosen && !isVerifier
                            ? "Belum ada output luaran yang tercatat untuk proposal Anda."
                            : "Belum ada data output luaran yang perlu diverifikasi."
                    }
                    emptyAction={error ? <Button onClick={() => load(1)}>Coba Lagi</Button> : null}
                    ariaLabel="Daftar Verifikasi Luaran IKU"
                />

                <Pagination meta={meta} onPage={(p) => load(p)} />
            </ModernPageCard>

            {/* Evidence Upload Modal */}
            {showEvidenceModal && evidenceTarget && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                    <div className="bg-white dark:bg-slate-800 rounded-xl shadow-xl p-6 w-full max-w-md mx-4">
                        <h3 className="text-lg font-bold text-slate-800 dark:text-slate-100 mb-4">
                            Upload Bukti Luaran
                        </h3>
                        <form onSubmit={handleUploadEvidence} className="space-y-4">
                            <div className="flex flex-col gap-1">
                                <label className="text-sm font-medium text-slate-700 dark:text-slate-200">URL Bukti</label>
                                <input
                                    type="url"
                                    value={evidenceUrl}
                                    onChange={(e) => setEvidenceUrl(e.target.value)}
                                    placeholder="https://..."
                                    className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e]"
                                />
                            </div>
                            <div className="flex flex-col gap-1">
                                <label className="text-sm font-medium text-slate-700 dark:text-slate-200">File Bukti (PDF/JPG/PNG, max 5MB)</label>
                                <input
                                    type="file"
                                    onChange={(e) => setEvidenceFile(e.target.files?.[0] || null)}
                                    accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                    className="block w-full text-sm text-slate-500 dark:text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 dark:file:bg-emerald-900/50 dark:file:text-emerald-300"
                                />
                            </div>
                            <div className="flex justify-end gap-2 pt-2">
                                <Button type="button" variant="ghost" onClick={() => setShowEvidenceModal(false)}>
                                    Batal
                                </Button>
                                <Button type="submit" variant="primary">
                                    Upload
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Detail Output Modal */}
            {showDetailModal && detailTarget && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                    <div className="bg-white dark:bg-slate-800 rounded-xl shadow-xl p-6 w-full max-w-2xl mx-4 max-h-[80vh] overflow-y-auto">
                        <div className="flex justify-between items-start mb-4">
                            <h3 className="text-lg font-bold text-slate-800 dark:text-slate-100">
                                Detail Output Luaran
                            </h3>
                            <button
                                type="button"
                                onClick={() => setShowDetailModal(false)}
                                className="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200"
                            >
                                ✕
                            </button>
                        </div>
                        <form onSubmit={handleUpdateDetail} className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {/* Status & Author */}
                                <div className="flex flex-col gap-1">
                                    <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Status Penulis</label>
                                    <select
                                        value={detailForm.status_type}
                                        onChange={(e) => setDetailForm({ ...detailForm, status_type: e.target.value })}
                                        className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e]"
                                    >
                                        <option value="">Pilih Status</option>
                                        <option value="draft">Draft</option>
                                        <option value="submitted">Diajukan</option>
                                        <option value="published">Diterbitkan</option>
                                        <option value="accepted">Diterima</option>
                                        <option value="under_review">Under Review</option>
                                        <option value="rejected">Ditolak</option>
                                    </select>
                                </div>
                                <div className="flex flex-col gap-1">
                                    <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Posisi Penulis</label>
                                    <select
                                        value={detailForm.author_status}
                                        onChange={(e) => setDetailForm({ ...detailForm, author_status: e.target.value })}
                                        className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e]"
                                    >
                                        <option value="">Pilih Posisi</option>
                                        <option value="first_author">First Author</option>
                                        <option value="co_author">Co-Author</option>
                                        <option value="corresponding_author">Corresponding Author</option>
                                    </select>
                                </div>

                                {/* Judul Output */}
                                <div className="md:col-span-2 flex flex-col gap-1">
                                    <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Judul Output</label>
                                    <input
                                        type="text"
                                        value={detailForm.journal_title || detailForm.article_title || detailForm.book_title || detailForm.product_name || ""}
                                        onChange={(e) =>
                                            setDetailForm({ ...detailForm, journal_title: e.target.value })
                                        }
                                        placeholder="Judul jurnal, artikel, buku, atau produk"
                                        className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e]"
                                    />
                                </div>

                                {/* Jurnal fields */}
                                <div className="md:col-span-2 flex flex-col gap-1">
                                    <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Judul Jurnal</label>
                                    <input
                                        type="text"
                                        value={detailForm.journal_title}
                                        onChange={(e) => setDetailForm({ ...detailForm, journal_title: e.target.value })}
                                        placeholder="Nama jurnal"
                                        className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e]"
                                    />
                                </div>
                                <div className="md:col-span-2 flex flex-col gap-1">
                                    <label className="text-sm font-medium text-slate-700 dark:text-slate-200">ISSN</label>
                                    <input
                                        type="text"
                                        value={detailForm.issn}
                                        onChange={(e) => setDetailForm({ ...detailForm, issn: e.target.value })}
                                        placeholder="ISSN"
                                        className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e]"
                                    />
                                </div>
                                <div className="md:col-span-2 flex flex-col gap-1">
                                    <label className="text-sm font-medium text-slate-700 dark:text-slate-200">EISSN</label>
                                    <input
                                        type="text"
                                        value={detailForm.eissn}
                                        onChange={(e) => setDetailForm({ ...detailForm, eissn: e.target.value })}
                                        placeholder="EISSN"
                                        className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e]"
                                    />
                                </div>
                                <div className="md:col-span-2 flex flex-col gap-1">
                                    <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Judul Artikel</label>
                                    <input
                                        type="text"
                                        value={detailForm.article_title}
                                        onChange={(e) => setDetailForm({ ...detailForm, article_title: e.target.value })}
                                        placeholder="Judul artikel"
                                        className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e]"
                                    />
                                </div>
                                <div className="md:col-span-2 flex flex-col gap-1">
                                    <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Volume</label>
                                    <input
                                        type="text"
                                        value={detailForm.volume}
                                        onChange={(e) => setDetailForm({ ...detailForm, volume: e.target.value })}
                                        placeholder="Volume"
                                        className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e]"
                                    />
                                </div>
                                <div className="md:col-span-2 flex flex-col gap-1">
                                    <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Issue Number</label>
                                    <input
                                        type="text"
                                        value={detailForm.issue_number}
                                        onChange={(e) => setDetailForm({ ...detailForm, issue_number: e.target.value })}
                                        placeholder="Issue Number"
                                        className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e]"
                                    />
                                </div>
                                <div className="md:col-span-2 flex flex-col gap-1">
                                    <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Page Start</label>
                                    <input
                                        type="text"
                                        value={detailForm.page_start}
                                        onChange={(e) => setDetailForm({ ...detailForm, page_start: e.target.value })}
                                        placeholder="Halaman awal"
                                        className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e]"
                                    />
                                </div>
                                <div className="md:col-span-2 flex flex-col gap-1">
                                    <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Page End</label>
                                    <input
                                        type="text"
                                        value={detailForm.page_end}
                                        onChange={(e) => setDetailForm({ ...detailForm, page_end: e.target.value })}
                                        placeholder="Halaman akhir"
                                        className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e]"
                                    />
                                </div>

                                {/* Buku fields */}
                                <div className="md:col-span-2 flex flex-col gap-1">
                                    <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Judul Buku</label>
                                    <input
                                        type="text"
                                        value={detailForm.book_title}
                                        onChange={(e) => setDetailForm({ ...detailForm, book_title: e.target.value })}
                                        placeholder="Judul buku"
                                        className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e]"
                                    />
                                </div>
                                <div className="md:col-span-2 flex flex-col gap-1">
                                    <label className="text-sm font-medium text-slate-700 dark:text-slate-200">ISBN</label>
                                    <input
                                        type="text"
                                        value={detailForm.isbn}
                                        onChange={(e) => setDetailForm({ ...detailForm, isbn: e.target.value })}
                                        placeholder="ISBN"
                                        className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e]"
                                    />
                                </div>
                                <div className="md:col-span-2 flex flex-col gap-1">
                                    <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Penerbit</label>
                                    <input
                                        type="text"
                                        value={detailForm.publisher}
                                        onChange={(e) => setDetailForm({ ...detailForm, publisher: e.target.value })}
                                        placeholder="Nama penerbit"
                                        className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e]"
                                    />
                                </div>
                                <div className="md:col-span-2 flex flex-col gap-1">
                                    <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Total Halaman</label>
                                    <input
                                        type="number"
                                        value={detailForm.total_pages}
                                        onChange={(e) => setDetailForm({ ...detailForm, total_pages: e.target.value })}
                                        placeholder="Total halaman"
                                        className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e]"
                                    />
                                </div>

                                {/* HKI fields */}
                                <div className="md:col-span-2 flex flex-col gap-1">
                                    <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Jenis HKI</label>
                                    <input
                                        type="text"
                                        value={detailForm.hki_type}
                                        onChange={(e) => setDetailForm({ ...detailForm, hki_type: e.target.value })}
                                        placeholder="Jenis HKI (Patent, Merek, dll.)"
                                        className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e]"
                                    />
                                </div>
                                <div className="md:col-span-2 flex flex-col gap-1">
                                    <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Nomor Registrasi</label>
                                    <input
                                        type="text"
                                        value={detailForm.registration_number}
                                        onChange={(e) => setDetailForm({ ...detailForm, registration_number: e.target.value })}
                                        placeholder="Nomor registrasi HKI"
                                        className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e]"
                                    />
                                </div>
                                <div className="md:col-span-2 flex flex-col gap-1">
                                    <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Inventors</label>
                                    <input
                                        type="text"
                                        value={detailForm.inventors}
                                        onChange={(e) => setDetailForm({ ...detailForm, inventors: e.target.value })}
                                        placeholder="Nama para pencipta"
                                        className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e]"
                                    />
                                </div>

                                {/* Product fields */}
                                <div className="md:col-span-2 flex flex-col gap-1">
                                    <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Nama Produk</label>
                                    <input
                                        type="text"
                                        value={detailForm.product_name}
                                        onChange={(e) => setDetailForm({ ...detailForm, product_name: e.target.value })}
                                        placeholder="Nama produk"
                                        className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e]"
                                    />
                                </div>
                                <div className="md:col-span-2 flex flex-col gap-1">
                                    <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Deskripsi</label>
                                    <textarea
                                        value={detailForm.description}
                                        onChange={(e) => setDetailForm({ ...detailForm, description: e.target.value })}
                                        rows={2}
                                        placeholder="Deskripsi produk"
                                        className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e]"
                                    />
                                </div>
                                <div className="md:col-span-2 flex flex-col gap-1">
                                    <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Tingkat Kesiapan (TRL)</label>
                                    <input
                                        type="text"
                                        value={detailForm.readiness_level}
                                        onChange={(e) => setDetailForm({ ...detailForm, readiness_level: e.target.value })}
                                        placeholder="Contoh: TRL 1-9"
                                        className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e]"
                                    />
                                </div>
                                <div className="md:col-span-2 flex flex-col gap-1">
                                    <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Lokasi Implementasi</label>
                                    <input
                                        type="text"
                                        value={detailForm.implementation_location}
                                        onChange={(e) => setDetailForm({ ...detailForm, implementation_location: e.target.value })}
                                        placeholder="Lokasi implementasi"
                                        className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e]"
                                    />
                                </div>

                                {/* Media fields */}
                                <div className="md:col-span-2 flex flex-col gap-1">
                                    <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Nama Media</label>
                                    <input
                                        type="text"
                                        value={detailForm.media_name}
                                        onChange={(e) => setDetailForm({ ...detailForm, media_name: e.target.value })}
                                        placeholder="Nama media"
                                        className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e]"
                                    />
                                </div>
                                <div className="md:col-span-2 flex flex-col gap-1">
                                    <label className="text-sm font-medium text-slate-700 dark:text-slate-200">URL Media</label>
                                    <input
                                        type="url"
                                        value={detailForm.media_url}
                                        onChange={(e) => setDetailForm({ ...detailForm, media_url: e.target.value })}
                                        placeholder="URL media"
                                        className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e]"
                                    />
                                </div>

                                {/* Video fields */}
                                <div className="md:col-span-2 flex flex-col gap-1">
                                    <label className="text-sm font-medium text-slate-700 dark:text-slate-200">URL Video</label>
                                    <input
                                        type="url"
                                        value={detailForm.video_url}
                                        onChange={(e) => setDetailForm({ ...detailForm, video_url: e.target.value })}
                                        placeholder="URL video"
                                        className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e]"
                                    />
                                </div>
                                <div className="md:col-span-2 flex flex-col gap-1">
                                    <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Platform</label>
                                    <input
                                        type="text"
                                        value={detailForm.platform}
                                        onChange={(e) => setDetailForm({ ...detailForm, platform: e.target.value })}
                                        placeholder="Platform video"
                                        className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e]"
                                    />
                                </div>

                                {/* Tanggal terbit */}
                                <div className="md:col-span-2 flex flex-col gap-1">
                                    <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Tanggal Terbit</label>
                                    <input
                                        type="date"
                                        value={detailForm.publication_date}
                                        onChange={(e) => setDetailForm({ ...detailForm, publication_date: e.target.value })}
                                        className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e]"
                                    />
                                </div>
                            </div>
                            <div className="flex justify-end gap-2 pt-4">
                                <Button type="button" variant="ghost" onClick={() => setShowDetailModal(false)}>
                                    Batal
                                </Button>
                                <Button type="submit" variant="primary">
                                    Simpan Detail
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}
