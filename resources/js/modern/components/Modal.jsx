import React, { useEffect } from "react";

export default function Modal({ open, onClose, title, children, size = "md" }) {
    useEffect(() => {
        if (!open) return;
        const onKey = (e) => e.key === "Escape" && onClose();
        document.addEventListener("keydown", onKey);
        document.body.style.overflow = "hidden";
        return () => {
            document.removeEventListener("keydown", onKey);
            document.body.style.overflow = "";
        };
    }, [open, onClose]);

    if (!open) return null;
    const widths = { sm: "max-w-sm", md: "max-w-md", lg: "max-w-lg", xl: "max-w-2xl" };
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4" onClick={onClose}>
            <div className="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" />
            <div
                className={`relative bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full ${widths[size]} p-6 animate-[fadeIn_0.15s_ease-out] text-slate-800 dark:text-slate-100`}
                onClick={(e) => e.stopPropagation()}
            >
                {title && <h3 className="text-lg font-semibold text-slate-800 dark:text-slate-100 mb-4">{title}</h3>}
                {children}
            </div>
        </div>
    );
}
