import React from "react";

// Vetted by AI - Manual Review Required by Senior Engineer/Manager
export default function ModernBanner() {
    return (
        <div className="rounded-xl overflow-hidden shadow-md mb-4 border border-[#1e3a6e]/20">
            <div className="bg-gradient-to-r from-[#11244e] via-[#1e3a6e] to-[#254b8c] text-white px-5 py-4">
                <div className="text-[10px] font-black uppercase tracking-widest text-blue-200 mb-0.5">
                    LPPM ITSNU Pekalongan · Mode Modern Aktif
                </div>
                <h2 className="text-lg font-black tracking-tight">Sistem Informasi Manajemen LPPM</h2>
                <p className="text-[11px] text-blue-100 font-medium mt-1">
                    Layout modern · Konten Blade/Livewire tetap berjalan di bawah.
                </p>
            </div>
        </div>
    );
}