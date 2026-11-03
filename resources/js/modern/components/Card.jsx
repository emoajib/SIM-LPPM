import React from "react";

export default function Card({ children, className = "", hover = false }) {
    return (
        <div
            className={`bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-sm p-6 text-slate-800 dark:text-slate-100 ${hover ? "transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md" : ""} ${className}`}
        >
            {children}
        </div>
    );
}
