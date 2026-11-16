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
import ProposalShowIndex from "./modern/ProposalShowIndex.jsx";

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
    "proposal-show-index": ProposalShowIndex,
};

class ErrorBoundary extends React.Component {
    constructor(props) {
        super(props);
        this.state = { error: null };
    }
    static getDerivedStateFromError(error) {
        return { error };
    }
    componentDidCatch(error, info) {
        // eslint-disable-next-line no-console
        console.error("Modern page crashed:", error, info);
    }
    render() {
        if (this.state.error) {
            return React.createElement(
                "div",
                {
                    className:
                        "mx-auto max-w-lg rounded-xl border border-rose-200 bg-rose-50 p-6 text-center text-rose-800 dark:border-rose-900/50 dark:bg-rose-950/50 dark:text-rose-200",
                },
                React.createElement("h2", { className: "mb-2 text-lg font-bold" }, "Halaman gagal dimuat"),
                React.createElement(
                    "p",
                    { className: "mb-4 text-sm" },
                    String(this.state.error?.message || this.state.error || "Terjadi kesalahan tak terduga.")
                ),
                React.createElement(
                    "button",
                    {
                        type: "button",
                        onClick: () => window.location.reload(),
                        className:
                            "rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700",
                    },
                    "Muat ulang"
                )
            );
        }
        return this.props.children;
    }
}

function mount() {
    const PAGE = window.__MODERN_PAGE__ || "banner";
    const rootEl = document.getElementById("modern-root");
    if (!rootEl) return;
    const Comp = PAGES[PAGE];
    if (!Comp) {
        rootEl.innerHTML = "<div style='padding:2rem;text-align:center;color:#64748b'>Halaman modern tidak tersedia: " + PAGE + "</div>";
        return;
    }
    createRoot(rootEl).render(React.createElement(ErrorBoundary, null, React.createElement(Comp)));
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", mount);
} else {
    mount();
}