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

const PAGE = window.__MODERN_PAGE__ || "banner";

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
};

function App() {
    const Comp = PAGES[PAGE];
    if (Comp) return <Comp />;
    return null;
}

function mountModern() {
    const rootEl = document.getElementById("modern-root");
    if (!rootEl) return;
    console.log("mountModern", window.__MODERN_PAGE__, rootEl);
    // avoid double mount on same element
    if (rootEl._reactRoot) {
        rootEl._reactRoot.render(<App />);
        return;
    }
    const root = createRoot(rootEl);
    rootEl._reactRoot = root;
    root.render(<App />);
}

mountModern();
document.addEventListener("livewire:navigated", () => {
    console.log("livewire:navigated fired", window.__MODERN_PAGE__);
    mountModern();
});