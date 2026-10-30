import React from "react";
import { createRoot } from "react-dom/client";
import ModernBanner from "./modern/ModernBanner.jsx";
import ProposalsIndex from "./modern/ProposalsIndex.jsx";

const PAGE = window.__MODERN_PAGE__ || "banner";

const PAGES = {
    "proposals-index": ProposalsIndex,
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