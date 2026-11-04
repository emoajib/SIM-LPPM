import "../css/modern.css";
import React from "react";
import { createRoot } from "react-dom/client";
import ProposalsIndex from "./modern/ProposalsIndex.jsx";
import DashboardIndex from "./modern/DashboardIndex.jsx";
import PengabdianIndex from "./modern/PengabdianIndex.jsx";
import ResearchRevisionIndex from "./modern/ResearchRevisionIndex.jsx";
import ResearchFinalReportIndex from "./modern/ResearchFinalReportIndex.jsx";
import ResearchDailyNoteIndex from "./modern/ResearchDailyNoteIndex.jsx";
import PengabdianRevisionIndex from "./modern/PengabdianRevisionIndex.jsx";
import PengabdianFinalReportIndex from "./modern/PengabdianFinalReportIndex.jsx";
import PengabdianDailyNoteIndex from "./modern/PengabdianDailyNoteIndex.jsx";
import VerifikasiLuaranIndex from "./modern/VerifikasiLuaranIndex.jsx";
import DailyNoteIndex from "./modern/DailyNoteIndex.jsx";

const PAGES = {
    "proposals-index": ProposalsIndex,
    "dashboard-index": DashboardIndex,
    "pengabdian-index": PengabdianIndex,
    "research-revision-index": ResearchRevisionIndex,
    "research-final-report-index": ResearchFinalReportIndex,
    "research-daily-note-index": ResearchDailyNoteIndex,
    "pengabdian-revision-index": PengabdianRevisionIndex,
    "pengabdian-final-report-index": PengabdianFinalReportIndex,
    "pengabdian-daily-note-index": PengabdianDailyNoteIndex,
    "verifikasi-luaran-index": VerifikasiLuaranIndex,
    "daily-note-index": DailyNoteIndex,
};

function mount() {
    const PAGE = window.__MODERN_PAGE__ || "banner";
    const rootEl = document.getElementById("modern-root");
    if (!rootEl) return;
    const Comp = PAGES[PAGE];
    if (!Comp) {
        rootEl.innerHTML = "<div style='padding:2rem;text-align:center;color:#64748b'>Halaman modern tidak tersedia: " + PAGE + "</div>";
        return;
    }
    createRoot(rootEl).render(React.createElement(Comp));
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", mount);
} else {
    mount();
}