import React from "react";
import { createRoot } from "react-dom/client";

function ModernBanner() {
    return (
        <div className="rounded-xl overflow-hidden shadow-md mb-4 border border-[#1e3a6e]/20">
            <div className="bg-gradient-to-r from-[#11244e] via-[#1e3a6e] to-[#254b8c] text-white px-5 py-4 flex items-center justify-between">
                <div>
                    <div className="text-[10px] font-black uppercase tracking-widest text-blue-200">Tema Modern · React + Tailwind</div>
                    <h2 className="text-lg font-black tracking-tight mt-0.5">Modern Mode Aktif</h2>
                    <p className="text-[11px] text-blue-100 font-medium mt-1">Layout & komponen modern diadopsi dari prototype. Konten di bawah tetap berjalan (Blade/Livewire).</p>
                </div>
                <div className="hidden md:flex gap-2">
                    <span className="bg-white/15 text-white text-[10px] font-bold px-2.5 py-1 rounded-full">React 19</span>
                    <span className="bg-white/15 text-white text-[10px] font-bold px-2.5 py-1 rounded-full">Tailwind 4</span>
                    <span className="bg-emerald-400/30 text-emerald-100 border border-emerald-400/40 text-[10px] font-bold px-2.5 py-1 rounded-full">Vite 7</span>
                </div>
            </div>
        </div>
    );
}

const rootEl = document.getElementById("modern-root");
if (rootEl) {
    createRoot(rootEl).render(<ModernBanner />);
}