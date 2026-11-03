// Vetted by AI - Manual Review Required by Senior Engineer/Manager
import React, { useEffect, useRef } from "react";

export default function Modal({ open, onClose, title, children, size = "md", ariaLabelledBy = "modal-title" }) {
    const dialogRef = useRef(null);

    useEffect(() => {
        if (!open) return;
        const onKey = (e) => e.key === "Escape" && onClose();
        document.addEventListener("keydown", onKey);
        document.body.style.overflow = "hidden";

        // Accessible focus management
        if (dialogRef.current) {
            dialogRef.current.focus();
        }

        return () => {
            document.removeEventListener("keydown", onKey);
            document.body.style.overflow = "";
        };
    }, [open, onClose]);

    if (!open) return null;
    const widths = { sm: "max-w-sm", md: "max-w-md", lg: "max-w-lg", xl: "max-w-2xl" };

    return (
        <div
            className="fixed inset-0 z-50 flex items-center justify-center p-4"
            onClick={onClose}
            role="presentation"
        >
            <div className="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" />
            <div
                ref={dialogRef}
                tabIndex={-1}
                role="dialog"
                aria-modal="true"
                aria-labelledby={title ? ariaLabelledBy : undefined}
                className={`relative bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-2xl w-full ${widths[size]} p-6 animate-[fadeIn_0.15s_ease-out] text-slate-800 dark:text-slate-100 outline-none`}
                onClick={(e) => e.stopPropagation()}
            >
                {title && (
                    <div className="flex items-center justify-between pb-3 mb-4 border-b border-slate-100 dark:border-slate-700">
                        <h3 id={ariaLabelledBy} className="text-lg font-bold text-slate-900 dark:text-slate-100">
                            {title}
                        </h3>
                        <button
                            type="button"
                            onClick={onClose}
                            aria-label="Tutup dialog"
                            className="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg p-1 transition-colors"
                        >
                            <span className="text-xl leading-none">&times;</span>
                        </button>
                    </div>
                )}
                {children}
            </div>
        </div>
    );
}
