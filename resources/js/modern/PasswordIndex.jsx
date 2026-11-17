// Vetted by SOSIOMEN - Manual Review Required by Senior Engineer/Manager
import React, { useState, useMemo } from "react";
import Button from "./components/Button.jsx";
import ModernPageCard from "./components/ModernPageCard.jsx";
import SettingsHeaderNav from "./components/SettingsHeaderNav.jsx";

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? "";

export default function PasswordIndex() {
    const [form, setForm] = useState({
        current_password: "",
        password: "",
        password_confirmation: "",
    });
    const [showPassword, setShowPassword] = useState({
        current: false,
        new: false,
        confirm: false,
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

    // Evaluasi kekuatan sandi secara real-time
    const passwordStrength = useMemo(() => {
        const pwd = form.password;
        if (!pwd) return { score: 0, label: "Belum diisi", color: "bg-slate-200 dark:bg-slate-700", text: "text-slate-400" };

        let score = 0;
        if (pwd.length >= 8) score += 1;
        if (pwd.length >= 12) score += 1;
        if (/[A-Z]/.test(pwd) && /[a-z]/.test(pwd)) score += 1;
        if (/[0-9]/.test(pwd)) score += 1;
        if (/[^A-Za-z0-9]/.test(pwd)) score += 1;

        if (score <= 2) {
            return { score: 1, label: "Lemah", color: "bg-rose-500", text: "text-rose-600 dark:text-rose-400" };
        } else if (score <= 3) {
            return { score: 2, label: "Cukup", color: "bg-amber-500", text: "text-amber-600 dark:text-amber-400" };
        } else if (score === 4) {
            return { score: 3, label: "Baik", color: "bg-blue-500", text: "text-blue-600 dark:text-blue-400" };
        } else {
            return { score: 4, label: "Sangat Kuat", color: "bg-emerald-500", text: "text-emerald-600 dark:text-emerald-400" };
        }
    }, [form.password]);

    const isMatch = useMemo(() => {
        if (!form.password_confirmation) return null;
        return form.password === form.password_confirmation;
    }, [form.password, form.password_confirmation]);

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
        <div className="max-w-4xl mx-auto space-y-6">
            <SettingsHeaderNav
                title="Ubah Kata Sandi"
                subtitle="Perbarui kata sandi akun Anda secara berkala untuk menjaga kerahasiaan dan integritas data tri dharma perguruan tinggi."
                categoryBadge="Keamanan Akun"
                currentPath="/settings/password/modern"
            />

            {toast && (
                <div
                    role="status"
                    className={`px-4 py-3 rounded-xl border text-sm flex items-center gap-2.5 shadow-xs transition-all ${
                        toast.type === "success"
                            ? "bg-emerald-50 border-emerald-200 text-emerald-800 dark:bg-emerald-950/50 dark:border-emerald-900/50 dark:text-emerald-300"
                            : "bg-rose-50 border-rose-200 text-rose-800 dark:bg-rose-950/50 dark:border-rose-900/50 dark:text-rose-300"
                    }`}
                >
                    <i className={`text-base ${toast.type === "success" ? "ti ti-check" : "ti ti-alert-circle"}`}></i>
                    <span>{toast.message}</span>
                </div>
            )}

            {/* Two-Column Balanced Layout */}
            <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                {/* Kolom Formulir Utama (7 Kolom) */}
                <div className="lg:col-span-7">
                    <ModernPageCard>
                        <div className="flex items-center justify-between pb-3 mb-5 border-b border-slate-100 dark:border-slate-700/60">
                            <div className="flex items-center gap-2.5">
                                <div className="w-9 h-9 rounded-xl bg-[#1e3a6e]/10 dark:bg-cyan-950/60 text-[#1e3a6e] dark:text-cyan-400 flex items-center justify-center text-lg shadow-xs">
                                    <i className="ti ti-key"></i>
                                </div>
                                <div>
                                    <h2 className="text-base font-bold text-slate-800 dark:text-slate-100">
                                        Formulir Pembaruan Sandi
                                    </h2>
                                    <p className="text-xs text-slate-500 dark:text-slate-400">
                                        Masukkan sandi aktif untuk memvalidasi perubahan.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <form onSubmit={handleSubmit} className="space-y-4" noValidate>
                            {/* Kata Sandi Saat Ini */}
                            <div className="flex flex-col gap-1.5">
                                <label htmlFor="current_password" className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                                    Kata Sandi Saat Ini <span className="text-rose-500">*</span>
                                </label>
                                <div className="relative">
                                    <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                                        <i className="ti ti-lock text-base"></i>
                                    </div>
                                    <input
                                        id="current_password"
                                        name="current_password"
                                        type={showPassword.current ? "text" : "password"}
                                        autoComplete="current-password"
                                        value={form.current_password}
                                        onChange={setField("current_password")}
                                        aria-invalid={Boolean(errors.current_password)}
                                        aria-describedby={errors.current_password ? "err-current_password" : undefined}
                                        className="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl pl-10 pr-10 py-2.5 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e] dark:focus:ring-cyan-500 focus:bg-white dark:focus:bg-slate-900 transition-all font-mono"
                                        placeholder="••••••••••••"
                                        required
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setShowPassword((s) => ({ ...s, current: !s.current }))}
                                        className="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors"
                                        aria-label={showPassword.current ? "Sembunyikan sandi" : "Lihat sandi"}
                                    >
                                        <i className={`text-base ${showPassword.current ? "ti ti-eye-off" : "ti ti-eye"}`}></i>
                                    </button>
                                </div>
                                {errors.current_password && (
                                    <p id="err-current_password" className="text-xs text-rose-600 dark:text-rose-400 flex items-center gap-1 mt-0.5">
                                        <i className="ti ti-alert-circle text-xs"></i>
                                        {errors.current_password[0]}
                                    </p>
                                )}
                            </div>

                            {/* Kata Sandi Baru */}
                            <div className="flex flex-col gap-1.5 pt-1">
                                <div className="flex items-center justify-between">
                                    <label htmlFor="password" className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                                        Kata Sandi Baru <span className="text-rose-500">*</span>
                                    </label>
                                    {form.password && (
                                        <span className={`text-[11px] font-bold ${passwordStrength.text}`}>
                                            Kekuatan: {passwordStrength.label}
                                        </span>
                                    )}
                                </div>
                                <div className="relative">
                                    <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                                        <i className="ti ti-lock-access text-base"></i>
                                    </div>
                                    <input
                                        id="password"
                                        name="password"
                                        type={showPassword.new ? "text" : "password"}
                                        autoComplete="new-password"
                                        value={form.password}
                                        onChange={setField("password")}
                                        aria-invalid={Boolean(errors.password)}
                                        aria-describedby={errors.password ? "err-password" : "hint-password"}
                                        className="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl pl-10 pr-10 py-2.5 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e] dark:focus:ring-cyan-500 focus:bg-white dark:focus:bg-slate-900 transition-all font-mono"
                                        placeholder="••••••••••••"
                                        required
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setShowPassword((s) => ({ ...s, new: !s.new }))}
                                        className="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors"
                                        aria-label={showPassword.new ? "Sembunyikan sandi" : "Lihat sandi"}
                                    >
                                        <i className={`text-base ${showPassword.new ? "ti ti-eye-off" : "ti ti-eye"}`}></i>
                                    </button>
                                </div>

                                {/* Password Strength Meter Bar */}
                                {form.password && (
                                    <div className="flex items-center gap-1 mt-1">
                                        {[1, 2, 3, 4].map((step) => (
                                            <div
                                                key={step}
                                                className={`h-1.5 flex-1 rounded-full transition-all duration-300 ${
                                                    step <= passwordStrength.score
                                                        ? passwordStrength.color
                                                        : "bg-slate-200 dark:bg-slate-700"
                                                }`}
                                            ></div>
                                        ))}
                                    </div>
                                )}

                                {errors.password && (
                                    <p id="err-password" className="text-xs text-rose-600 dark:text-rose-400 flex items-center gap-1 mt-0.5">
                                        <i className="ti ti-alert-circle text-xs"></i>
                                        {errors.password[0]}
                                    </p>
                                )}
                            </div>

                            {/* Konfirmasi Kata Sandi Baru */}
                            <div className="flex flex-col gap-1.5 pt-1">
                                <div className="flex items-center justify-between">
                                    <label htmlFor="password_confirmation" className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                                        Ulangi Kata Sandi Baru <span className="text-rose-500">*</span>
                                    </label>
                                    {isMatch !== null && (
                                        <span className={`text-[11px] font-bold flex items-center gap-1 ${
                                            isMatch ? "text-emerald-600 dark:text-emerald-400" : "text-rose-600 dark:text-rose-400"
                                        }`}>
                                            <i className={`text-xs ${isMatch ? "ti ti-check" : "ti ti-x"}`}></i>
                                            {isMatch ? "Sandi cocok" : "Belum cocok"}
                                        </span>
                                    )}
                                </div>
                                <div className="relative">
                                    <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                                        <i className="ti ti-check text-base"></i>
                                    </div>
                                    <input
                                        id="password_confirmation"
                                        name="password_confirmation"
                                        type={showPassword.confirm ? "text" : "password"}
                                        autoComplete="new-password"
                                        value={form.password_confirmation}
                                        onChange={setField("password_confirmation")}
                                        aria-invalid={Boolean(errors.password_confirmation)}
                                        className="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl pl-10 pr-10 py-2.5 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e] dark:focus:ring-cyan-500 focus:bg-white dark:focus:bg-slate-900 transition-all font-mono"
                                        placeholder="••••••••••••"
                                        required
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setShowPassword((s) => ({ ...s, confirm: !s.confirm }))}
                                        className="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors"
                                        aria-label={showPassword.confirm ? "Sembunyikan sandi" : "Lihat sandi"}
                                    >
                                        <i className={`text-base ${showPassword.confirm ? "ti ti-eye-off" : "ti ti-eye"}`}></i>
                                    </button>
                                </div>
                                {errors.password_confirmation && (
                                    <p className="text-xs text-rose-600 dark:text-rose-400 flex items-center gap-1 mt-0.5">
                                        <i className="ti ti-alert-circle text-xs"></i>
                                        {errors.password_confirmation[0]}
                                    </p>
                                )}
                            </div>

                            {/* Tombol Simpan Aksi */}
                            <div className="pt-4 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-between">
                                <p className="text-xs text-slate-400 dark:text-slate-500 hidden sm:block">
                                    Sesi lain akan tetap aktif setelah kata sandi diubah.
                                </p>
                                <Button type="submit" variant="primary" size="md" disabled={saving}>
                                    <i className="ti ti-device-floppy mr-1.5"></i>
                                    {saving ? "Menyimpan…" : "Perbarui Kata Sandi"}
                                </Button>
                            </div>
                        </form>
                    </ModernPageCard>
                </div>

                {/* Kolom Informasi & Kebijakan Keamanan (5 Kolom) */}
                <div className="lg:col-span-5 space-y-4">
                    {/* Security Checklist Card */}
                    <div className="bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700/80 rounded-2xl p-5 shadow-xs">
                        <div className="flex items-center gap-2.5 pb-3 mb-3 border-b border-slate-100 dark:border-slate-700/60">
                            <div className="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-base">
                                <i className="ti ti-shield-check"></i>
                            </div>
                            <h3 className="text-sm font-bold text-slate-800 dark:text-slate-100">
                                Standar Keamanan Sandi
                            </h3>
                        </div>

                        <ul className="space-y-2.5 text-xs text-slate-600 dark:text-slate-300">
                            <li className="flex items-start gap-2">
                                <i className={`ti ${form.password.length >= 8 ? "ti-circle-check text-emerald-500" : "ti-circle text-slate-300 dark:text-slate-600"} text-sm shrink-0 mt-0.5`}></i>
                                <span>Minimal 8 karakter (disarankan 12 karakter atau lebih).</span>
                            </li>
                            <li className="flex items-start gap-2">
                                <i className={`ti ${/[A-Z]/.test(form.password) && /[a-z]/.test(form.password) ? "ti-circle-check text-emerald-500" : "ti-circle text-slate-300 dark:text-slate-600"} text-sm shrink-0 mt-0.5`}></i>
                                <span>Kombinasi huruf besar (A-Z) dan huruf kecil (a-z).</span>
                            </li>
                            <li className="flex items-start gap-2">
                                <i className={`ti ${/[0-9]/.test(form.password) ? "ti-circle-check text-emerald-500" : "ti-circle text-slate-300 dark:text-slate-600"} text-sm shrink-0 mt-0.5`}></i>
                                <span>Menyertakan sekurang-kurangnya satu angka (0-9).</span>
                            </li>
                            <li className="flex items-start gap-2">
                                <i className={`ti ${/[^A-Za-z0-9]/.test(form.password) ? "ti-circle-check text-emerald-500" : "ti-circle text-slate-300 dark:text-slate-600"} text-sm shrink-0 mt-0.5`}></i>
                                <span>Karakter khusus atau simbol simbolik (misal: !@#$%^&*).</span>
                            </li>
                        </ul>
                    </div>

                    {/* Security Advice Card */}
                    <div className="bg-slate-50 dark:bg-slate-800/60 border border-slate-200/70 dark:border-slate-700/70 rounded-2xl p-5 text-xs text-slate-600 dark:text-slate-300 space-y-3">
                        <div className="flex items-center gap-2 font-bold text-slate-800 dark:text-slate-200">
                            <i className="ti ti-lock-heart text-base text-[#1e3a6e] dark:text-cyan-400"></i>
                            <span>Pedoman Keamanan Siber LPPM</span>
                        </div>
                        <p className="leading-relaxed text-slate-500 dark:text-slate-400">
                            Hindari menggunakan informasi pribadi seperti tanggal lahir, nama instansi, atau kombinasi umum (seperti <code className="px-1 py-0.5 rounded bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 font-mono">12345678</code>). Disarankan untuk memperbarui kata sandi secara berkala setiap awal semester akademik.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    );
}
