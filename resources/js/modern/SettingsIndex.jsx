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
        <div className="max-w-7xl mx-auto space-y-8">
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
                <section key={section.title} aria-labelledby={`settings-section-${section.title}`}>
                    <h2
                        id={`settings-section-${section.title}`}
                        className="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-3 pb-2 border-b border-slate-200 dark:border-slate-700"
                    >
                        {section.title}
                    </h2>

                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        {section.items.map((item) => (
                            <a
                                key={item.key}
                                href={item.href}
                                className="group block focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1e3a6e] rounded-xl"
                            >
                                <ModernPageCard className="h-full group-hover:border-[#1e3a6e] dark:group-hover:border-[#4a7bc8] group-hover:shadow-md transition-all">
                                    <div className="flex items-start gap-3">
                                        <span
                                            aria-hidden="true"
                                            className="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-200 shrink-0"
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
                                        <span className="min-w-0">
                                            <span className="flex items-center gap-2 flex-wrap">
                                                <span className="text-base font-semibold text-slate-800 dark:text-slate-100">
                                                    {item.title}
                                                </span>
                                                {!item.modern && (
                                                    <span className="text-[10px] font-semibold uppercase tracking-wide px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-300">
                                                        Klasik
                                                    </span>
                                                )}
                                            </span>
                                            <span className="block mt-1 text-sm text-slate-500 dark:text-slate-400">
                                                {item.description}
                                            </span>
                                        </span>
                                    </div>
                                </ModernPageCard>
                            </a>
                        ))}
                    </div>
                </section>
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
}
