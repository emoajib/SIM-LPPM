// Vetted by SOSIOMEN - Manual Review Required by Senior Engineer/Manager
import React, { useEffect, useState } from "react";
import ModernPageCard from "./components/ModernPageCard.jsx";

export default function SettingsIndex() {
    const [sections, setSections] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        let alive = true;
        (async () => {
            try {
                const res = await fetch("/api/v1/settings/hub", {
                    credentials: "same-origin",
                    cache: "no-store",
                    headers: { Accept: "application/json" },
                });
                if (!res.ok) throw new Error("Gagal memuat daftar pengaturan.");
                const data = await res.json();
                if (alive) setSections(data.sections || []);
            } catch (e) {
                if (alive) setError(e.message);
            } finally {
                if (alive) setLoading(false);
            }
        })();
        return () => { alive = false; };
    }, []);

    if (loading) {
        return <div className="flex items-center justify-center h-64 text-slate-500 dark:text-slate-400">Memuat…</div>;
    }

    const totalItems = sections.reduce((a, s) => a + s.items.length, 0);
    const modernItems = sections.reduce((a, s) => a + s.items.filter((i) => i.modern).length, 0);

    const iconFor = (icon) =>
        icon === "user" ? "👤" : icon === "lock" ? "🔒" : icon === "shield-lock" ? "🛡️"
        : icon === "shield-check" ? "✅" : icon === "shield" ? "🔐" : icon === "bell" ? "🔔"
        : icon === "toggle-right" ? "🎛️" : icon === "palette" ? "🎨" : icon === "file-type-pdf" ? "📄"
        : icon === "chart-histogram" ? "📊" : icon === "list-details" ? "📋" : icon === "refresh" ? "🔄"
        : icon === "database-export" ? "💾" : icon === "database-import" ? "📥" : icon === "folders" ? "📁"
        : icon === "calendar-event" ? "📅" : icon === "file-text" ? "📝" : icon === "file-pencil" ? "✍️"
        : icon === "book-2" ? "📚" : "⚙️";

    return (
        <div className="max-w-7xl mx-auto space-y-4">
            <div>
                <h1 className="text-2xl font-bold text-slate-800 dark:text-slate-100">Pusat Pengaturan</h1>
                <p className="text-sm text-slate-500 dark:text-slate-400 mt-1">Kelola akun, sistem, dan master data aplikasi.</p>
            </div>
            <nav aria-label="Breadcrumb" className="text-xs text-slate-500 dark:text-slate-400">
                <a href="/dashboard" className="hover:underline">Dashboard</a>
                <span className="mx-1.5 opacity-60">/</span>
                <span className="font-semibold text-slate-700 dark:text-slate-200">Pengaturan</span>
            </nav>

            {error && (
                <div className="px-4 py-3 rounded-lg border text-sm bg-rose-50 border-rose-200 text-rose-800 dark:bg-rose-950/50 dark:border-rose-900/50 dark:text-rose-300">
                    ✕ {error}
                </div>
            )}

            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                <aside className="md:col-span-1">
                    <ModernPageCard noPadding>
                        {sections.map((section) => (
                            <div key={section.title} className="px-3 py-3 border-b border-slate-100 dark:border-slate-700/70 last:border-b-0">
                                <h4 className="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2 px-2">
                                    {section.title}
                                </h4>
                                <ul className="space-y-0.5">
                                    {section.items.map((item) => (
                                        <li key={item.key}>
                                            <a href={item.href} className="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700/50 transition-colors group">
                                                <span aria-hidden="true" className="w-5 h-5 flex items-center justify-center shrink-0 text-base">
                                                    {iconFor(item.icon)}
                                                </span>
                                                <span className="flex-1 truncate group-hover:text-[#1e3a6e] dark:group-hover:text-cyan-400 transition-colors">
                                                    {item.title}
                                                </span>
                                                {!item.modern && (
                                                    <span className="text-[10px] font-semibold uppercase tracking-wide px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-300 shrink-0">
                                                        Klasik
                                                    </span>
                                                )}
                                                <span aria-hidden="true" className="text-slate-400 dark:text-slate-500 shrink-0">›</span>
                                            </a>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        ))}
                    </ModernPageCard>
                </aside>

                <main className="md:col-span-3">
                    <ModernPageCard>
                        <div className="p-5">
                            <h3 className="text-lg font-bold text-slate-800 dark:text-slate-100 mb-2">
                                Selamat datang di Pusat Pengaturan
                            </h3>
                            <p className="text-sm text-slate-500 dark:text-slate-400">
                                Pilih pengaturan di sidebar untuk mengelola akun, sistem, dan master data.
                            </p>
                            <div className="mt-5 grid grid-cols-2 sm:grid-cols-3 gap-3">
                                <div className="p-3 rounded-lg bg-slate-50 dark:bg-slate-700/40">
                                    <div className="text-2xl font-bold text-[#1e3a6e] dark:text-cyan-400">{sections.length}</div>
                                    <div className="text-xs text-slate-500 dark:text-slate-400">Area Pengaturan</div>
                                </div>
                                <div className="p-3 rounded-lg bg-slate-50 dark:bg-slate-700/40">
                                    <div className="text-2xl font-bold text-[#1e3a6e] dark:text-cyan-400">{totalItems}</div>
                                    <div className="text-xs text-slate-500 dark:text-slate-400">Total Item</div>
                                </div>
                                <div className="p-3 rounded-lg bg-slate-50 dark:bg-slate-700/40">
                                    <div className="text-2xl font-bold text-[#1e3a6e] dark:text-cyan-400">{modernItems}</div>
                                    <div className="text-xs text-slate-500 dark:text-slate-400">Halaman Modern</div>
                                </div>
                            </div>
                        </div>
                    </ModernPageCard>
                </main>
            </div>

            {sections.length === 0 && !error && (
                <ModernPageCard>
                    <p className="text-sm text-slate-500 dark:text-slate-400">
                        Tidak ada pengaturan yang tersedia untuk akun Anda.
                    </p>
                </ModernPageCard>
            )}
        </div>
    );
}