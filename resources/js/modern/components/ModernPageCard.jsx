// Vetted by AI - Manual Review Required by Senior Engineer/Manager
import React from "react";

/**
 * Standard content container card for Modern Theme pages
 * Supports consistent light/dark mode, entrance animation, and border styling
 */
export default function ModernPageCard({ children, className = "", noPadding = false, header = null, footer = null }) {
    return (
        <div
            className={`bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700/80 rounded-xl shadow-sm transition-colors duration-200 modern-tab-content ${className}`}
        >
            {header && (
                <div className="px-5 py-4 border-b border-slate-100 dark:border-slate-700/70 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    {header}
                </div>
            )}
            <div className={noPadding ? "" : "p-4 sm:p-5"}>
                {children}
            </div>
            {footer && (
                <div className="px-5 py-3.5 bg-slate-50/50 dark:bg-slate-800/80 border-t border-slate-100 dark:border-slate-700/70 rounded-b-xl">
                    {footer}
                </div>
            )}
        </div>
    );
}

