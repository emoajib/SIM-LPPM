import React, { useEffect, useState } from "react";

export default function PengabdianFinalReportIndex() {
    const [proposals, setProposals] = useState([]);
    const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0 });
    const [search, setSearch] = useState("");
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    const tabs = [
        { label: "Usulan", href: "/community-service" },
        { label: "Perbaikan Usulan", href: "/community-service/proposal-revision" },
        { label: "Laporan Akhir", href: "/community-service/final-report" },
        { label: "Realisasi Anggaran", href: "/community-service/daily-note" },
    ];
    const currentPath = window.location.pathname.replace(/\/$/, "");

    const load = async () => {
        setLoading(true);
        setError(null);
        try {
            const params = new URLSearchParams({ search, type: "community-service", submodule: "final-report", _: Date.now() });
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
    useEffect(() => { load(); }, []);

    return (
        <div className="space-y-4">
            <div className="bg-white border border-slate-200 rounded-xl shadow-sm px-4 pt-3">
                <div className="flex gap-1 overflow-x-auto modern-tab">
                    {tabs.map((t) => {
                        const active = currentPath === t.href || currentPath.startsWith(t.href + "/");
                        return <a key={t.href} href={t.href} className={`px-4 py-2 text-sm font-bold whitespace-nowrap border-b-2 transition ${active ? "border-[#1e3a6e] text-[#1e3a6e]" : "border-transparent text-slate-500 hover:text-slate-700"}`}>{t.label}</a>;
                    })}
                </div>
            </div>
            <div className="bg-white border border-slate-200 rounded-xl shadow-sm p-4 modern-tab-content">
                <div className="flex flex-col md:flex-row gap-3 mb-4">
                    <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} onKeyDown={(e) => e.key === "Enter" && load()} placeholder="Cari berdasarkan judul..." className="flex-1 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#1e3a6e]" />
                    <button onClick={load} className="bg-[#1e3a6e] text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-[#254b8c] transition">Cari</button>
                </div>
                {loading ? (
                    <div className="animate-pulse space-y-2 py-4">{[1,2,3,4,5].map((i) => <div key={i} className="h-10 bg-slate-100 rounded-lg" />)}</div>
                ) : error ? (
                    <div className="text-center py-8 text-red-500"><div className="text-3xl mb-2">⚠️</div><div className="font-semibold">{error}</div><button onClick={load} className="mt-3 bg-[#1e3a6e] text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-[#254b8c] transition">Coba lagi</button></div>
                ) : proposals.length === 0 ? (
                    <div className="text-center py-8 text-slate-500"><div className="text-3xl mb-2">📄</div><div className="font-semibold">Belum ada laporan akhir.</div></div>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-slate-50 text-slate-600 text-xs uppercase sticky top-0 z-10">
                                <tr><th className="text-left px-3 py-2">Judul</th><th className="text-left px-3 py-2">Pengusul</th><th className="text-left px-3 py-2">Status</th><th className="text-left px-3 py-2">Tanggal</th><th className="text-left px-3 py-2">Aksi</th></tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {proposals.map((p) => (
                                    <tr key={p.id} className="hover:bg-slate-50">
                                        <td className="px-3 py-2"><div className="font-bold text-slate-800">{p.title}</div></td>
                                        <td className="px-3 py-2"><div>{p.submitter}</div><div className="text-xs text-slate-500">{p.identity_id}</div></td>
                                        <td className="px-3 py-2"><span className="inline-block px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">{p.status_label}</span></td>
                                        <td className="px-3 py-2 text-slate-500 whitespace-nowrap">{p.created_at}</td>
                                        <td className="px-3 py-2"><a href={p.show_url} className="text-[#1e3a6e] font-bold text-xs hover:underline">Lihat</a></td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
                {meta.last_page > 1 && <div className="mt-4 text-xs text-slate-500">Halaman {meta.current_page} dari {meta.last_page} · Total {meta.total}</div>}
            </div>
        </div>
    );
}