import React from "react";

// Vetted by AI - Manual Review Required by Senior Engineer/Manager
// Presets: lines (default), avatar, card, table-row
const PRESETS = {
    "avatar": "h-10 w-10 rounded-full",
    "card":   "h-32 w-full rounded-xl",
    "table-row": "h-10 w-full",
};

export default function Skeleton({ className = "h-4 w-full", lines = 1, preset = null }) {
    const cls = preset ? (PRESETS[preset] ?? className) : className;
    return (
        <div className="space-y-2">
            {Array.from({ length: lines }).map((_, i) => (
                <div key={i} className={`skeleton-shimmer rounded-lg ${cls}`} />
            ))}
        </div>
    );
}
