// Vetted by AI - Manual Review Required by Senior Engineer/Manager
/**
 * SIM-LPPM Modern Theme Color & Status Constants
 * Single Source of Truth for React Layer
 */

export const THEME_PALETTE = {
    navy: {
        dark: "#11244e",
        DEFAULT: "#1e3a6e",
        light: "#254b8c",
        subtle: "rgba(30, 58, 110, 0.08)",
        border: "rgba(30, 58, 110, 0.15)",
    },
    cyan: {
        DEFAULT: "#06b6d4",
        hover: "#0891b2",
        subtle: "rgba(6, 182, 212, 0.1)",
    },
    emerald: {
        DEFAULT: "#10b981",
        hover: "#059669",
    },
    amber: {
        DEFAULT: "#f59e0b",
        hover: "#d97706",
    },
    rose: {
        DEFAULT: "#f43f5e",
        hover: "#e11d48",
    },
};

export const STATUS_COLORS = {
    draft: "bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700",
    submitted: "bg-sky-100 text-sky-800 border-sky-200 dark:bg-sky-950/60 dark:text-sky-300 dark:border-sky-800",
    need_assignment: "bg-blue-100 text-[#1e3a6e] border-blue-200 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800",
    approved: "bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800",
    waiting_reviewer: "bg-purple-100 text-purple-800 border-purple-200 dark:bg-purple-950/60 dark:text-purple-300 dark:border-purple-800",
    under_review: "bg-cyan-100 text-cyan-800 border-cyan-200 dark:bg-cyan-950/60 dark:text-cyan-300 dark:border-cyan-800",
    reviewed: "bg-teal-100 text-teal-800 border-teal-200 dark:bg-teal-950/60 dark:text-teal-300 dark:border-teal-800",
    revision_needed: "bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800",
    revision_submitted: "bg-orange-100 text-orange-800 border-orange-200 dark:bg-orange-950/60 dark:text-orange-300 dark:border-orange-800",
    completed: "bg-slate-200 text-slate-800 border-slate-300 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700",
    rejected: "bg-rose-100 text-rose-800 border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800",
};

/**
 * Single Source of Truth for module tab navigation (Modern Theme).
 * Defines the 5-stage "Alur Manajemen Penelitian & Pengabdian" tabs.
 * Base tabs are shared across all pages; the Verifier tab is added conditionally.
 */
export const MODULE_TABS = {
    research: [
        { label: "Usulan", href: "/research", icon: "📋" },
        { label: "Perbaikan Usulan", href: "/research/proposal-revision", icon: "✏️" },
        { label: "Laporan Akhir", href: "/research/final-report", icon: "📑" },
        { label: "Realisasi Anggaran", href: "/research/daily-note", icon: "💰" },
    ],
    communityService: [
        { label: "Usulan", href: "/community-service", icon: "🤝" },
        { label: "Perbaikan Usulan", href: "/community-service/proposal-revision", icon: "✏️" },
        { label: "Laporan Akhir", href: "/community-service/final-report", icon: "📑" },
        { label: "Realisasi Anggaran", href: "/community-service/daily-note", icon: "💰" },
    ],
};

/**
 * Verifier-only tab for output verification (MandatoryOutput / AdditionalOutput).
 * Only rendered when the current user is a verifier (admin lppm / kepala lppm / rektor).
 */
export const VERIFIER_TAB = {
    label: "Verifikasi Luaran",
    href: "/iku/verification",
    icon: "🛡️",
};

