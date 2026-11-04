// Vetted by AI - Manual Review Required by Senior Engineer/Manager
import React, { useEffect, useState } from "react";
import Button from "./components/Button.jsx";
import ModernPageCard from "./components/ModernPageCard.jsx";
import ModernTable from "./components/ModernTable.jsx";
import { STATUS_COLORS } from "./constants/themeColors.js";

const NAV_ITEMS = [
    { key: "dashboard", label: "Dashboard", icon: "📊" },
    { key: "penelitian", label: "Penelitian", href: "/research", icon: "🔬" },
    { key: "pengabdian", label: "Pengabdian", href: "/community-service", icon: "🤝" },
    { key: "kontrak", label: "Kontrak", href: "/research/contract", icon: "📝" },
    { key: "surat", label: "Surat", href: "/dashboard/dosen/surat/dashboard", icon: "✉️" },
    { key: "prototipe", label: "Prototipe", href: "/prototype", icon: "📦" },
    { key: "kki", label: "Kekayaan Intelektual", href: "/kki", icon: "💡" },
];

const STAT_GRADIENTS = [
    "bg-gradient-to-br from-[#fb7185] to-[#e11d48]",
    "bg-gradient-to-br from-[#06b6d4] to-[#0284c7]",
    "bg-gradient-to-br from-[#fbbf24] to-[#d97706]",
    "bg-gradient-to-br from-[#10b981] to-[#059669]",
];

