// Vetted by SOSIOMEN - Manual Review Required by Senior Engineer/Manager
import React, { useEffect, useMemo, useState } from "react";
import EmptyState from "./components/EmptyState.jsx";
import Skeleton from "./components/Skeleton.jsx";
import SettingsHeaderNav from "./components/SettingsHeaderNav.jsx";

export default function SettingsIndex() {
    const [sections, setSections] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [searchQuery, setSearchQuery] = useState("");
    const [activeCategory, setActiveCategory] = useState("all");

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

    const categoryIcon = (title) => {
        if (/akun/i.test(title)) return "ti ti-user-cog";
        if (/sistem/i.test(title)) return "ti ti-settings-2";
        if (/master|akademik/i.test(title)) return "ti ti-database";
        return "ti ti-adjustments-horizontal";
    };

    const itemIconClass = (icon) => {
        if (!icon) return "ti ti-settings";
        return `ti ti-${icon}`;
    };

    const totalItemsCount = useMemo(() => {
        return sections.reduce((acc, sec) => acc + (sec.items?.length || 0), 0);
    }, [sections]);

    const filteredSections = useMemo(() => {
        const query = searchQuery.trim().toLowerCase();

        return sections
            .map((section) => {
                if (activeCategory !== "all" && section.title.toLowerCase() !== activeCategory.toLowerCase()) {
                    return null;
                }

                if (!query) return section;

                const filteredItems = (section.items || []).filter((item) => {
                    const matchTitle = (item.title || "").toLowerCase().includes(query);
                    const matchDesc = (item.description || "").toLowerCase().includes(query);
                    const matchKey = (item.key || "").toLowerCase().includes(query);
                    return matchTitle || matchDesc || matchKey;
                });

                return {
                    ...section,
                    items: filteredItems,
                };
            })
            .filter((section) => section && (section.items?.length || 0) > 0);
    }, [sections, activeCategory, searchQuery]);

    const headerAction = (
        <div className="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-700/60 border border-slate-200/60 dark:border-slate-600/60 text-xs font-semibold text-slate-700 dark:text-slate-200">
            <i className="ti ti-layers-linked text-[#1e3a6e] dark:text-cyan-400 text-sm"></i>
            <span>{totalItemsCount} Modul Tersedia</span>
        </div>
    );

    return (
        <div className="space-y-6">
            <SettingsHeaderNav
                title="Pusat Pengaturan"
                subtitle="Kelola konfigurasi akun pribadi, preferensi keamanan, sistem notifikasi, dan tata kelola master data SIM-LPPM."
                categoryBadge="Pusat Kendali Pengaturan"
                currentPath="/settings/modern"
                action={headerAction}
            />

            {/* Search & Category Filter Toolbar */}
            <div className="bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700/80 rounded-2xl p-4 sm:p-5 shadow-xs">
                <div className="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3.5">
                    {/* Live Search Input */}
                    <div className="relative flex-1 max-w-md">
                        <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                            <i className="ti ti-search text-base"></i>
                        </div>
                        <input
                            type="text"
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            placeholder="Cari pengaturan (profil, sandi, notifikasi, skema)..."
                            aria-label="Cari modul pengaturan"
                            className="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl pl-10 pr-9 py-2 text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e] dark:focus:ring-cyan-500 focus:bg-white dark:focus:bg-slate-900 transition-all"
                        />
                        {searchQuery && (
                            <button
                                type="button"
                                onClick={() => setSearchQuery("")}
                                className="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"
                                aria-label="Hapus pencarian"
                            >
                                <i className="ti ti-x text-sm"></i>
                            </button>
                        )}
                    </div>

                    {/* Category Filter Pills */}
                    <div className="flex items-center gap-1.5 overflow-x-auto pb-1 lg:pb-0 no-scrollbar">
                        <button
                            type="button"
                            onClick={() => setActiveCategory("all")}
                            className={`px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-colors ${
                                activeCategory === "all"
                                    ? "bg-[#1e3a6e] text-white dark:bg-cyan-500 dark:text-slate-950 shadow-2xs"
                                    : "bg-slate-100 dark:bg-slate-700/60 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700"
                            }`}
                        >
                            Semua ({totalItemsCount})
                        </button>
                        {sections.map((section) => (
                            <button
                                key={section.title}
                                type="button"
                                onClick={() => setActiveCategory(section.title)}
                                className={`px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-colors flex items-center gap-1.5 ${
                                    activeCategory.toLowerCase() === section.title.toLowerCase()
                                        ? "bg-[#1e3a6e] text-white dark:bg-cyan-500 dark:text-slate-950 shadow-2xs"
                                        : "bg-slate-100 dark:bg-slate-700/60 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700"
                                }`}
                            >
                                <i className={categoryIcon(section.title)}></i>
                                <span>{section.title}</span>
                                <span className="opacity-70 font-normal">({section.items?.length || 0})</span>
                            </button>
                        ))}
                    </div>
                </div>
            </div>

            {/* Error Notification */}
            {error && (
                <div className="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-900/60 text-rose-800 dark:text-rose-200 flex items-center justify-between gap-3 text-sm">
                    <div className="flex items-center gap-2">
                        <i className="ti ti-alert-circle text-lg"></i>
                        <span>{error}</span>
                    </div>
                    <button
                        type="button"
                        onClick={() => window.location.reload()}
                        className="px-3 py-1 bg-white dark:bg-slate-800 border border-rose-300 dark:border-rose-700 rounded-lg text-xs font-bold text-rose-700 dark:text-rose-300 hover:bg-rose-100/50"
                    >
                        Muat Ulang
                    </button>
                </div>
            )}

            {/* Loading State with Skeleton Cards */}
            {loading && (
                <div className="space-y-6">
                    {[1, 2].map((s) => (
                        <div key={s} className="space-y-3">
                            <Skeleton className="h-5 w-48 rounded" />
                            <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                                {[1, 2, 3].map((c) => (
                                    <div
                                        key={c}
                                        className="bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700/80 rounded-2xl p-5 space-y-3"
                                    >
                                        <div className="flex items-center justify-between">
                                            <Skeleton className="h-10 w-10 rounded-xl" />
                                            <Skeleton className="h-4 w-16 rounded-full" />
                                        </div>
                                        <Skeleton className="h-5 w-3/4 rounded" />
                                        <Skeleton className="h-4 w-full rounded" />
                                        <Skeleton className="h-4 w-2/3 rounded" />
                                    </div>
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
            )}

            {/* Categorized Settings Grid */}
            {!loading && filteredSections.length > 0 && (
                <div className="space-y-8">
                    {filteredSections.map((section) => (
                        <div key={section.title} className="space-y-4">
                            {/* Section Header */}
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-2.5">
                                    <div className="w-8 h-8 rounded-lg bg-[#1e3a6e]/10 dark:bg-cyan-950/60 text-[#1e3a6e] dark:text-cyan-400 flex items-center justify-center text-lg">
                                        <i className={categoryIcon(section.title)}></i>
                                    </div>
                                    <h2 className="text-base font-bold text-slate-800 dark:text-slate-100 tracking-tight">
                                        {section.title}
                                    </h2>
                                    <span className="px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 dark:bg-slate-700/60 text-slate-600 dark:text-slate-400 border border-slate-200/60 dark:border-slate-600/60">
                                        {section.items.length}
                                    </span>
                                </div>
                            </div>

                            {/* Responsive Card Grid */}
                            <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                                {section.items.map((item) => (
                                    <a
                                        key={item.key}
                                        href={item.href}
                                        className="group relative bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700/80 rounded-2xl p-5 shadow-2xs hover:shadow-md hover:border-[#1e3a6e]/40 dark:hover:border-cyan-500/40 hover:-translate-y-0.5 transition-all duration-200 flex flex-col justify-between"
                                    >
                                        <div>
                                            {/* Card Top: Icon & Badge */}
                                            <div className="flex items-center justify-between mb-3.5">
                                                <div className="w-11 h-11 rounded-xl flex items-center justify-center bg-slate-100 dark:bg-slate-700/70 text-[#1e3a6e] dark:text-cyan-400 group-hover:bg-[#1e3a6e] group-hover:text-white dark:group-hover:bg-cyan-500 dark:group-hover:text-slate-950 transition-colors duration-200 text-xl shadow-2xs">
                                                    <i className={itemIconClass(item.icon)}></i>
                                                </div>

                                                {item.modern ? (
                                                    <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200/70 dark:border-emerald-800/70">
                                                        <span className="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                        Modern
                                                    </span>
                                                ) : (
                                                    <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-slate-100 dark:bg-slate-700/60 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-600">
                                                        Klasik
                                                    </span>
                                                )}
                                            </div>

                                            {/* Card Title & Description */}
                                            <h3 className="text-sm font-bold text-slate-800 dark:text-slate-100 group-hover:text-[#1e3a6e] dark:group-hover:text-cyan-400 transition-colors mb-1.5">
                                                {item.title}
                                            </h3>
                                            <p className="text-xs text-slate-500 dark:text-slate-400 line-clamp-2 leading-relaxed">
                                                {item.description}
                                            </p>
                                        </div>

                                        {/* Card Bottom: Action Link */}
                                        <div className="mt-4 pt-3 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-between text-xs font-semibold text-[#1e3a6e] dark:text-cyan-400 group-hover:text-[#11244e] dark:group-hover:text-cyan-300">
                                            <span>Kelola Modul</span>
                                            <i className="ti ti-arrow-right text-sm transform group-hover:translate-x-1 transition-transform"></i>
                                        </div>
                                    </a>
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
            )}

            {/* Empty Search Result State */}
            {!loading && filteredSections.length === 0 && (
                <div className="bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700/80 rounded-2xl p-8 shadow-xs">
                    <EmptyState
                        icon="ti ti-search-off"
                        title="Pengaturan tidak ditemukan"
                        description={
                            searchQuery
                                ? `Tidak ada pengaturan yang cocok dengan kata kunci "${searchQuery}". Silakan coba kata kunci lain.`
                                : "Tidak ada pengaturan yang tersedia untuk akun Anda."
                        }
                        action={
                            searchQuery ? (
                                <button
                                    type="button"
                                    onClick={() => {
                                        setSearchQuery("");
                                        setActiveCategory("all");
                                    }}
                                    className="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-[#1e3a6e] text-white hover:bg-[#11244e] dark:bg-cyan-500 dark:text-slate-950 dark:hover:bg-cyan-400 transition-colors shadow-2xs"
                                >
                                    <i className="ti ti-rotate-clockwise"></i>
                                    Reset Pencarian
                                </button>
                            ) : null
                        }
                    />
                </div>
            )}
        </div>
    );
}
