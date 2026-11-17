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

        return () => {
            alive = false;
        };
    }, []);

    if (loading) {
        return (
            <div className="flex items-center justify-center h-64 text-slate-500 dark:text-slate-400">
                Memuat…
            </div>
        );
    }

    return (
        <div className="max-w-4xl mx-auto space-y-6">
            <div>
                <h1 className="text-2xl font-bold text-slate-800 dark:text-slate-100">Pusat Pengaturan</h1>
                <p className="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Kelola akun, sistem, dan master data aplikasi.
                </p>
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

            {sections.map((section) => (
                <ModernPageCard key={section.title} noPadding>
                    <div className="px-5 py-3 border-b border-slate-100 dark:border-slate-700/70">
                        <h2 className="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            {section.title}
                        </h2>
                    </div>
                    <ul className="divide-y divide-slate-100 dark:divide-slate-700/60">
                        {section.items.map((item) => (
                            <li key={item.key}>
                                <a
                                    href={item.href}
                                    className="flex items-center gap-4 px-5 py-3.5 hover:bg-slate-50 dark:hover:bg-slate-700/40 transition-colors group"
                                >
                                    <span
                                        aria-hidden="true"
                                        className="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-200 shrink-0 text-lg"
                                    >
                                        {item.icon === "user" ? "👤"
                                            : item.icon === "lock" ? "🔒"
                                            : item.icon === "shield-lock" ? "🛡️"
                                            : item.icon === "shield-check" ? "✅"
                                            : item.icon === "shield" ? "🔐"
                                            : item.icon === "bell" ? "🔔"
                                            : item.icon === "toggle-right" ? "🎛️"
                                            : item.icon === "palette" ? "🎨"
                                            : item.icon === "file-type-pdf" ? "📄"
                                            : item.icon === "chart-histogram" ? "📊"
                                            : item.icon === "list-details" ? "📋"
                                            : item.icon === "refresh" ? "🔄"
                                            : item.icon === "database-export" ? "💾"
                                            : item.icon === "database-import" ? "📥"
                                            : item.icon === "folders" ? "📁"
                                            : item.icon === "calendar-event" ? "📅"
                                            : item.icon === "file-text" ? "📝"
                                            : item.icon === "file-pencil" ? "✍️"
                                            : item.icon === "book-2" ? "📚"
                                            : "⚙️"}
                                    </span>
                                    <div className="min-w-0 flex-1">
                                        <div className="flex items-center gap-2 flex-wrap">
                                            <span className="text-sm font-semibold text-slate-800 dark:text-slate-100 group-hover:text-[#1e3a6e] dark:group-hover:text-cyan-400 transition-colors">
                                                {item.title}
                                            </span>
                                            {!item.modern && (
                                                <span className="text-[10px] font-semibold uppercase tracking-wide px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-300">
                                                    Klasik
                                                </span>
                                            )}
                                        </div>
                                        <p className="text-xs text-slate-500 dark:text-slate-400 mt-0.5 truncate">
                                            {item.description}
                                        </p>
                                    </div>
                                    <span aria-hidden="true" className="text-slate-400 dark:text-slate-500 shrink-0 text-lg">
                                        ›
                                    </span>
                                </a>
                            </li>
                        ))}
                    </ul>
                </ModernPageCard>
            ))}

            {sections.length === 0 && !error && (
                <ModernPageCard>
                    <p className="text-sm text-slate-500 dark:text-slate-400">
                        Tidak ada pengaturan yang tersedia untuk akun Anda.
                    </p>
                </ModernPageCard>
            )}
        </div>
    );

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
