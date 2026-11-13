// Vetted by AI - Manual Review Required by Senior Engineer/Manager
import React, { useEffect, useState } from "react";
import Button from "./components/Button.jsx";
import Modal from "./components/Modal.jsx";
import ModuleTabNav from "./components/ModuleTabNav.jsx";
import ModernPageCard from "./components/ModernPageCard.jsx";
import ModernTable from "./components/ModernTable.jsx";
import { MODULE_TABS, VERIFIER_TAB, getVerifierTab } from "./constants/themeColors.js";

function formatRupiah(value) {
    const num = value == null ? 0 : Number(value);
    return new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(num);
}

export default function ProposalShowIndex() {
    const [proposal, setProposal] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    const userRoles = window.__USER_ROLES__ || [];
    const isVerifier = userRoles.some((r) => ["admin lppm", "kepala lppm", "rektor", "dosen"].includes(r));
    const isResearch = window.location.pathname.includes("/research/");
    const module = isResearch ? "research" : "communityService";
    const apiBase = isResearch ? "/research" : "/community-service";

    const tabs = [
        ...MODULE_TABS[module],
        ...(isVerifier ? [getVerifierTab(module)] : []),
    ];
    const currentPath = window.location.pathname.replace(/\/$/, "");

    useEffect(() => {
        const id = window.__PROPOSAL_ID__;
        if (!id) { setError("ID proposal tidak ditemukan."); setLoading(false); return; }
        fetch(`${apiBase}/api/v1/proposals/${id}`, { credentials: "same-origin", cache: "no-store" })
            .then((r) => { if (!r.ok) throw new Error("HTTP " + r.status); return r.json(); })
            .then((json) => { setProposal(json); setLoading(false); })
            .catch((e) => { console.error(e); setError("Gagal memuat detail proposal."); setLoading(false); });
    }, []);

    if (loading) return <div className="text-center py-8 text-slate-500">Memuat detail proposal...</div>;
    if (error) return <div className="text-center py-8 text-rose-500">{error}</div>;
    if (!proposal) return <div className="text-center py-8 text-slate-500">Proposal tidak ditemukan.</div>;

    const statusColor = (s) => ({ draft: "bg-slate-100 text-slate-700", submitted: "bg-sky-100 text-sky-800", approved: "bg-emerald-100 text-emerald-800", completed: "bg-slate-200 text-slate-800", rejected: "bg-rose-100 text-rose-800", revision_needed: "bg-amber-100 text-amber-800" }[s] || "bg-gray-100 text-gray-700");

    const budgetColumns = [
        { key: "group", label: "Kelompok", render: (b) => b.group || "-" },
        { key: "component", label: "Komponen", render: (b) => b.component || b.item_description || "-" },
        { key: "volume", label: "Volume", cellClassName: "text-center", render: (b) => b.volume },
        { key: "unit_price", label: "Harga Satuan", cellClassName: "text-right", render: (b) => formatRupiah(b.unit_price) },
        { key: "total_price", label: "Total", cellClassName: "text-right font-bold", render: (b) => formatRupiah(b.total_price) },
    ];

    const reviewerColumns = [
        { key: "name", label: "Reviewer", render: (r) => r.name },
        { key: "status", label: "Status", render: (r) => r.status },
        { key: "recommendation", label: "Rekomendasi", render: (r) => r.recommendation || "-" },
        { key: "completed_at", label: "Selesai", render: (r) => r.completed_at || "-" },
    ];

    return (
        <div className="space-y-4">
            <nav aria-label="Breadcrumb" className="text-xs text-slate-500 dark:text-slate-400 mb-1">
                <a href="/dashboard" className="hover:underline">Dashboard</a>
                <span className="mx-1.5 opacity-60">/</span>
                <a href={isResearch ? "/research" : "/community-service"} className="hover:underline">{isResearch ? "Penelitian" : "Pengabdian"}</a>
                <span className="mx-1.5 opacity-60">/</span>
                <span className="font-semibold text-slate-700 dark:text-slate-200">Detail Proposal</span>
            </nav>

            <ModuleTabNav tabs={tabs} currentPath={currentPath} />

            <ModernPageCard>
                <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
                    <div>
                        <h1 className="text-xl font-bold text-slate-800 dark:text-slate-100">{proposal.title}</h1>
                        <div className="flex items-center gap-2 mt-1">
                            <span className={`inline-block px-2.5 py-0.5 rounded-full text-xs font-bold border ${statusColor(proposal.status)}`}>
                                {proposal.status_label}
                            </span>
                            <span className="text-xs text-slate-500 dark:text-slate-400">{proposal.created_at}</span>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        {proposal.can_edit && (
                            <a href={proposal.edit_url} className="inline-flex items-center gap-1 px-3 py-2 rounded-lg text-sm font-bold text-white bg-[#1e3a6e] hover:bg-[#254b8c] transition">
                                ✏️ Edit
                            </a>
                        )}
                        <a href={proposal.preview_pdf_url} target="_blank" rel="noopener noreferrer" className="inline-flex items-center gap-1 px-3 py-2 rounded-lg text-sm font-bold text-[#1e3a6e] dark:text-cyan-400 border border-[#1e3a6e]/30 hover:bg-slate-100 dark:hover:bg-slate-700/60 transition">
                            👁️ Preview PDF
                        </a>
                        <a href={proposal.export_pdf_url} target="_blank" rel="noopener noreferrer" className="inline-flex items-center gap-1 px-3 py-2 rounded-lg text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 transition">
                            ⬇️ Unduh PDF
                        </a>
                    </div>
                </div>

                {/* Info Grid */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div className="space-y-2">
                        <div className="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold">Pengusul (Ketua)</div>
                        <div className="font-medium text-slate-800 dark:text-slate-100">{proposal.submitter?.name}</div>
                        <div className="text-xs text-slate-500 dark:text-slate-400">{proposal.submitter?.identity_id} — {proposal.submitter?.faculty} / {proposal.submitter?.study_program}</div>
                    </div>
                    <div className="space-y-2">
                        <div className="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold">Skema</div>
                        <div className="font-medium text-slate-800 dark:text-slate-100">{proposal.scheme || "-"}</div>
                    </div>
                    <div className="space-y-2">
                        <div className="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold">Area Fokus</div>
                        <div className="font-medium text-slate-800 dark:text-slate-100">{proposal.focus_area || "-"}</div>
                    </div>
                    <div className="space-y-2">
                        <div className="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold">Durasi / Tahun Mulai</div>
                        <div className="font-medium text-slate-800 dark:text-slate-100">{proposal.duration_in_years} tahun / {proposal.start_year} ({proposal.semester})</div>
                    </div>
                    {proposal.theme && (
                        <div className="space-y-2">
                            <div className="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold">Tema</div>
                            <div className="font-medium text-slate-800 dark:text-slate-100">{proposal.theme}</div>
                        </div>
                    )}
                    {proposal.topic && (
                        <div className="space-y-2">
                            <div className="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold">Topik</div>
                            <div className="font-medium text-slate-800 dark:text-slate-100">{proposal.topic}</div>
                        </div>
                    )}
                    {proposal.contract_number && (
                        <div className="space-y-2">
                            <div className="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold">No. Kontrak</div>
                            <div className="font-medium text-slate-800 dark:text-slate-100">{proposal.contract_number} ({proposal.contract_date})</div>
                        </div>
                    )}
                </div>

                {/* Summary */}
                {proposal.summary && (
                    <div className="mb-6">
                        <div className="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold mb-1">Ringkasan</div>
                        <div className="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-wrap">{proposal.summary}</div>
                    </div>
                )}

                {/* Budget Items (RAB) */}
                {proposal.budget_items && proposal.budget_items.length > 0 && (
                    <div className="mb-6">
                        <h3 className="text-sm font-bold text-slate-800 dark:text-slate-100 mb-2">Rencana Anggaran Biaya (RAB)</h3>
                        <ModernTable
                            columns={budgetColumns}
                            data={proposal.budget_items}
                            loading={false}
                            emptyTitle="Tidak ada item anggaran"
                            ariaLabel="Rencana Anggaran Biaya"
                        />
                        <div className="text-right mt-2 text-sm font-bold text-slate-800 dark:text-slate-100">
                            Total: {formatRupiah(proposal.budget_items.reduce((s, b) => s + (b.total_price || 0), 0))}
                        </div>
                    </div>
                )}

                {/* Team Members */}
                {proposal.team_members && proposal.team_members.length > 0 && (
                    <div className="mb-6">
                        <h3 className="text-sm font-bold text-slate-800 dark:text-slate-100 mb-2">Anggota Tim</h3>
                        <div className="flex flex-wrap gap-2">
                            {proposal.team_members.map((tm) => (
                                <span key={tm.id} className="inline-block px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-600">
                                    {tm.name} ({tm.role})
                                </span>
                            ))}
                        </div>
                    </div>
                )}

                {/* Reviewers */}
                {proposal.reviewers && proposal.reviewers.length > 0 && (
                    <div className="mb-6">
                        <h3 className="text-sm font-bold text-slate-800 dark:text-slate-100 mb-2">Reviewer</h3>
                        <ModernTable
                            columns={reviewerColumns}
                            data={proposal.reviewers}
                            loading={false}
                            emptyTitle="Belum ada reviewer"
                            ariaLabel="Daftar Reviewer"
                        />
                    </div>
                )}

                {/* Status Logs */}
                {proposal.status_logs && proposal.status_logs.length > 0 && (
                    <div className="mb-6">
                        <h3 className="text-sm font-bold text-slate-800 dark:text-slate-100 mb-2">Riwayat Status</h3>
                        <div className="space-y-2">
                            {proposal.status_logs.map((sl, i) => (
                                <div key={i} className="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-400">
                                    <span className="font-mono text-slate-400">{sl.at}</span>
                                    <span className="font-semibold text-slate-700 dark:text-slate-300">{sl.status_before} → {sl.status_after}</span>
                                    {sl.user && <span>oleh {sl.user}</span>}
                                    {sl.notes && <span className="text-slate-500">({sl.notes})</span>}
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* Outputs */}
                {proposal.outputs && proposal.outputs.length > 0 && (
                    <div className="mb-6">
                        <h3 className="text-sm font-bold text-slate-800 dark:text-slate-100 mb-2">Luaran yang Dijanjikan</h3>
                        <div className="flex flex-wrap gap-2">
                            {proposal.outputs.map((o) => (
                                <span key={o.id} className="inline-block px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-700">
                                    {o.category} — {o.target_status || "draft"}
                                </span>
                            ))}
                        </div>
                    </div>
                )}
            </ModernPageCard>
        </div>
    );
}
