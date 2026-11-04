// Vetted by AI - Manual Review Required by Senior Engineer/Manager
import React, { useEffect, useState } from "react";
import Button from "./components/Button.jsx";
import Pagination from "./components/Pagination.jsx";
import ModuleTabNav from "./components/ModuleTabNav.jsx";
import ModernPageCard from "./components/ModernPageCard.jsx";
import ModernTable from "./components/ModernTable.jsx";
import { MODULE_TABS, VERIFIER_TAB } from "./constants/themeColors.js";

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

    const userRoles = window.__USER_ROLES__ || [];
    const isVerifier = userRoles.some((r) => VERIFIER_ROLES.includes(r));
    const isDosen = userRoles.includes("dosen");

    const tabs = [
        ...MODULE_TABS.research,
        ...MODULE_TABS.communityService,
        ...(isVerifier ? [VERIFIER_TAB] : []),
    ];
    const currentPath = window.location.pathname.replace(/\/$/, "");

    const load = async (p = 1) => {
        setLoading(true);
        setError(null);
        try {
            const params = new URLSearchParams({ search, status, type, page: p });
            const res = await fetch(`/research/api/v1/verifikasi-luaran?${params.toString()}`, {
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
            const res = await fetch(`/research/api/v1/verifikasi-luaran/${id}/verify`, {
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
            const res = await fetch(`/research/api/v1/verifikasi-luaran/${id}/unverify`, {
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
            const res = await fetch(`/research/api/v1/verifikasi-luaran/${id}/rank`, {
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
            const res = await fetch(`/research/api/v1/verifikasi-luaran/${id}/status`, {
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
            key: "actions",
            label: "Aksi",
            align: "center",
            render: (p) => {
                const isOwner = isDosen && p.submitter_id && String(p.submitter_id) === String(window.__USER_ID__ || "");
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
                                <input
                                    type="text"
                                    value={p.rank || ""}
                                    placeholder="Rank"
                                    onChange={(e) => {
                                        // Debounced via blur
                                    }}
                                    onBlur={(e) => handleUpdateRank(p.id, p.model_type, e.target.value)}
                                    className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded px-2 py-1 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e] w-16"
                                />
                            </>
                        )}
                        {isDosen && isOwner && !isVerifier && (
                            <Button
                                onClick={() => {
                                    handleUpdateStatus(p.id, p.model_type, p.status_type, p.author_status);
                                }}
                                variant="secondary"
                                size="sm"
                            >
                                Simpan
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
        </div>
    );
}
