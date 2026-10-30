import React from "react";
import { createRoot } from "react-dom/client";

function ModernApp() {
    return (
        <div className="min-h-screen bg-slate-50 text-slate-800 p-6">
            <h1 className="text-2xl font-bold text-[#1e3a6e]">Modern Theme (React + Tailwind)</h1>
            <p className="text-sm text-slate-500 mt-2">
                Shell modern aktif. Migrasi modul bertahap.
            </p>
            <div className="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div className="bg-white border border-slate-200 rounded-xl shadow-sm p-4">
                    <div className="text-xs font-bold text-slate-500 uppercase">Penelitian</div>
                    <div className="text-2xl font-black text-[#1e3a6e] mt-1">—</div>
                </div>
                <div className="bg-white border border-slate-200 rounded-xl shadow-sm p-4">
                    <div className="text-xs font-bold text-slate-500 uppercase">Pengabdian</div>
                    <div className="text-2xl font-black text-[#1e3a6e] mt-1">—</div>
                </div>
                <div className="bg-white border border-slate-200 rounded-xl shadow-sm p-4">
                    <div className="text-xs font-bold text-slate-500 uppercase">Laporan</div>
                    <div className="text-2xl font-black text-[#1e3a6e] mt-1">—</div>
                </div>
            </div>
        </div>
    );
}

const rootEl = document.getElementById("modern-root");
if (rootEl) {
    createRoot(rootEl).render(<ModernApp />);
}