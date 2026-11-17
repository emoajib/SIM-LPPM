// Vetted by SOSIOMEN - Manual Review Required by Senior Engineer/Manager
import React, { useState } from "react";
import Button from "./components/Button.jsx";
import ModernPageCard from "./components/ModernPageCard.jsx";

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? "";

export default function PasswordIndex() {
    const [form, setForm] = useState({
        current_password: "",
        password: "",
        password_confirmation: "",
    });
    const [errors, setErrors] = useState({});
    const [saving, setSaving] = useState(false);
    const [toast, setToast] = useState(null);

    const showToast = (type, message) => {
        setToast({ type, message });
        setTimeout(() => setToast(null), 4000);
    };

    const setField = (key) => (e) => {
        setForm((f) => ({ ...f, [key]: e.target.value }));
        setErrors((prev) => {
            if (!prev[key]) return prev;
            const next = { ...prev };
            delete next[key];
            return next;
        });
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSaving(true);
        setErrors({});

        try {
            const res = await fetch("/api/v1/settings/password", {
                method: "PUT",
                credentials: "same-origin",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrf(),
                },
                body: JSON.stringify(form),
            });

            if (res.status === 422) {
                const data = await res.json();
                setErrors(data.errors || {});
                showToast("error", data.message || "Periksa kembali isian Anda.");
                return;
            }

            if (!res.ok) throw new Error("Gagal memperbarui kata sandi.");

            const data = await res.json();
            setForm({ current_password: "", password: "", password_confirmation: "" });
            showToast("success", data.message || "Kata sandi berhasil diperbarui.");
        } catch (err) {
            showToast("error", err.message);
        } finally {
            setSaving(false);
        }
    };

    return (
        <div className="space-y-4">
            {toast && (
                <div
                    role="status"
                    className={`px-4 py-3 rounded-lg border text-sm ${
                        toast.type === "success"
                            ? "bg-emerald-50 border-emerald-200 text-emerald-800 dark:bg-emerald-950/50 dark:border-emerald-900/50 dark:text-emerald-300"
                            : "bg-rose-50 border-rose-200 text-rose-800 dark:bg-rose-950/50 dark:border-rose-900/50 dark:text-rose-300"
                    }`}
                >
                    {toast.type === "success" ? "✓" : "✕"} {toast.message}
                </div>
            )}

            <nav aria-label="Breadcrumb" className="text-xs text-slate-500 dark:text-slate-400">
                <a href="/dashboard" className="hover:underline">Dashboard</a>
                <span className="mx-1.5 opacity-60">/</span>
                <a href="/settings/modern" className="hover:underline">Pengaturan</a>
                <span className="mx-1.5 opacity-60">/</span>
                <span className="font-semibold text-slate-700 dark:text-slate-200">Ubah Kata Sandi</span>
            </nav>

            <ModernPageCard>
                <div className="mb-4">
                    <h2 className="text-xl font-bold text-slate-800 dark:text-slate-100 mb-1">Ubah Kata Sandi</h2>
                    <p className="text-slate-500 dark:text-slate-400">
                        Gunakan kata sandi yang panjang dan acak agar akun tetap aman.
                    </p>
                </div>

                <form onSubmit={handleSubmit} className="space-y-4 max-w-xl" noValidate>
                    <div className="flex flex-col gap-1">
                        <label htmlFor="current_password" className="text-sm font-medium text-slate-700 dark:text-slate-200">
                            Kata Sandi Saat Ini
                        </label>
                        <input
                            id="current_password"
                            name="current_password"
                            type="password"
                            autoComplete="current-password"
                            value={form.current_password}
                            onChange={setField("current_password")}
                            aria-invalid={Boolean(errors.current_password)}
                            aria-describedby={errors.current_password ? "err-current_password" : undefined}
                            className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm"
                        />
                        {errors.current_password && (
                            <p id="err-current_password" className="text-xs text-rose-600 dark:text-rose-400">
                                {errors.current_password[0]}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1">
                        <label htmlFor="password" className="text-sm font-medium text-slate-700 dark:text-slate-200">
                            Kata Sandi Baru
                        </label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            autoComplete="new-password"
                            value={form.password}
                            onChange={setField("password")}
                            aria-invalid={Boolean(errors.password)}
                            aria-describedby={errors.password ? "err-password" : "hint-password"}
                            className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm"
                        />
                        <p id="hint-password" className="text-xs text-slate-500 dark:text-slate-400">
                            Minimal 8 karakter, kombinasi huruf besar/kecil dan angka.
                        </p>
                        {errors.password && (
                            <p id="err-password" className="text-xs text-rose-600 dark:text-rose-400">
                                {errors.password[0]}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1">
                        <label htmlFor="password_confirmation" className="text-sm font-medium text-slate-700 dark:text-slate-200">
                            Konfirmasi Kata Sandi Baru
                        </label>
                        <input
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            autoComplete="new-password"
                            value={form.password_confirmation}
                            onChange={setField("password_confirmation")}
                            aria-invalid={Boolean(errors.password_confirmation)}
                            className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm"
                        />
                        {errors.password_confirmation && (
                            <p className="text-xs text-rose-600 dark:text-rose-400">
                                {errors.password_confirmation[0]}
                            </p>
                        )}
                    </div>

                    <div className="flex justify-end">
                        <Button type="submit" variant="primary" size="md" disabled={saving}>
                            {saving ? "Menyimpan…" : "Simpan Kata Sandi"}
                        </Button>
                    </div>
                </form>
            </ModernPageCard>
        </div>
    );
}
