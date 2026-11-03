// Vetted by AI - Manual Review Required by Senior Engineer/Manager
import React from "react";

const VARIANTS = {
    default: "bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-700 dark:text-slate-200 dark:border-slate-600",
    primary: "bg-[#1e3a6e]/10 text-[#1e3a6e] border-[#1e3a6e]/20 dark:bg-[#254b8c]/30 dark:text-sky-200 dark:border-[#254b8c]/40",
    navy: "bg-[#1e3a6e]/10 text-[#1e3a6e] border-[#1e3a6e]/20 dark:bg-[#254b8c]/30 dark:text-sky-200 dark:border-[#254b8c]/40",
    success: "bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-200 dark:border-emerald-700",
    warning: "bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-900/40 dark:text-amber-200 dark:border-amber-700",
    danger: "bg-rose-100 text-rose-800 border-rose-200 dark:bg-rose-900/40 dark:text-rose-200 dark:border-rose-700",
    info: "bg-cyan-100 text-cyan-800 border-cyan-200 dark:bg-cyan-900/40 dark:text-cyan-200 dark:border-cyan-700",
};

export default function Badge({ children, variant = "default", dot = false, className = "" }) {
    return (
        <span
            className={`inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold border ${VARIANTS[variant] || VARIANTS.default} ${className}`}
        >
            {dot && <span className="w-1.5 h-1.5 rounded-full bg-current" />}
            {children}
        </span>
    );
}
