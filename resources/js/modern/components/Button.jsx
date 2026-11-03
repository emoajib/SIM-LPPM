import React from "react";

const VARIANTS = {
    primary: "bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm",
    secondary: "bg-slate-100 text-slate-700 hover:bg-slate-200",
    ghost: "bg-transparent text-slate-600 hover:bg-slate-100",
    danger: "bg-red-600 text-white hover:bg-red-700",
    success: "bg-emerald-600 text-white hover:bg-emerald-700",
};

export default function Button({ children, variant = "primary", size = "md", disabled = false, className = "", ...props }) {
    const sizes = { sm: "px-3 py-1.5 text-xs", md: "px-4 py-2 text-sm", lg: "px-5 py-2.5 text-base" };
    return (
        <button
            disabled={disabled}
            className={`inline-flex items-center justify-center gap-2 rounded-lg font-semibold transition-all duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed ${VARIANTS[variant]} ${sizes[size]} ${className}`}
            {...props}
        >
            {children}
        </button>
    );
}