export default function DashboardIndex() {
    const [stats, setStats] = useState({
        penelitian: 0,
        pengabdian: 0,
        surat: 0,
        prototipe: 0,
    });
    const [proposals, setProposals] = useState([]);
    const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0 });
    const [search, setSearch] = useState("");
    const [status, setStatus] = useState("all");
    const [typeFilter, setTypeFilter] = useState("all");
    const [loading, setLoading] = useState(true);
    const [activeNav, setActiveNav] = useState("dashboard");

    const activeRole = window.__ACTIVE_ROLE__ || "";
    const isDosen = activeRole === "dosen";

    const loadStats = async () => {
        try {
            const [resResearch, resCommunity] = await Promise.all([
                fetch(`/research/api/v1/proposals?type=research`, { credentials: "same-origin" }),
                fetch(`/research/api/v1/proposals?type=community-service`, { credentials: "same-origin" }),
            ]);
            const jsonResearch = await resResearch.json();
            const jsonCommunity = await resCommunity.json();
            setStats({
                penelitian: (jsonResearch.meta && jsonResearch.meta.total) || (jsonResearch.data || []).length,
                pengabdian: (jsonCommunity.meta && jsonCommunity.meta.total) || (jsonCommunity.data || []).length,
                surat: 0,
                prototipe: 0,
            });
        } catch (e) {
            console.error(e);
        }
    };

    const loadProposals = async () => {
        setLoading(true);
        try {
            const params = new URLSearchParams({ search, status, page: 1 });
            if (typeFilter !== "all") params.set("type", typeFilter);
            else params.set("type", "research");
            const res = await fetch(`/research/api/v1/proposals?${params.toString()}`, { credentials: "same-origin" });
            const json = await res.json();
            setProposals(json.data || []);
            setMeta(json.meta || meta);
        } catch (e) {
            console.error(e);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        loadStats();
        loadProposals();
    }, []);

    useEffect(() => {
        loadProposals();
    }, [typeFilter]);

    const total = stats.penelitian + stats.pengabdian + stats.surat + stats.prototipe;

    const columns = [
        {
            key: "title",
            label: "Judul",
            render: (p) => (
                <div>
                    <div className="font-bold text-slate-800 dark:text-slate-100 group-hover:text-[#1e3a6e] dark:group-hover:text-cyan-400 transition-colors">
                        {p.title}
                    </div>
                    {p.focus_area && (
                        <span className="inline-block mt-1 text-[10px] bg-slate-100 dark:bg-slate-700/60 text-slate-600 dark:text-slate-300 px-2 py-0.5 rounded-full border border-slate-200/50 dark:border-slate-600">
                            {p.focus_area}
                        </span>
                    )}
                </div>
            ),
        },
        {
            key: "submitter",
            label: "Pengusul",
            render: (p) => (
                <div>
                    <div className="font-medium text-slate-700 dark:text-slate-200">{p.submitter}</div>
                    <div className="text-xs text-slate-500 dark:text-slate-400">{p.identity_id}</div>
                </div>
            ),
        },
        {
            key: "category",
            label: "Kategori",
            render: (p) => (
                <span className="text-xs font-semibold text-slate-600 dark:text-slate-300">
                    {p.detailable_type === "App\\Models\\CommunityService" ? "Pengabdian" : "Penelitian"}
                </span>
            ),
        },
        {
            key: "status",
            label: "Status",
            render: (p) => (
                <span className={`inline-block px-2.5 py-0.5 rounded-full text-xs font-bold border ${STATUS_COLORS[p.status] || "bg-gray-100 text-gray-700"}`}>
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
            <div className="bg-gradient-to-r from-[#11244e] via-[#1e3a6e] to-[#254b8c] text-white rounded-xl p-5 shadow-md relative overflow-hidden">
                <div className="relative z-10">
                    <div className="text-[10px] font-black uppercase tracking-widest text-cyan-200">
                        SIM-LPPM ITSNU Pekalongan
                    </div>
                    <h2 className="text-xl font-black tracking-tight mt-0.5">Dashboard Eksekutif Riset</h2>
                    <p className="text-[11px] text-blue-100 font-medium mt-1">
                        Monitoring ringkasan usulan penelitian, pengabdian masyarakat, dan kinerja luaran.
                    </p>
                </div>
                <div className="absolute -right-6 -bottom-6 w-32 h-32 bg-white/5 rounded-full blur-xl pointer-events-none" />
            </div>

            {/* Stats cards */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                {[
                    { key: "penelitian", label: "Penelitian", value: stats.penelitian, color: STAT_GRADIENTS[0], icon: "🔍", sub: "Didanai oleh Simlitabmas/ITSNU" },
                    { key: "pengabdian", label: "Pengabdian", value: stats.pengabdian, color: STAT_GRADIENTS[1], icon: "🤝", sub: "Didanai oleh Simlitabmas/ITSNU" },
                    { key: "surat", label: "Surat", value: stats.surat, color: STAT_GRADIENTS[2], icon: "✉️", sub: "Surat Tugas & Izin Penelitian/Abdimas" },
                    { key: "prototipe", label: "Prototipe", value: stats.prototipe, color: STAT_GRADIENTS[3], icon: "📦", sub: "Didanai oleh Simlitabmas/ITSNU" },
                ].map((s) => (
                    <div
                        key={s.key}
                        className={`rounded-xl p-4 shadow-sm border border-white/20 ${s.color} text-white transition-all duration-200 hover:-translate-y-1 hover:shadow-lg`}
                    >
                        <div className="flex items-center justify-between">
                            <div>
                                <div className="text-[10px] font-black opacity-80 uppercase tracking-wider">{s.label}</div>
                                <div className="text-2xl font-black mt-1">{s.value}</div>
                                <div className="text-[10px] opacity-90 mt-1">{s.sub}</div>
                            </div>
                            <div className="text-[28px] opacity-90">{s.icon}</div>
                        </div>
                    </div>
                ))}
            </div>

            {/* Total Summary Mini Banner */}
            <div className="rounded-xl p-4 shadow-sm border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 flex items-center justify-between transition-colors">
                <div className="flex items-center gap-3">
                    <div className="w-10 h-10 rounded-lg bg-[#1e3a6e]/10 dark:bg-cyan-500/20 text-[#1e3a6e] dark:text-cyan-400 flex items-center justify-center text-xl">
                        📈
                    </div>
                    <div>
                        <div className="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Keseluruhan Usulan</div>
                        <div className="text-lg font-black text-[#1e3a6e] dark:text-cyan-400">{total} Judul Terdata</div>
                    </div>
                </div>
                <div className="text-xs text-slate-500 dark:text-slate-400 hidden sm:block">
                    Semua skema Tri Dharma Perguruan Tinggi
                </div>
            </div>

            {/* Horizontal navigation */}
            <div className="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm p-2 transition-colors">
                <div className="overflow-x-auto no-scrollbar">
                    <div className="flex gap-1 min-w-max">
                        {NAV_ITEMS.map((item) => {
                            const isCurrent = activeNav === item.key;
                            return item.href ? (
                                <a
                                    key={item.key}
                                    href={item.href}
                                    className="px-3.5 py-2 rounded-lg text-sm font-medium whitespace-nowrap text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/60 transition-colors inline-flex items-center gap-1.5"
                                >
                                    <span>{item.icon}</span>
                                    <span>{item.label}</span>
                                </a>
                            ) : (
                                <button
                                    key={item.key}
                                    onClick={() => setActiveNav(item.key)}
                                    className={`px-3.5 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition inline-flex items-center gap-1.5 ${
                                        isCurrent
                                            ? "bg-[#1e3a6e] dark:bg-cyan-600 text-white shadow-sm"
                                            : "text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/60"
                                    }`}
                                >
                                    <span>{item.icon}</span>
                                    <span>{item.label}</span>
                                </button>
                            );
                        })}
                    </div>
                </div>
            </div>

            {/* Quick actions for Dosen */}
            {isDosen && (
                <div className="flex flex-wrap gap-2.5">
                    <a
                        href="/research/proposal/create"
                        className="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-bold shadow-sm transition inline-flex items-center gap-1.5"
                    >
                        <span>+</span>
                        <span>Buat Usulan Penelitian</span>
                    </a>
                    <a
                        href="/community-service/proposal/create"
                        className="bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-2 rounded-lg text-sm font-bold shadow-sm transition inline-flex items-center gap-1.5"
                    >
                        <span>+</span>
                        <span>Buat Usulan Pengabdian</span>
                    </a>
                </div>
            )}

            {/* Proposals table */}
            <ModernPageCard>
                <div className="flex flex-col md:flex-row gap-3 mb-4 items-end">
                    <div className="flex-1 w-full">
                        <label className="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">Pencarian Judul</label>
                        <input
                            type="text"
                            value={search}
                            aria-label="Cari judul proposal di dashboard"
                            onChange={(e) => setSearch(e.target.value)}
                            onKeyDown={(e) => e.key === "Enter" && loadProposals()}
                            placeholder="Ketik judul usulan lalu tekan enter..."
                            className="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e] dark:focus:ring-cyan-500"
                        />
                    </div>
                    <div className="w-full md:w-40">
                        <label className="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">Tipe Usulan</label>
                        <select
                            value={typeFilter}
                            aria-label="Filter tipe proposal di dashboard"
                            onChange={(e) => setTypeFilter(e.target.value)}
                            className="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e] dark:focus:ring-cyan-500"
                        >
                            <option value="all">Semua Tipe</option>
                            <option value="research">Penelitian</option>
                            <option value="community-service">Pengabdian</option>
                        </select>
                    </div>
                    <div className="w-full md:w-48">
                        <label className="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">Status Usulan</label>
                        <select
                            value={status}
                            aria-label="Filter status proposal di dashboard"
                            onChange={(e) => setStatus(e.target.value)}
                            className="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e] dark:focus:ring-cyan-500"
                        >
                            <option value="all">Semua Status</option>
                            <option value="draft">Draft</option>
                            <option value="submitted">Diajukan</option>
                            <option value="approved">Disetujui</option>
                            <option value="rejected">Ditolak</option>
                            <option value="completed">Selesai</option>
                        </select>
                    </div>
                    <Button onClick={loadProposals} variant="primary" size="md">
                        Cari
                    </Button>
                </div>

                <ModernTable
                    columns={columns}
                    data={proposals}
                    loading={loading}
                    emptyIcon="ti ti-certificate"
                    emptyTitle="Tidak ada data usulan"
                    emptyDescription="Belum ada usulan penelitian yang tercatat."
                    ariaLabel="Tabel Usulan Terkini"
                />

                {meta.last_page > 1 && (
                    <div className="mt-4 text-xs text-slate-500 dark:text-slate-400">
                        Halaman {meta.current_page} dari {meta.last_page} · Total {meta.total} usulan
                    </div>
                )}
            </ModernPageCard>
        </div>
    );
}
