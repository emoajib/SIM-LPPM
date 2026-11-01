import React from "react";
import { createRoot } from "react-dom/client";
import ProposalsIndex from "./modern/ProposalsIndex.jsx";
import DashboardIndex from "./modern/DashboardIndex.jsx";
import PengabdianIndex from "./modern/PengabdianIndex.jsx";
import ResearchRevisionIndex from "./modern/ResearchRevisionIndex.jsx";
import ResearchFinalReportIndex from "./modern/ResearchFinalReportIndex.jsx";
import ResearchDailyNoteIndex from "./modern/ResearchDailyNoteIndex.jsx";

const PAGE = window.__MODERN_PAGE__ || "banner";

const PAGES = {
    "proposals-index": ProposalsIndex,
    "dashboard-index": DashboardIndex,
    "pengabdian-index": PengabdianIndex,
    "research-revision-index": ResearchRevisionIndex,
    "research-final-report-index": ResearchFinalReportIndex,
    "research-daily-note-index": ResearchDailyNoteIndex,
};

function App() {
    const Comp = PAGES[PAGE];
    if (Comp) return <Comp />;
    return null;
}

function mountModern() {
    const rootEl = document.getElementById("modern-root");
    if (!rootEl) return;
    // avoid double mount
    if (rootEl._reactRoot) return;
    const root = createRoot(rootEl);
    rootEl._reactRoot = root;
    root.render(<App />);
}

mountModern();
document.addEventListener("livewire:navigated", mountModern);