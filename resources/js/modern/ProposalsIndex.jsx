import React, { useEffect, useState } from "react";

const STATUS_COLORS = {
    draft: "bg-gray-100 text-gray-700",
    submitted: "bg-blue-100 text-blue-700",
    approved: "bg-emerald-100 text-emerald-700",
    rejected: "bg-red-100 text-red-700",
    completed: "bg-slate-100 text-slate-700",
    revision_needed: "bg-amber-100 text-amber-700",
};

export default function ProposalsIndex() {
    const [proposals, setProposals] = useState([]);
    const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0 });
    const [search, setSearch] = useState("");
    const [status, setStatus] = useState("all");
    const [loading, setLoading] = useState(true);

    const load = async () => {
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

    useEffect(() => { load(); }, []);

    return (
        <div className="space-y-4">
            <div className="bg-gradient-to-r from-[#11244e] via-[#1e3a6e] to-[#254b8c] text-white rounded-xl p-5 shadow-md">
                <h2 className="text-xl font-black tracking-tight">Daftar Penelitian</h2>
                <p className="text-[11px] text-blue-100 font-medium mt-1">Modern theme · React + Tailwind (migrasi bertahap)</p>
            </div>

            <div className="bg-white border border-slate-200 rounded-xl shadow-sm p-4">
                <div className="flex flex-col md:flex-row gap-3 mb-4">
                    <input
                        type="text"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        onKeyDown={(e) => e.key === "Enter" && load()}
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
                        onClick={load}
                        className="bg-[#1e3a6e] text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-[#254b8c] transition"
                    >
                        Cari
                    </button>
                </div>

                {loading ? (
                    <div className="text-center py-8 text-slate-500">Memuat...</div>
                ) : proposals.length === 0 ? (
                    <div className="text-center py-8 text-slate-500">Tidak ada data penelitian.</div>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-slate-50 text-slate-600 text-xs uppercase">
                                <tr>
                                    <th className="text-left px-3 py-2">Judul</th>
                                    <th className="text-left px-3 py-2">Author</th>
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