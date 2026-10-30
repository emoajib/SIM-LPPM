import React, { useEffect, useState } from "react";

const STATUS_COLORS = {
    draft: "bg-gray-100 text-gray-700",
    submitted: "bg-blue-100 text-blue-700",
    approved: "bg-emerald-100 text-emerald-700",
    rejected: "bg-red-100 text-red-700",
    completed: "bg-slate-100 text-slate-700",
    revision_needed: "bg-amber-100 text-amber-700",
};

const NAV_ITEMS = [
    { key: "dashboard", label: "Dashboard" },
    { key: "penelitian", label: "Penelitian" },
    { key: "pengabdian", label: "Pengabdian" },
    { key: "kontrak", label: "Kontrak" },
    { key: "surat", label: "Surat" },
    { key: "prototipe", label: "Prototipe" },
    { key: "kki", label: "Kekayaan Intelektual" },
];

const STAT_COLORS = [
    "bg-[#fb7185]",
    "bg-[#06b6d4]",
    "bg-[#fbbf24]",
    "bg-[#10b981]",
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
    const [loading, setLoading] = useState(true);
    const [activeNav, setActiveNav] = useState("dashboard");

    const loadStats = async () => {
        try {
            const res = await fetch(`/research/api/proposals?type=research`, { credentials: "same-origin" });
            const json = await res.json();
            const data = json.data || [];
            setStats({
                penelitian: data.filter((p) => p.detailable_type === "App\\Models\\Research").length,
                pengabdian: data.filter((p) => p.detailable_type === "App\\Models\\CommunityService").length,
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
            const params = new URLSearchParams({ search, status, type: "research" });
            const res = await fetch(`/research/api/proposals?${params.toString()}`, { credentials: "same-origin" });
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

    const total = stats.penelitian + stats.pengabdian + stats.surat + stats.prototipe;

    return (
        <div className="space-y-4">
            <div className="bg-gradient-to-r from-[#11244e] via-[#1e3a6e] to-[#254b8c] text-white rounded-xl p-5 shadow-md">
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <div>
                        <div className="text-[10px] font-black uppercase tracking-widest text-blue-200">Tema Modern · React + Tailwind</div>
                        <h2 className="text-xl font-black tracking-tight mt-0.5">Dashboard LPPM</h2>
                        <p className="text-[11px] text-blue-100 font-medium mt-1">Ringkasan usulan penelitian dan pengabdian masyarakat.</p>
                    </div>
                    <div className="hidden md:flex gap-2">
                        <span className="bg-white/15 text-white text-[10px] font-bold px-2.5 py-1 rounded-full">React 19</span>
                        <span className="bg-white/15 text-white text-[10px] font-bold px-2.5 py-1 rounded-full">Tailwind 4</span>
                        <span className="bg-emerald-400/30 text-emerald-100 border border-emerald-400/40 text-[10px] font-bold px-2.5 py-1 rounded-full">Vite 7</span>
                    </div>
                </div>
            </div>

            {/* Stats cards */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                {[
                    { key: "penelitian", label: "Penelitian", value: stats.penelitian, color: STAT_COLORS[0], icon: "🔍", sub: "Didanai oleh Simlitabmas/ITSNU" },
                    { key: "pengabdian", label: "Pengabdian", value: stats.pengabdian, color: STAT_COLORS[1], icon: "🤝", sub: "Didanai oleh Simlitabmas/ITSNU" },
                    { key: "surat", label: "Surat", value: stats.surat, color: STAT_COLORS[2], icon: "✉️", sub: "Surat Tugas & Izin Penelitian/Abdimas" },
                    { key: "prototipe", label: "Prototipe", value: stats.prototipe, color: STAT_COLORS[3], icon: "📦", sub: "Didanai oleh Simlitabmas/ITSNU" },
                ].map((s) => (
                    <div key={s.key} className={`rounded-xl p-4 shadow-sm border border-slate-200 ${s.color} text-white`}>
                        <div className="flex items-center justify-between">
                            <div>
                                <div className="text-[10px] font-black opacity-80">{s.label}</div>
                                <div className="text-2xl font-black mt-1">{s.value}</div>
                                <div className="text-[10px] opacity-90 mt-1">{s.sub}</div>
                            </div>
                            <div className="text-[24px]">{s.icon}</div>
                        </div>
                    </div>
                ))}
                <div className="rounded-xl p-4 shadow-sm border border-slate-200 bg-white text-slate-800">
                    <div className="flex items-center justify-between">
                        <div>
                            <div className="text-[10px] font-black opacity-60 uppercase">Total Usulan</div>
                            <div className="text-2xl font-black mt-1 text-[#1e3a6e]">{total}</div>
                            <div className="text-[10px] text-slate-500 mt-1">Semua jenis usulan</div>
                        </div>
                        <div className="text-[24px]">📊</div>
                    </div>
                </div>
            </div>

            {/* Horizontal navigation (zip pattern) */}
            <div className="bg-white border border-slate-200 rounded-xl shadow-sm p-3">
                <div className="overflow-x-auto no-scrollbar mb-2">
                    <div className="flex gap-1 min-w-max">
                        {NAV_ITEMS.map((item) => (
                            <button
                                key={item.key}
                                onClick={() => setActiveNav(item.key)}
                                className={`px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition ${activeNav === item.key ? "bg-[#1e3a6e] text-white shadow-sm" : "text-slate-600 hover:bg-slate-50"}`}
                            >
                                {item.label}
                            </button>
                        ))}
                    </div>
                </div>
            </div>

            {/* Proposals table */}
            <div className="bg-white border border-slate-200 rounded-xl shadow-sm p-4">
                <div className="flex flex-col md:flex-row gap-3 mb-4 items-end">
                    <input
                        type="text"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        onKeyDown={(e) => e.key === "Enter" && loadProposals()}
                        placeholder="Cari berdasarkan judul..."
                        className="flex-1 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#1e3a6e]"
                    />
                    <select
                        value={status}
                        onChange={(e) => setStatus(e.target.value)}
                        className="border border-slate-300 rounded-lg px-3 py-2 text-sm"
                    >
                        <option value="all">Semua Status</option>
                        <option value="draft">Draft</option>
                        <option value="submitted">Diajukan</option>
                        <option value="approved">Disetujui</option>
                        <option value="rejected">Ditolak</option>
                        <option value="completed">Selesai</option>
                    </select>
                    <button
                        onClick={loadProposals}
                        className="bg-[#1e3a6e] text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-[#254b8c] transition"
                    >
                        Cari
                    </button>
                </div>

                {loading ? (
                    <div className="text-center py-8 text-slate-500">Memuat...</div>
                ) : proposals.length === 0 ? (
                    <div className="text-center py-8 text-slate-500">
                        <div className="text-[24px] mb-2">📭</div>
                        Tidak ada data usulan.
                    </div>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-slate-50 text-slate-600 text-xs uppercase">
                                <tr>
                                    <th className="text-left px-3 py-2">Judul</th>
                                    <th className="text-left px-3 py-2">Pengusul</th>
                                    <th className="text-left px-3 py-2">Kategori</th>
                                    <th className="text-left px-3 py-2">Status</th>
                                    <th className="text-left px-3 py-2">Tanggal</th>
                                    <th className="text-left px-3 py-2">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {proposals.map((p) => (
                                    <tr key={p.id} className="hover:bg-slate-50">
                                        <td className="px-3 py-2">
                                            <div className="font-bold text-slate-800">{p.title}</div>
                                            {p.focus_area && (
                                                <span className="inline-block mt-1 text-[10px] bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full">{p.focus_area}</span>
                                            )}
                                        </td>
                                        <td className="px-3 py-2">
                                            <div>{p.submitter}</div>
                                            <div className="text-xs text-slate-500">{p.identity_id}</div>
                                        </td>
                                        <td className="px-3 py-2 text-slate-600 text-sm">{p.detailable_type === "App\\Models\\CommunityService" ? "Pengabdian" : "Penelitian"}</td>
                                        <td className="px-3 py-2">
                                            <span className={`inline-block px-2 py-0.5 rounded-full text-xs font-bold ${STATUS_COLORS[p.status] || "bg-gray-100 text-gray-700"}`}>
                                                {p.status_label}
                                            </span>
                                        </td>
                                        <td className="px-3 py-2 text-slate-500">{p.created_at}</td>
                                        <td className="px-3 py-2">
                                            <a href={p.show_url} className="text-[#1e3a6e] font-bold text-xs hover:underline">Lihat</a>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
                {meta.last_page > 1 && (
                    <div className="mt-4 text-xs text-slate-500">
                        Halaman {meta.current_page} dari {meta.last_page} · Total {meta.total}
                    </div>
                )}
            </div>
        </div>
    );
}
