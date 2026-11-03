import React from "react";

// Vetted by AI - Manual Review Required by Senior Engineer/Manager
export default function EmptyState({ icon = "ti ti-inbox", title = "Tidak ada data", description = "", action = null }) {
    return (
        <div className="text-center py-12 px-4">
            <div className="text-5xl mb-3 opacity-60"><i className={icon} /></div>
            <h3 className="text-base font-semibold text-slate-800 dark:text-slate-100">{title}</h3>
            {description && <p className="text-sm text-slate-500 dark:text-slate-400 mt-1 max-w-md mx-auto">{description}</p>}
            {action && <div className="mt-4">{action}</div>}
        </div>
    );
}
