// Vetted by AI - Manual Review Required by Senior Engineer/Manager
import React, { useEffect, useState } from "react";
import Button from "./components/Button.jsx";
import Pagination from "./components/Pagination.jsx";
import ModuleTabNav from "./components/ModuleTabNav.jsx";
import ModernPageCard from "./components/ModernPageCard.jsx";
import ModernTable from "./components/ModernTable.jsx";
import { STATUS_COLORS } from "./constants/themeColors.js";

export default function ResearchFinalReportIndex() {
    const [proposals, setProposals] = useState([]);
    const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0 });
    const [search, setSearch] = useState("");
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    const isVerifier = (window.__USER_ROLES__ || []).some((r) => ["admin lppm", "kepala lppm", "rektor"].includes(r));

    const tabs = [
        { label: "Usulan", href: "/research", icon: "📋" },
        { label: "Perbaikan Usulan", href: "/research/proposal-revision", icon: "✏️" },
        { label: "Laporan Akhir", href: "/research/final-report", icon: "📑" },
        { label: "Realisasi Anggaran", href: "/research/daily-note", icon: "💰" },
        ...(isVerifier ? [{ label: "Verifikasi Luaran", href: "/iku/verification", icon: "🛡️" }] : []),
    ];
    const currentPath = window.location.pathname.replace(/\/$/, "");

    const load = async (p = 1) => {
        setLoading(true);
        setError(null);
        try {
            const params = new URLSearchParams({ search, type: "research", submodule: "final-report", page: p, _: Date.now() });
            const res = await fetch(`/research/api/v1/proposals?${params.toString()}`, { credentials: "same-origin", cache: "no-store" });
            if (!res.ok) throw new Error("HTTP " + res.status);
            const json = await res.json();
            setProposals(json.data || []);
            setMeta(json.meta || meta);
        } catch (e) {
            console.error(e);
            setError("Gagal memuat data. Silakan coba lagi.");
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => { load(1); }, []);

    const columns = [
        {
            key: "title",
            label: "Judul",
            render: (p) => (
                <div className="font-bold text-slate-800 dark:text-slate-100 group-hover:text-[#1e3a6e] dark:group-hover:text-cyan-400 transition-colors">
                    {p.title}
                </div>
            ),
        },
        {
            key: "submitter",
            label: "Author",
            render: (p) => (
                <div>
                    <div className="font-medium text-slate-700 dark:text-slate-200">{p.submitter}</div>
                    <div className="text-xs text-slate-500 dark:text-slate-400">{p.identity_id}</div>
                </div>
            ),
        },
        {
            key: "status",
            label: "Status",
            render: (p) => (
                <span className={`inline-block px-2.5 py-0.5 rounded-full text-xs font-bold border ${STATUS_COLORS[p.status] || "bg-emerald-100 text-emerald-800"}`}>
                    {p.status_label}
                </span>
            ),
        },
        {
            key: "created_at",
            label: "Tanggal",
            cellClassName: "text-slate-500 dark:text-slate-400 whitespace-nowrap",
        },
        {
            key: "actions",
            label: "Aksi",
            align: "center",
            render: (p) => (
                <a
                    href={p.show_url}
                    className="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-bold text-[#1e3a6e] dark:text-cyan-400 hover:bg-slate-100 dark:hover:bg-slate-700/60 transition-colors"
                >
                    Lihat &rarr;
                </a>
            ),
        },
    ];

    return (
        <div className="space-y-4">
            <nav aria-label="Breadcrumb" className="text-xs text-slate-500 dark:text-slate-400 mb-1">
                <a href="/dashboard" className="hover:underline">Dashboard</a>
                <span className="mx-1.5 opacity-60">/</span>
                <span className="font-semibold text-slate-700 dark:text-slate-200">Laporan Akhir Penelitian</span>
            </nav>

            <ModuleTabNav tabs={tabs} currentPath={currentPath} />

            <ModernPageCard>
                <div className="flex flex-col md:flex-row gap-3 mb-4">
                    <input
                        type="text"
                        value={search}
                        aria-label="Cari laporan akhir penelitian"
                        onChange={(e) => setSearch(e.target.value)}
                        onKeyDown={(e) => e.key === "Enter" && load(1)}
                        placeholder="Cari berdasarkan judul..."
                        className="flex-1 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e] dark:focus:ring-cyan-500"
                    />
                    <Button onClick={() => load(1)} variant="primary" size="md">
                        Cari
                    </Button>
                </div>

                <ModernTable
                    columns={columns}
                    data={proposals}
                    loading={loading}
                    emptyIcon={error ? "ti ti-alert-triangle" : "ti ti-file-analytics"}
                    emptyTitle={error ? "Gagal memuat data" : "Belum ada laporan akhir"}
                    emptyDescription={error || "Belum ada laporan akhir penelitian yang diajukan."}
                    emptyAction={error ? <Button onClick={() => load(1)}>Coba Lagi</Button> : null}
                    ariaLabel="Daftar Laporan Akhir Penelitian"
                />

                <Pagination meta={meta} onPage={(p) => load(p)} />
            </ModernPageCard>
        </div>
    );
}
