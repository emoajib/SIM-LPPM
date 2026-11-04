// Vetted by AI - Manual Review Required by Senior Engineer/Manager
import React, { useEffect, useState } from "react";
import Button from "./components/Button.jsx";
import Modal from "./components/Modal.jsx";
import Pagination from "./components/Pagination.jsx";
import CountdownTimer from "./components/CountdownTimer.jsx";
import ModuleTabNav from "./components/ModuleTabNav.jsx";
import ModernPageCard from "./components/ModernPageCard.jsx";
import ModernTable from "./components/ModernTable.jsx";
import { STATUS_COLORS } from "./constants/themeColors.js";

export default function PengabdianIndex() {
    const [proposals, setProposals] = useState([]);
    const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0 });
    const [page, setPage] = useState(1);
    const [search, setSearch] = useState("");
    const [status, setStatus] = useState("all");
    const [roleFilter, setRoleFilter] = useState("all");
    const [loading, setLoading] = useState(true);
    const [filtersMeta, setFiltersMeta] = useState({ years: [], schemes: [], statuses: [], faculties: [], study_programs: [] });

    useEffect(() => {
        fetch(`/research/api/v1/filters?type=community_service`, { credentials: "same-origin", cache: "no-store" })
            .then((r) => r.json())
            .then((d) => setFiltersMeta(d))
            .catch(() => {});
    }, []);
    const isDosen = (window.__USER_ROLES__ || []).includes("dosen");
    const isVerifier = (window.__USER_ROLES__ || []).some((r) => ["admin lppm", "kepala lppm", "rektor"].includes(r));
    const [error, setError] = useState(null);
    const [showVerif, setShowVerif] = useState(false);
    const [showElig, setShowElig] = useState(false);
    const [elig, setElig] = useState(null);

    const load = async (p = page) => {
        setLoading(true);
        setError(null);
        try {
            const params = new URLSearchParams({ search, status, type: "community-service", role: roleFilter, page: p, _: Date.now() });
            const res = await fetch(`/research/api/v1/proposals?${params.toString()}`, { credentials: "same-origin", cache: "no-store" });
            if (!res.ok) throw new Error("HTTP " + res.status);
            const json = await res.json();
            setProposals(json.data || []);
            setMeta(json.meta || meta);
            setPage(p);
        } catch (e) {
            console.error("Pengabdian load error:", e);
            setError("Gagal memuat data. Silakan coba lagi.");
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => { load(1); }, [roleFilter]);

    useEffect(() => {
        if (!isDosen) return;
        fetch(`/research/api/v1/eligibility?type=community_service`, { credentials: "same-origin", cache: "no-store" })
            .then((r) => r.ok ? r.json() : null)
            .then((j) => { if (j) setElig(j); })
            .catch(() => {});
    }, []);

    const loadEligibility = async () => {
        try {
            const res = await fetch(`/research/api/v1/eligibility?type=community_service`, { credentials: "same-origin", cache: "no-store" });
            if (res.ok) setElig(await res.json());
        } catch (e) { console.error(e); }
        setShowElig(true);
    };

    const tabs = [
        { label: "Usulan", href: "/community-service", icon: "🤝" },
        { label: "Perbaikan Usulan", href: "/community-service/proposal-revision", icon: "✏️" },
        { label: "Laporan Akhir", href: "/community-service/final-report", icon: "📑" },
        { label: "Realisasi Anggaran", href: "/community-service/daily-note", icon: "💰" },
        ...(isVerifier ? [{ label: "Verifikasi Luaran", href: "#", icon: "🛡️", isActive: showVerif, onClick: () => setShowVerif((v) => !v) }] : []),
    ];
    const currentPath = window.location.pathname.replace(/\/$/, "");

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
            <nav aria-label="Breadcrumb" className="text-xs text-slate-500 dark:text-slate-400 mb-1">
                <a href="/dashboard" className="hover:underline">Dashboard</a>
                <span className="mx-1.5 opacity-60">/</span>
                <span className="font-semibold text-slate-700 dark:text-slate-200">Pengabdian</span>
            </nav>

            <ModuleTabNav tabs={tabs} currentPath={currentPath} />

            {elig && elig.schedule_start && elig.schedule_end && (
                <CountdownTimer start={elig.schedule_start} end={elig.schedule_end} />
            )}

            <ModernPageCard>
                <div className="flex flex-col md:flex-row gap-3 mb-4">
                    <input
                        type="text"
                        value={search}
                        aria-label="Cari usulan pengabdian berdasarkan judul"
                        onChange={(e) => setSearch(e.target.value)}
                        onKeyDown={(e) => e.key === "Enter" && load()}
                        placeholder="Cari berdasarkan judul..."
                        className="flex-1 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e] dark:focus:ring-cyan-500"
                    />
                    <select
                        value={roleFilter}
                        aria-label="Filter pengabdian berdasarkan peran"
                        onChange={(e) => setRoleFilter(e.target.value)}
                        className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e] dark:focus:ring-cyan-500"
                    >
                        <option value="all">Semua Peran</option>
                        <option value="ketua">Sebagai Ketua</option>
                        <option value="anggota">Sebagai Anggota</option>
                    </select>
                    <select
                        value={status}
                        aria-label="Filter pengabdian berdasarkan status"
                        onChange={(e) => setStatus(e.target.value)}
                        className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e] dark:focus:ring-cyan-500"
                    >
                        <option value="all">Semua Status</option>
                        {Array.isArray(filtersMeta.statuses) && filtersMeta.statuses.map((s) => (
                            <option key={s.value} value={s.value}>{s.label}</option>
                        ))}
                    </select>
                    <Button onClick={() => load(1)} variant="primary" size="md">
                        Cari
                    </Button>
                    {isDosen && (
                        <>
                            <Button onClick={loadEligibility} variant="secondary" size="md">
                                Info Eligibilitas
                            </Button>
                            {elig && elig.schedule_open ? (
                                elig.eligible && Array.isArray(elig.schemes) && elig.schemes.length > 0 ? (
                                    elig.can_create ? (
                                        <a href="/community-service/proposal/create" className="inline-flex items-center justify-center bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-2 rounded-lg text-sm font-bold shadow-sm transition">
                                            + Buat Usulan
                                        </a>
                                    ) : (
                                        <button type="button" disabled title="Kuota terbatas" className="bg-slate-300 dark:bg-slate-700 text-slate-600 dark:text-slate-400 px-4 py-2 rounded-lg text-sm font-bold cursor-not-allowed">
                                            Kuota Terbatas
                                        </button>
                                    )
                                ) : (
                                    <button type="button" disabled title={(Array.isArray(elig.reasons) && elig.reasons.length ? elig.reasons.join(" ") : "Anda tidak memenuhi syarat untuk skema pengabdian manapun.")} className="bg-slate-300 dark:bg-slate-700 text-slate-600 dark:text-slate-400 px-4 py-2 rounded-lg text-sm font-bold cursor-not-allowed">
                                        Usulan Dikunci
                                    </button>
                                )
                            ) : null}
                        </>
                    )}
                </div>

                <div className="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div className="overflow-x-auto">
                        <ModernTable
                            columns={columns}
                            data={proposals}
                            loading={loading}
                            emptyIcon={error ? "ti ti-alert-triangle" : "ti ti-certificate"}
                            emptyTitle={error ? "Gagal memuat data" : "Tidak ada data pengabdian"}
                            emptyDescription={error || "Belum ada usulan pengabdian yang tercatat pada sistem."}
                            emptyAction={error ? <Button onClick={() => load(1)}>Coba Lagi</Button> : null}
                            ariaLabel="Daftar Usulan Pengabdian Masyarakat"
                        />
                    </div>
                </div>

                <Pagination meta={meta} onPage={(p) => load(p)} />

                {isDosen && elig && elig.can_create && (
                    <a href="/community-service/proposal/create" className="fixed bottom-6 right-6 w-14 h-14 rounded-full bg-teal-600 hover:bg-teal-700 text-white flex items-center justify-center shadow-lg text-2xl z-50 transition" title="Buat Usulan Pengabdian">
                        +
                    </a>
                )}

                <Modal open={showElig} onClose={() => setShowElig(false)} title="Info Eligibilitas Pengabdian">
                    {!elig ? (
                        <div className="text-slate-500 py-4 text-center">Memuat data kualifikasi...</div>
                    ) : (
                        <div className="space-y-3 text-sm">
                            <div className={`p-3 rounded-xl font-bold flex items-center gap-2 ${elig.eligible ? "bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300" : "bg-rose-50 text-rose-800 dark:bg-rose-950/50 dark:text-rose-300"}`}>
                                <span>{elig.eligible ? "✓" : "⚠"}</span>
                                <span>{elig.eligible ? "Memenuhi Syarat Pengajuan" : "Tidak Memenuhi Syarat Pengajuan"}</span>
                            </div>
                            {Array.isArray(elig.reasons) && elig.reasons.length > 0 && (
                                <ul className="list-disc pl-5 text-slate-600 dark:text-slate-300 space-y-1">
                                    {elig.reasons.map((r, i) => <li key={i}>{r}</li>)}
                                </ul>
                            )}
                            <div className="pt-2 border-t border-slate-100 dark:border-slate-700 text-xs text-slate-500 dark:text-slate-400 space-y-1">
                                <div>Status Jadwal: <span className="font-semibold text-slate-700 dark:text-slate-200">{elig.schedule_open ? "Buka" : "Tutup"}</span></div>
                                <div>Hak Membuat Usulan: <span className="font-semibold text-slate-700 dark:text-slate-200">{elig.can_create ? "Tersedia" : "Terkunci"}</span></div>
                            </div>
                        </div>
                    )}
                </Modal>
            </ModernPageCard>
        </div>
    );
}
