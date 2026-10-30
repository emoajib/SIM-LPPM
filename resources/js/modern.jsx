import React from "react";
import { createRoot } from "react-dom/client";
import ModernBanner from "./modern/ModernBanner.jsx";
import ProposalsIndex from "./modern/ProposalsIndex.jsx";
import DashboardIndex from "./modern/DashboardIndex.jsx";
import PengabdianIndex from "./modern/PengabdianIndex.jsx";

const PAGE = window.__MODERN_PAGE__ || "banner";

const PAGES = {
    "proposals-index": ProposalsIndex,
    "dashboard-index": DashboardIndex,
    "pengabdian-index": PengabdianIndex,
};

function App() {
    const Comp = PAGES[PAGE];
    if (Comp) return <Comp />;
    return <ModernBanner />;
}

const rootEl = document.getElementById("modern-root");
if (rootEl) {
    createRoot(rootEl).render(<App />);
}