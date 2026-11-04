// Vetted by AI - Manual Review Required by Senior Engineer/Manager
import React, { useEffect, useState } from "react";
import Button from "./Button.jsx";
import Pagination from "./Pagination.jsx";
import ModuleTabNav from "./ModuleTabNav.jsx";
import ModernPageCard from "./ModernPageCard.jsx";
import ModernTable from "./ModernTable.jsx";
import { STATUS_COLORS, MODULE_TABS, VERIFIER_TAB } from "../constants/themeColors.js";

/**
 * Format angka ke Rupiah Indonesia.
 */
function formatRupiah(value) {
    const num = value == null ? 0 : Number(value);
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(num);
}

/**
 * Reusable index page for module sub-pages (Perbaikan Usulan, Laporan Akhir, Realisasi Anggaran).
 * Eliminates duplication across 6 near-identical JSX files.
 *
 * @param {string} module - 'research' | 'communityService'
 * @param {string} submodule - 'revision' | 'final-report' | 'daily-note'
 * @param {string} breadcrumbTitle - Breadcrumb page title
 * @param {string} searchPlaceholder - Search input placeholder
 * @param {string} searchAriaLabel - Search input aria-label
 * @param {string} emptyIcon - Tabler icon name for empty state
 * @param {string} emptyTitle - Empty state title
 * @param {string} emptyDescription - Empty state description
 * @param {string} ariaLabel - ModernTable ariaLabel
 * @param {boolean} isRealisasi - When true, shows Budget (RAB) vs Actual columns for Realisasi Anggaran
 */
export default function ModuleSubpageIndex({
    module,
    submodule,
    breadcrumbTitle,
    searchPlaceholder,
    searchAriaLabel,
    emptyIcon,
    emptyTitle,
    emptyDescription,
    ariaLabel,
    isRealisasi = false,
}) {
    const [proposals, setProposals] = useState([]);
    const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0 });
    const [search, setSearch] = useState("");
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    const isVerifier = (window.__USER_ROLES__ || []).some((r) => ["admin lppm", "kepala lppm", "rektor"].includes(r));
    const type = module === "research" ? "research" : "community-service";

    const tabs = [
        ...MODULE_TABS[module],
        ...(isVerifier ? [VERIFIER_TAB] : []),
    ];
    const currentPath = window.location.pathname.replace(/\/$/, "");

    const load = async (p = 1) => {
        setLoading(true);
        setError(null);
        try {
            const params = new URLSearchParams({ search, type, submodule, page: p, _: Date.now() });
            const res = await fetch(`/research/api/v1/proposals?${params.toString()}`, {
                credentials: "same-origin",
                cache: "no-store",
            });
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

    useEffect(() => {
        load(1);
    }, []);

    const baseColumns = [
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

    const columns = isRealisasi
        ? [
            ...baseColumns.slice(0, 2), // title, submitter
            {
                key: "budget_total",
                label: "RAB (Rp)",
                cellClassName: "text-right text-slate-500 dark:text-slate-400 whitespace-nowrap",
                render: (p) => formatRupiah(p.budget_total),
            },
            {
                key: "actual_total",
                label: "Realisasi (Rp)",
                cellClassName: "text-right text-slate-500 dark:text-slate-400 whitespace-nowrap",
                render: (p) => formatRupiah(p.actual_total),
            },
            {
                key: "variance",
                label: "Selisih (Rp)",
                cellClassName: "text-right text-slate-500 dark:text-slate-400 whitespace-nowrap",
                render: (p) => formatRupiah((p.budget_total || 0) - (p.actual_total || 0)),
            },
            {
                key: "percentage",
                label: "Realisasi %",
                cellClassName: "text-right text-slate-500 dark:text-slate-400 whitespace-nowrap",
                render: (p) => {
                    const budget = p.budget_total || 0;
                    const actual = p.actual_total || 0;
                    return budget > 0 ? `${((actual / budget) * 100).toFixed(1)}%` : "0%";
                },
            },
            ...baseColumns.slice(3, 5), // created_at, actions
        ]
        : baseColumns;

    return (
        <div className="space-y-4">
            <nav aria-label="Breadcrumb" className="text-xs text-slate-500 dark:text-slate-400 mb-1">
                <a href="/dashboard" className="hover:underline">Dashboard</a>
                <span className="mx-1.5 opacity-60">/</span>
                <span className="font-semibold text-slate-700 dark:text-slate-200">{breadcrumbTitle}</span>
            </nav>

            <ModuleTabNav tabs={tabs} currentPath={currentPath} />

            <ModernPageCard>
                <div className="flex flex-col md:flex-row gap-3 mb-4">
                    <input
                        type="text"
                        value={search}
                        aria-label={searchAriaLabel}
                        onChange={(e) => setSearch(e.target.value)}
                        onKeyDown={(e) => e.key === "Enter" && load(1)}
                        placeholder={searchPlaceholder}
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
                    emptyIcon={error ? "ti ti-alert-triangle" : emptyIcon}
                    emptyTitle={error ? "Gagal memuat data" : emptyTitle}
                    emptyDescription={error || emptyDescription}
                    emptyAction={error ? <Button onClick={() => load(1)}>Coba Lagi</Button> : null}
                    ariaLabel={ariaLabel}
                />

                <Pagination meta={meta} onPage={(p) => load(p)} />
            </ModernPageCard>
        </div>
    );
}
