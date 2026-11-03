import React, { useEffect, useState } from "react";

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

    const color = state === "waiting" ? "bg-blue-50 border-blue-200 text-blue-700"
        : state === "active" ? "bg-emerald-50 border-emerald-200 text-emerald-700"
        : "bg-slate-100 border-slate-200 text-slate-600";

    const label = state === "waiting" ? "Pendaftaran dibuka dalam:" : state === "active" ? "Sisa waktu pengajuan:" : "Sistem telah ditutup";

    return (
        <div className={`border rounded-xl p-4 ${color}`}>
            <div className="flex items-center justify-between flex-wrap gap-2">
                <span className="font-semibold text-sm">{label}</span>
                <span className="font-mono font-bold text-lg">{d}h {pad(h)}:{pad(m)}:{pad(sec)}</span>
            </div>
        </div>
    );
}