import React from "react";
import { createRoot } from "react-dom/client";
import ProposalsIndex from "./modern/ProposalsIndex.jsx";
import DashboardIndex from "./modern/DashboardIndex.jsx";
import PengabdianIndex from "./modern/PengabdianIndex.jsx";
import ResearchRevisionIndex from "./modern/ResearchRevisionIndex.jsx";

const PAGE = window.__MODERN_PAGE__ || "banner";

const PAGES = {
    "proposals-index": ProposalsIndex,
    "dashboard-index": DashboardIndex,
    "pengabdian-index": PengabdianIndex,
    "research-revision-index": ResearchRevisionIndex,
};

function App() {
    const Comp = PAGES[PAGE];
    if (Comp) return <Comp />;
    return null;
}

const rootEl = document.getElementById("modern-root");
if (rootEl) {
    createRoot(rootEl).render(<App />);
}