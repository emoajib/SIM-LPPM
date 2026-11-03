import React from "react";

export default function Pagination({ meta, onPage }) {
    if (!meta || meta.last_page <= 1) return null;
    const pages = [];
    const cur = meta.current_page;
    const last = meta.last_page;
    const push = (p) => pages.push(p);
    push(1);
    if (cur > 3) push("...");
    for (let p = Math.max(2, cur - 1); p <= Math.min(last - 1, cur + 1); p++) push(p);
    if (cur < last - 2) push("...");
    if (last > 1) push(last);

    return (
        <div className="mt-4 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm text-slate-500">
            <div>Halaman {cur} dari {last} · Total {meta.total}</div>
            <div className="flex items-center gap-1">
                <button
                    disabled={cur <= 1}
                    onClick={() => onPage(cur - 1)}
                    className="px-3 py-1.5 rounded-lg border border-slate-300 disabled:opacity-40 hover:bg-slate-50 transition"
                >
                    Prev
                </button>
                {pages.map((p, i) =>
                    p === "..." ? (
                        <span key={`e${i}`} className="px-2">…</span>
                    ) : (
                        <button
                            key={p}
                            onClick={() => onPage(p)}
                            className={`px-3 py-1.5 rounded-lg border transition ${p === cur ? "bg-indigo-600 text-white border-indigo-600" : "border-slate-300 hover:bg-slate-50"}`}
                        >
                            {p}
                        </button>
                    )
                )}
                <button
                    disabled={cur >= last}
                    onClick={() => onPage(cur + 1)}
                    className="px-3 py-1.5 rounded-lg border border-slate-300 disabled:opacity-40 hover:bg-slate-50 transition"
                >
                    Next
                </button>
            </div>
        </div>
    );
}
