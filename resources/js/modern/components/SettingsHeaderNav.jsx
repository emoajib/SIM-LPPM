// Vetted by SOSIOMEN - Manual Review Required by Senior Engineer/Manager
import React from "react";

export default function SettingsHeaderNav({
    title = "Pengaturan",
    subtitle = "",
    categoryBadge = "Pusat Kendali Pengaturan",
    currentPath = window.location.pathname.replace(/\/$/, ""),
    action = null,
}) {
    const userRoles = window.__USER_ROLES__ || [];
    const isAdmin = userRoles.some((r) => ["admin lppm", "superadmin"].includes(r));

    const tabs = [
        {
            label: "Katalog Pengaturan",
            href: "/settings/modern",
            icon: "ti ti-layout-grid",
        },
        {
            label: "Profil Saya",
            href: "/settings/profile/modern",
            icon: "ti ti-user",
        },
        {
            label: "Ubah Kata Sandi",
            href: "/settings/password/modern",
            icon: "ti ti-lock",
        },
        ...(isAdmin
            ? [
                  {
                      label: "Notifikasi (SMTP & WA)",
                      href: "/settings/notification-config/modern",
                      icon: "ti ti-bell",
                  },
                  {
                      label: "Feature Flags",
                      href: "/settings/feature-flags/modern",
                      icon: "ti ti-toggle-right",
                  },
                  {
                      label: "Keamanan CAPTCHA",
                      href: "/settings/captcha/modern",
                      icon: "ti ti-shield",
                  },
              ]
            : []),
    ];

    return (
        <div className="bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700/80 rounded-2xl shadow-xs overflow-hidden transition-colors duration-200 mb-6">
            {/* Header Content Area */}
            <div className="p-5 sm:p-6 pb-4 sm:pb-5">
                {/* Breadcrumb Navigation */}
                <nav aria-label="Breadcrumb" className="text-xs text-slate-500 dark:text-slate-400 mb-3">
                    <ol className="flex items-center gap-1.5 flex-wrap">
                        <li className="flex items-center gap-1">
                            <a href="/dashboard" className="hover:text-slate-800 dark:hover:text-slate-200 transition-colors flex items-center gap-1">
                                <i className="ti ti-home text-xs"></i>
                                <span>Dashboard</span>
                            </a>
                            <span className="text-slate-300 dark:text-slate-600">/</span>
                        </li>
                        <li className="flex items-center gap-1">
                            <a href="/settings/modern" className="hover:text-slate-800 dark:hover:text-slate-200 transition-colors">
                                Pengaturan
                            </a>
                            {title !== "Pusat Pengaturan" && (
                                <>
                                    <span className="text-slate-300 dark:text-slate-600">/</span>
                                    <span className="font-semibold text-slate-800 dark:text-slate-100">{title}</span>
                                </>
                            )}
                        </li>
                    </ol>
                </nav>

                {/* Title & Action Row */}
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div className="min-w-0">
                        {categoryBadge && (
                            <div className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#1e3a6e]/10 dark:bg-cyan-950/60 text-[#1e3a6e] dark:text-cyan-300 border border-[#1e3a6e]/15 dark:border-cyan-800/60 mb-2">
                                <i className="ti ti-adjustments-alt text-xs"></i>
                                {categoryBadge}
                            </div>
                        )}
                        <h1 className="text-2xl font-extrabold text-slate-900 dark:text-slate-50 tracking-tight">
                            {title}
                        </h1>
                        {subtitle && (
                            <p className="text-sm text-slate-500 dark:text-slate-400 mt-1 max-w-3xl leading-relaxed">
                                {subtitle}
                            </p>
                        )}
                    </div>
                    {action && <div className="shrink-0 flex items-center gap-2">{action}</div>}
                </div>
            </div>

            {/* Integrated Sub-Navigation Module Tabs */}
            <div className="border-t border-slate-100 dark:border-slate-700/60 bg-slate-50/50 dark:bg-slate-800/60 px-4 sm:px-6">
                <nav
                    role="tablist"
                    aria-label="Sub-modul Pengaturan"
                    className="flex gap-1 overflow-x-auto no-scrollbar scroll-smooth"
                >
                    {tabs.map((t) => {
                        const isActive = currentPath === t.href || (t.href !== "/settings/modern" && currentPath.startsWith(t.href));

                        return (
                            <a
                                key={t.href}
                                href={t.href}
                                role="tab"
                                aria-selected={isActive}
                                aria-current={isActive ? "page" : undefined}
                                className={`group inline-flex items-center gap-2 px-3.5 py-3 text-xs sm:text-sm font-semibold whitespace-nowrap border-b-2 transition-all duration-150 ${
                                    isActive
                                        ? "border-[#1e3a6e] dark:border-cyan-400 text-[#1e3a6e] dark:text-cyan-400 bg-white dark:bg-slate-800/90 shadow-2xs"
                                        : "border-transparent text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 hover:border-slate-300 dark:hover:border-slate-600"
                                }`}
                            >
                                <i
                                    className={`${t.icon} text-base transition-transform group-hover:scale-110 ${
                                        isActive ? "text-[#1e3a6e] dark:text-cyan-400" : "text-slate-400 dark:text-slate-500"
                                    }`}
                                ></i>
                                <span>{t.label}</span>
                            </a>
                        );
                    })}
                </nav>
            </div>
        </div>
    );
}
