// Vetted by AI - Manual Review Required by Senior Engineer/Manager
import React, { useEffect, useState } from "react";
import Button from "./Button.jsx";
import Pagination from "./Pagination.jsx";
import ModuleTabNav from "./ModuleTabNav.jsx";
import ModernPageCard from "./ModernPageCard.jsx";
import ModernTable from "./ModernTable.jsx";
import { STATUS_COLORS, MODULE_TABS, getVerifierTab } from "../constants/themeColors.js";

const SELECT_CLASS =
    "bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e] dark:focus:ring-cyan-500";

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
 * Filter parity (BC-01): status, tahun akademik, skema, fakultas, program studi,
 * dan peran (ketua/anggota) dikirim ke API — sama seperti halaman Usulan modern
 * dan index klasik (Livewire). Tanpa ini, pengguna hanya bisa mencari teks.
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
 * @param {function} [actionUrlFn] - Optional function to compute the "Lihat" action URL per proposal
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
    actionUrlFn = null,
}) {
    const [proposals, setProposals] = useState([]);
    const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0, stats: null });
    const [search, setSearch] = useState("");
    const [status, setStatus] = useState("all");
    const [roleFilter, setRoleFilter] = useState("all");
    const [academicYear, setAcademicYear] = useState("");
    const [schemeId, setSchemeId] = useState("");
    const [facultyId, setFacultyId] = useState("");
    const [studyProgramId, setStudyProgramId] = useState("");
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [filtersMeta, setFiltersMeta] = useState({
        academic_years: [],
        schemes: [],
        statuses: [],
        report_statuses: [],
        faculties: [],
        study_programs: [],
    });

    const userRoles = window.__USER_ROLES__ || [];
    const isVerifier = userRoles.some((r) => ["admin lppm", "kepala lppm", "rektor", "dosen"].includes(r));
    const isDosen = userRoles.includes("dosen");
    const type = module === "research" ? "research" : "community-service";
    const apiBase = module === "communityService" ? "/community-service" : "/research";
    const isFinalReport = submodule === "final-report";

    const tabs = [
        ...MODULE_TABS[module],
        ...(isVerifier ? [getVerifierTab(module)] : []),
    ];
    const currentPath = window.location.pathname.replace(/\/$/, "");

    const statusOptions = isFinalReport
        ? filtersMeta.report_statuses || []
        : filtersMeta.statuses || [];

    const load = async (p = 1, overrides = {}) => {
        const value = (key, fallback) => (overrides[key] !== undefined ? overrides[key] : fallback);
        const qSearch = value("search", search);
        const qStatus = value("status", status);
        const qRole = value("role", roleFilter);
        const qAcademicYear = value("academic_year", academicYear);
        const qScheme = value("scheme_id", schemeId);
        const qFaculty = value("faculty_id", facultyId);
        const qProdi = value("study_program_id", studyProgramId);

        setLoading(true);
        setError(null);
        try {
            const params = new URLSearchParams({ search: qSearch, status: qStatus, type, submodule, page: p, _: Date.now() });
            if (qRole !== "all") params.set("role", qRole);
            if (qAcademicYear) params.set("academic_year", qAcademicYear);
            if (qScheme) params.set("scheme_id", qScheme);
            if (qFaculty) params.set("faculty_id", qFaculty);
            if (qProdi) params.set("study_program_id", qProdi);
            const res = await fetch(`${apiBase}/api/v1/proposals?${params.toString()}`, {
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
        fetch(`${apiBase}/api/v1/filters?type=${type}`, { credentials: "same-origin", cache: "no-store" })
            .then((r) => r.json())
            .then((d) => setFiltersMeta((prev) => ({ ...prev, ...d })))
            .catch(() => {});
        load(1);
    }, []);

    const resetFilters = () => {
        setSearch("");
        setStatus("all");
        setRoleFilter("all");
        setAcademicYear("");
        setSchemeId("");
        setFacultyId("");
        setStudyProgramId("");
    };

    const stats = meta.stats || null;

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
            render: (p) => {
                const value = isFinalReport ? p.report_status : p.status;
                const label = isFinalReport ? p.report_status_label : p.status_label;
                return (
                    <span className={`inline-block px-2.5 py-0.5 rounded-full text-xs font-bold border ${STATUS_COLORS[value] || "bg-emerald-100 text-emerald-800"}`}>
                        {label}
                    </span>
                );
            },
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
                    href={actionUrlFn ? actionUrlFn(p) : `${module === "communityService" ? "/community-service" : "/research"}/proposal/${p.id}/modern`}
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

    const statCards = isFinalReport && stats
        ? [
            { key: "all", label: "Total Wajib Lapor", value: stats.total, tone: "text-[#1e3a6e] dark:text-cyan-400", border: "border-[#1e3a6e] dark:border-cyan-500" },
            { key: "approved", label: "Laporan Selesai", value: stats.approved, tone: "text-emerald-600 dark:text-emerald-400", border: "border-emerald-500" },
            { key: "submitted", label: "Dalam Proses", value: stats.in_review, tone: "text-sky-600 dark:text-sky-400", border: "border-sky-500" },
            { key: "belum_laporan", label: "Belum Laporan", value: stats.belum_laporan, tone: "text-amber-600 dark:text-amber-400", border: "border-amber-500" },
        ]
        : [];

    return (
        <div className="space-y-4">
            <nav aria-label="Breadcrumb" className="text-xs text-slate-500 dark:text-slate-400 mb-1">
                <a href="/dashboard" className="hover:underline">Dashboard</a>
                <span className="mx-1.5 opacity-60">/</span>
                <span className="font-semibold text-slate-700 dark:text-slate-200">{breadcrumbTitle}</span>
            </nav>

            <ModuleTabNav tabs={tabs} currentPath={currentPath} />

            {statCards.length > 0 && (
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
                    {statCards.map((card) => (
                        <button
                            key={card.key}
                            type="button"
                            onClick={() => {
                                setStatus(card.key);
                                load(1, { status: card.key });
                            }}
                            aria-pressed={status === card.key}
                            className={`text-left bg-white dark:bg-slate-900 rounded-xl border p-3 transition-all hover:shadow-md ${status === card.key ? `${card.border} shadow` : "border-slate-200 dark:border-slate-700"}`}
                        >
                            <div className="text-xs font-semibold text-slate-500 dark:text-slate-400">{card.label}</div>
                            <div className={`text-2xl font-extrabold ${card.tone}`}>{card.value ?? 0}</div>
                        </button>
                    ))}
                </div>
            )}

            <ModernPageCard>
                <div className="flex flex-col md:flex-row md:flex-wrap items-center gap-3 mb-4">
                    <input
                        type="text"
                        value={search}
                        aria-label={searchAriaLabel}
                        onChange={(e) => setSearch(e.target.value)}
                        onKeyDown={(e) => e.key === "Enter" && load(1)}
                        placeholder={searchPlaceholder}
                        className="flex-1 min-w-[200px] bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e] dark:focus:ring-cyan-500"
                    />
                    {isDosen && (
                        <select
                            value={roleFilter}
                            aria-label="Filter berdasarkan peran"
                            onChange={(e) => setRoleFilter(e.target.value)}
                            className={SELECT_CLASS}
                        >
                            <option value="all">Semua Peran</option>
                            <option value="ketua">Sebagai Ketua</option>
                            <option value="anggota">Sebagai Anggota</option>
                        </select>
                    )}
                    <select
                        value={status}
                        aria-label="Filter berdasarkan status"
                        onChange={(e) => setStatus(e.target.value)}
                        className={SELECT_CLASS}
                    >
                        <option value="all">Semua Status</option>
                        {statusOptions.map((s) => (
                            <option key={s.value} value={s.value}>{s.label}</option>
                        ))}
                    </select>
                    {Array.isArray(filtersMeta.academic_years) && filtersMeta.academic_years.length > 0 && (
                        <select
                            value={academicYear}
                            aria-label="Filter berdasarkan tahun akademik"
                            onChange={(e) => setAcademicYear(e.target.value)}
                            className={SELECT_CLASS}
                        >
                            <option value="">Semua Tahun Akademik</option>
                            {filtersMeta.academic_years.map((y) => (
                                <option key={y} value={y}>{y}</option>
                            ))}
                        </select>
                    )}
                    {Array.isArray(filtersMeta.schemes) && filtersMeta.schemes.length > 0 && (
                        <select
                            value={schemeId}
                            aria-label="Filter berdasarkan skema"
                            onChange={(e) => setSchemeId(e.target.value)}
                            className={SELECT_CLASS}
                        >
                            <option value="">Semua Skema</option>
                            {filtersMeta.schemes.map((s) => (
                                <option key={s.id} value={s.id}>{s.name}</option>
                            ))}
                        </select>
                    )}
                    {Array.isArray(filtersMeta.faculties) && filtersMeta.faculties.length > 0 && (
                        <select
                            value={facultyId}
                            aria-label="Filter berdasarkan fakultas"
                            onChange={(e) => setFacultyId(e.target.value)}
                            className={SELECT_CLASS}
                        >
                            <option value="">Semua Fakultas</option>
                            {filtersMeta.faculties.map((f) => (
                                <option key={f.id} value={f.id}>{f.name}</option>
                            ))}
                        </select>
                    )}
                    {Array.isArray(filtersMeta.study_programs) && filtersMeta.study_programs.length > 0 && (
                        <select
                            value={studyProgramId}
                            aria-label="Filter berdasarkan program studi"
                            onChange={(e) => setStudyProgramId(e.target.value)}
                            className={SELECT_CLASS}
                        >
                            <option value="">Semua Prodi</option>
                            {filtersMeta.study_programs.map((p) => (
                                <option key={p.id} value={p.id}>{p.name}</option>
                            ))}
                        </select>
                    )}
                    <Button onClick={() => load(1)} variant="primary" size="md" className="flex items-center gap-2">
                        <i className="ti ti-search me-1" aria-hidden="true"></i>
                        Cari
                    </Button>
                    <Button
                        variant="ghost"
                        size="md"
                        onClick={() => {
                            resetFilters();
                            load(1, {
                                search: "",
                                status: "all",
                                role: "all",
                                academic_year: "",
                                scheme_id: "",
                                faculty_id: "",
                                study_program_id: "",
                            });
                        }}
                        className="flex items-center gap-2"
                    >
                        <i className="ti ti-refresh me-1" aria-hidden="true"></i>
                        Reset
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
