// Vetted by AI - Manual Review Required by Senior Engineer/Manager
import React from "react";
import Skeleton from "./Skeleton.jsx";
import EmptyState from "./EmptyState.jsx";

/**
 * Reusable Modern Theme Table with built-in accessibility, responsive wrapper,
 * loading state, empty state, and dark mode styling.
 */
export default function ModernTable({
    columns = [],
    data = [],
    loading = false,
    emptyIcon = "ti ti-folder-open",
    emptyTitle = "Tidak ada data",
    emptyDescription = "Belum ada item yang dapat ditampilkan.",
    emptyAction = null,
    keyField = "id",
    rowClassName = "",
    ariaLabel = "Tabel Data",
}) {
    if (loading) {
        return (
            <div className="py-2">
                <Skeleton lines={6} preset="table-row" className="h-10 my-1.5" />
            </div>
        );
    }

    if (!data || data.length === 0) {
        return (
            <EmptyState
                icon={emptyIcon}
                title={emptyTitle}
                description={emptyDescription}
                action={emptyAction}
            />
        );
    }

    return (
        <div className="overflow-x-auto rounded-lg border border-slate-200/80 dark:border-slate-700">
            <table className="w-full text-left text-sm" role="table" aria-label={ariaLabel}>
                <thead className="bg-slate-50 dark:bg-slate-750/70 text-slate-600 dark:text-slate-300 text-xs uppercase tracking-wider sticky top-0 z-10 border-b border-slate-200 dark:border-slate-700">
                    <tr>
                        {columns.map((col, idx) => (
                            <th
                                key={col.key || idx}
                                scope="col"
                                className={`px-3.5 py-3 font-bold ${col.align === "right" ? "text-right" : col.align === "center" ? "text-center" : "text-left"} ${col.className || ""}`}
                            >
                                {col.label}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody className="divide-y divide-slate-100 dark:divide-slate-700/60 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200">
                    {data.map((item, rowIdx) => (
                        <tr
                            key={item[keyField] ?? rowIdx}
                            className={`group hover:bg-slate-50/80 dark:hover:bg-slate-700/40 transition-colors duration-150 ${rowClassName}`}
                        >
                            {columns.map((col, colIdx) => (
                                <td
                                    key={col.key || colIdx}
                                    className={`px-3.5 py-3 align-middle text-sm ${col.align === "right" ? "text-right" : col.align === "center" ? "text-center" : "text-left"} ${col.cellClassName || ""}`}
                                >
                                    {col.render ? col.render(item, rowIdx) : item[col.key]}
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

