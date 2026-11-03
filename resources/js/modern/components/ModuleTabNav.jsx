// Vetted by AI - Manual Review Required by Senior Engineer/Manager
import React, { useRef, useEffect } from "react";

/**
 * Accessible & responsive module tab navigation for Modern Theme
 */
export default function ModuleTabNav({ tabs = [], currentPath = "", onTabClick = null, className = "" }) {
    const activeTabRef = useRef(null);

    useEffect(() => {
        if (activeTabRef.current) {
            activeTabRef.current.scrollIntoView({
                behavior: "smooth",
                block: "nearest",
                inline: "center",
            });
        }
    }, [currentPath]);

    return (
        <nav
            role="tablist"
            aria-label="Navigasi Modul"
            className={`bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm px-3 pt-2.5 transition-colors duration-200 ${className}`}
        >
            <div className="flex gap-1 overflow-x-auto no-scrollbar scroll-smooth">
                {tabs.map((t) => {
                    const isActive = t.isActive !== undefined 
                        ? t.isActive 
                        : (currentPath === t.href || (t.href !== "#" && currentPath.startsWith(t.href + "/")));

                    const handleClick = (e) => {
                        if (t.onClick) {
                            e.preventDefault();
                            t.onClick(e);
                        } else if (onTabClick) {
                            onTabClick(t, e);
                        }
                    };

                    return (
                        <a
                            key={t.href + t.label}
                            ref={isActive ? activeTabRef : null}
                            role="tab"
                            aria-selected={isActive}
                            aria-current={isActive ? "page" : undefined}
                            href={t.href}
                            onClick={handleClick}
                            className={`group inline-flex items-center gap-2 px-3.5 py-2 text-sm font-semibold whitespace-nowrap border-b-2 rounded-t-lg transition-all duration-150 ${
                                isActive
                                    ? "border-[#1e3a6e] dark:border-cyan-400 text-[#1e3a6e] dark:text-cyan-400 bg-slate-50/80 dark:bg-slate-700/50"
                                    : "border-transparent text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 hover:border-slate-300 dark:hover:border-slate-600"
                            }`}
                        >
                            {t.icon && (
                                <span className={`text-base transition-transform group-hover:scale-110 ${isActive ? "text-[#1e3a6e] dark:text-cyan-400" : "text-slate-400 dark:text-slate-500"}`}>
                                    {t.icon}
                                </span>
                            )}
                            <span>{t.label}</span>
                            {t.badge !== undefined && t.badge !== null && (
                                <span className={`text-[10px] font-bold px-1.5 py-0.5 rounded-full ${
                                    isActive
                                        ? "bg-[#1e3a6e] text-white dark:bg-cyan-500 dark:text-slate-900"
                                        : "bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300"
                                }`}>
                                    {t.badge}
                                </span>
                            )}
                        </a>
                    );
                })}
            </div>
        </nav>
    );
}

