import React, { useEffect, useState } from "react";

// Vetted by AI - Manual Review Required by Senior Engineer/Manager
export default function CountdownTimer({ start, end }) {
    const [now, setNow] = useState(new Date());
    useEffect(() => {
        const t = setInterval(() => setNow(new Date()), 1000);
        return () => clearInterval(t);
    }, []);

    if (!start || !end) return null;
    const s = new Date(start);
    const e = new Date(end);
    let state = "closed";
    if (now < s) state = "waiting";
    else if (now <= e) state = "active";
    else state = "closed";

    const target = state === "waiting" ? s : e;
    const diff = Math.max(0, target - now);
    const d = Math.floor(diff / 86400000);
    const h = Math.floor((diff % 86400000) / 3600000);
    const m = Math.floor((diff % 3600000) / 60000);
    const sec = Math.floor((diff % 60000) / 1000);
    const pad = (n) => String(n).padStart(2, "0");

    const themes = {
        waiting: {
            wrapper: "bg-blue-50 border-blue-200 dark:bg-blue-950/40 dark:border-blue-800",
            dot:     "bg-blue-400",
            label:   "text-blue-700 dark:text-blue-300",
            box:     "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200",
        },
        active: {
            wrapper: "bg-emerald-50 border-emerald-200 dark:bg-emerald-950/40 dark:border-emerald-800",
            dot:     "bg-emerald-400 animate-pulse",
            label:   "text-emerald-700 dark:text-emerald-300",
            box:     "bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200",
        },
        closed: {
            wrapper: "bg-slate-100 border-slate-200 dark:bg-slate-800 dark:border-slate-700",
            dot:     "bg-slate-400",
            label:   "text-slate-600 dark:text-slate-400",
            box:     "bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300",
        },
    };
    const t = themes[state];

    const label = state === "waiting"
        ? "Pendaftaran dibuka dalam:"
        : state === "active"
        ? "Sisa waktu pengajuan:"
        : "Periode pendaftaran telah berakhir";

    const units = [
        { val: d, label: "Hari" },
        { val: h, label: "Jam" },
        { val: m, label: "Menit" },
        { val: sec, label: "Detik" },
    ];

    return (
        <div className={`border rounded-xl p-4 ${t.wrapper}`}>
            <div className="flex items-center gap-2 mb-3">
                <span className={`w-2 h-2 rounded-full ${t.dot}`} />
                <span className={`font-semibold text-sm ${t.label}`}>{label}</span>
            </div>
            {state !== "closed" && (
                <div className="flex gap-2">
                    {units.map(({ val, label: ul }) => (
                        <div key={ul} className={`flex-1 rounded-lg p-2 text-center ${t.box}`}>
                            <div className="text-xl font-black font-mono leading-none">{pad(val)}</div>
                            <div className="text-[10px] font-semibold uppercase tracking-wider mt-0.5 opacity-75">{ul}</div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}