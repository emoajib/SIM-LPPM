import React from "react";

export default function Skeleton({ className = "h-4 w-full", lines = 1 }) {
    return (
        <div className="space-y-2">
            {Array.from({ length: lines }).map((_, i) => (
                <div key={i} className={`skeleton-shimmer rounded-lg ${className}`} />
            ))}
        </div>
    );
}
