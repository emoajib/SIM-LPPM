// Vetted by SOSIOMEN - Manual Review Required by Senior Engineer/Manager
import React, { useEffect, useState } from "react";
import Button from "./components/Button.jsx";
import ModernPageCard from "./components/ModernPageCard.jsx";
import SettingsHeaderNav from "./components/SettingsHeaderNav.jsx";
import Skeleton from "./components/Skeleton.jsx";

export default function NotificationConfigIndex() {
    const [smtp, setSmtp] = useState({
        host: "",
        port: "587",
        username: "",
        password: "",
        encryption: "tls",
        from_address: "",
        from_name: "",
    });
    const [whatsapp, setWhatsapp] = useState({
        api_url: "",
        api_key: "",
        sender_id: "",
    });
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [testing, setTesting] = useState({ email: false, whatsapp: false });
    const [toast, setToast] = useState(null);
    const [testEmail, setTestEmail] = useState("");
    const [testPhone, setTestPhone] = useState("");

    const showToast = (type, message) => {
        setToast({ type, message });
        setTimeout(() => setToast(null), 4500);
    };

    const load = async () => {
        setLoading(true);
        try {
            const res = await fetch("/api/v1/settings/notification", {
                credentials: "same-origin",
                cache: "no-store",
            });
            if (!res.ok) throw new Error("Gagal memuat konfigurasi notifikasi.");
            const data = await res.json();
            setSmtp(data.smtp || {});
            setWhatsapp(data.whatsapp || {});
        } catch (e) {
            showToast("error", e.message);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        load();
    }, []);

    const handleSave = async () => {
        setSaving(true);
        try {
            const res = await fetch("/api/v1/settings/notification", {
                method: "PUT",
                credentials: "same-origin",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content ?? "",
                },
                body: JSON.stringify({ smtp, whatsapp }),
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || "Gagal menyimpan konfigurasi");
            showToast("success", "Konfigurasi notifikasi berhasil disimpan.");
            load();
        } catch (e) {
            showToast("error", e.message);
        } finally {
            setSaving(false);
        }
    };

    const handleTestEmail = async () => {
        if (!testEmail) return showToast("error", "Silakan masukkan alamat email tujuan uji coba.");
        setTesting((e) => ({ ...e, email: true }));
        try {
            const res = await fetch("/api/v1/settings/notification/test-email", {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content ?? "",
                },
                body: JSON.stringify({ email: testEmail }),
            });
            const data = await res.json();
            showToast(data.success ? "success" : "error", data.message || "Test email selesai.");
        } catch (e) {
            showToast("error", e.message);
        } finally {
            setTesting((e) => ({ ...e, email: false }));
        }
    };

    const handleTestWhatsApp = async () => {
        if (!testPhone) return showToast("error", "Silakan masukkan nomor WhatsApp tujuan uji coba.");
        setTesting((e) => ({ ...e, whatsapp: true }));
        try {
            const res = await fetch("/api/v1/settings/notification/test-whatsapp", {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content ?? "",
                },
                body: JSON.stringify({ phone: testPhone }),
            });
            const data = await res.json();
            showToast(data.success ? "success" : "error", data.message || "Test WhatsApp selesai.");
        } catch (e) {
            showToast("error", e.message);
        } finally {
            setTesting((e) => ({ ...e, whatsapp: false }));
        }
    };

    if (loading) {
        return (
            <div className="max-w-4xl mx-auto space-y-6">
                <SettingsHeaderNav
                    title="Konfigurasi Notifikasi"
                    subtitle="Kelola server SMTP email dan gateway WhatsApp untuk pengiriman notifikasi otomatis aplikasi."
                    categoryBadge="Integrasi & Notifikasi"
                    currentPath="/settings/notification-config/modern"
                />
                <div className="grid grid-cols-1 gap-6">
                    <Skeleton className="h-72 w-full rounded-2xl" />
                    <Skeleton className="h-64 w-full rounded-2xl" />
                </div>
            </div>
        );
    }

    return (
        <div className="max-w-4xl mx-auto space-y-6">
            <SettingsHeaderNav
                title="Konfigurasi Notifikasi"
                subtitle="Kelola server SMTP email dan gateway WhatsApp untuk pengiriman notifikasi otomatis aplikasi."
                categoryBadge="Integrasi & Notifikasi"
                currentPath="/settings/notification-config/modern"
            />

            {toast && (
                <div
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

            {/* Kartu 1: Server SMTP Email */}
            <ModernPageCard>
                <div className="flex items-center justify-between pb-3 mb-4 border-b border-slate-100 dark:border-slate-700/60">
                    <div className="flex items-center gap-2.5">
                        <div className="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-[#1e3a6e] dark:text-cyan-400 flex items-center justify-center text-xl shadow-xs">
                            <i className="ti ti-mail"></i>
                        </div>
                        <div>
                            <h2 className="text-base font-bold text-slate-800 dark:text-slate-100">Server SMTP (Email)</h2>
                            <p className="text-xs text-slate-500 dark:text-slate-400">Pengiriman surel persetujuan, revisi berkas, dan pengingat tenggat waktu.</p>
                        </div>
                    </div>
                    <span className="px-2.5 py-1 rounded-full text-[11px] font-semibold bg-blue-50 dark:bg-blue-950/60 text-[#1e3a6e] dark:text-cyan-300 border border-blue-200/60 dark:border-blue-800/60 hidden sm:inline-block">
                        Protokol SMTP
                    </span>
                </div>

                {/* Info Note */}
                <div className="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/70 dark:border-slate-700/70 text-xs text-slate-600 dark:text-slate-300 mb-5 flex items-start gap-2.5">
                    <i className="ti ti-info-circle text-base text-[#1e3a6e] dark:text-cyan-400 shrink-0 mt-0.5"></i>
                    <p className="leading-relaxed">
                        Gunakan server SMTP kampus (misal: <code className="px-1.5 py-0.5 rounded bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 font-mono">mail.itsnu.ac.id</code>, port 465/SSL atau 587/TLS) atau Google Workspace dengan App Password aktif.
                    </p>
                </div>

                {/* Grid Fields SMTP */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                    <div className="flex flex-col gap-1.5 sm:col-span-2">
                        <label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                            SMTP Host
                        </label>
                        <input
                            type="text"
                            value={smtp.host}
                            onChange={(e) => setSmtp({ ...smtp, host: e.target.value })}
                            placeholder="Contoh: smtp.gmail.com atau mail.itsnu.ac.id"
                            className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e] dark:focus:ring-cyan-500 font-mono"
                        />
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                            Port & Enkripsi
                        </label>
                        <div className="grid grid-cols-2 gap-2">
                            <input
                                type="number"
                                value={smtp.port}
                                onChange={(e) => setSmtp({ ...smtp, port: e.target.value })}
                                placeholder="587"
                                className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2.5 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e] dark:focus:ring-cyan-500 font-mono text-center"
                            />
                            <select
                                value={smtp.encryption}
                                onChange={(e) => setSmtp({ ...smtp, encryption: e.target.value })}
                                className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-2 py-2.5 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e] dark:focus:ring-cyan-500 uppercase font-bold"
                            >
                                <option value="tls">TLS</option>
                                <option value="ssl">SSL</option>
                                <option value="none">None</option>
                            </select>
                        </div>
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                            Username Akun Email
                        </label>
                        <input
                            type="email"
                            value={smtp.username}
                            onChange={(e) => setSmtp({ ...smtp, username: e.target.value })}
                            placeholder="noreply@itsnu.ac.id"
                            className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e] dark:focus:ring-cyan-500 font-mono"
                        />
                    </div>

                    <div className="flex flex-col gap-1.5 sm:col-span-2">
                        <label className="text-xs font-semibold text-slate-700 dark:text-slate-300 flex items-center justify-between">
                            <span>Kata Sandi / App Password</span>
                            <span className="text-[10px] text-slate-400 font-normal">Kosongkan jika tidak diubah</span>
                        </label>
                        <input
                            type="password"
                            value={smtp.password}
                            onChange={(e) => setSmtp({ ...smtp, password: e.target.value })}
                            placeholder="••••••••••••••••"
                            className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e] dark:focus:ring-cyan-500 font-mono"
                        />
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                            From Address (Email Pengirim)
                        </label>
                        <input
                            type="email"
                            value={smtp.from_address}
                            onChange={(e) => setSmtp({ ...smtp, from_address: e.target.value })}
                            placeholder="lppm@itsnu.ac.id"
                            className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e] dark:focus:ring-cyan-500 font-mono"
                        />
                    </div>

                    <div className="flex flex-col gap-1.5 sm:col-span-2">
                        <label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                            From Name (Nama Tampilan Pengirim)
                        </label>
                        <input
                            type="text"
                            value={smtp.from_name}
                            onChange={(e) => setSmtp({ ...smtp, from_name: e.target.value })}
                            placeholder="SIM-LPPM ITSNU Pekalongan"
                            className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e] dark:focus:ring-cyan-500"
                        />
                    </div>
                </div>

                {/* Live Test Email Bar */}
                <div className="p-4 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h4 className="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                            <i className="ti ti-send text-emerald-600 dark:text-emerald-400"></i>
                            Uji Coba Pengiriman Surel
                        </h4>
                        <p className="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                            Simulasi pengiriman pesan uji ke kotak masuk Anda.
                        </p>
                    </div>

                    <div className="flex items-center gap-2 w-full sm:w-auto">
                        <input
                            type="email"
                            value={testEmail}
                            onChange={(e) => setTestEmail(e.target.value)}
                            placeholder="alamat-email-tujuan@domain.com"
                            className="flex-1 sm:w-64 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e] dark:focus:ring-cyan-500 font-mono"
                        />
                        <Button onClick={handleTestEmail} variant="secondary" size="sm" disabled={testing.email}>
                            {testing.email ? "Mengirim…" : "Kirim Uji"}
                        </Button>
                    </div>
                </div>
            </ModernPageCard>

            {/* Kartu 2: Gateway WhatsApp */}
            <ModernPageCard>
                <div className="flex items-center justify-between pb-3 mb-4 border-b border-slate-100 dark:border-slate-700/60">
                    <div className="flex items-center gap-2.5">
                        <div className="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-xl shadow-xs">
                            <i className="ti ti-brand-whatsapp"></i>
                        </div>
                        <div>
                            <h2 className="text-base font-bold text-slate-800 dark:text-slate-100">Gateway WhatsApp</h2>
                            <p className="text-xs text-slate-500 dark:text-slate-400">Pengiriman notifikasi pesan instan pengumuman dan konfirmasi penting.</p>
                        </div>
                    </div>
                    <span className="px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60 hidden sm:inline-block">
                        WA Gateway REST
                    </span>
                </div>

                {/* Info Note */}
                <div className="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/70 dark:border-slate-700/70 text-xs text-slate-600 dark:text-slate-300 mb-5 flex items-start gap-2.5">
                    <i className="ti ti-info-circle text-base text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5"></i>
                    <p className="leading-relaxed">
                        Mendukung penyedia layanan gateway WhatsApp (Fonnte, Wablas, atau custom REST API). Masukkan URL endpoint kirim pesan dan token otentikasi.
                    </p>
                </div>

                {/* Grid Fields WhatsApp */}
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                    <div className="flex flex-col gap-1.5 sm:col-span-2">
                        <label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                            Endpoint API URL
                        </label>
                        <input
                            type="url"
                            value={whatsapp.api_url}
                            onChange={(e) => setWhatsapp({ ...whatsapp, api_url: e.target.value })}
                            placeholder="Contoh: https://api.fonnte.com/send"
                            className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e] dark:focus:ring-cyan-500 font-mono"
                        />
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <label className="text-xs font-semibold text-slate-700 dark:text-slate-300 flex items-center justify-between">
                            <span>API Token / Secret Key</span>
                            <span className="text-[10px] text-slate-400 font-normal">Kosongkan jika tidak diubah</span>
                        </label>
                        <input
                            type="password"
                            value={whatsapp.api_key}
                            onChange={(e) => setWhatsapp({ ...whatsapp, api_key: e.target.value })}
                            placeholder="••••••••••••••••"
                            className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e] dark:focus:ring-cyan-500 font-mono"
                        />
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                            Sender ID / Nomor Pengirim
                        </label>
                        <input
                            type="text"
                            value={whatsapp.sender_id}
                            onChange={(e) => setWhatsapp({ ...whatsapp, sender_id: e.target.value })}
                            placeholder="Format: 628xxxxxxxxxx"
                            className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e] dark:focus:ring-cyan-500 font-mono"
                        />
                    </div>
                </div>

                {/* Live Test WhatsApp Bar */}
                <div className="p-4 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h4 className="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                            <i className="ti ti-brand-whatsapp text-emerald-600 dark:text-emerald-400"></i>
                            Uji Coba Pengiriman WhatsApp
                        </h4>
                        <p className="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                            Kirim pesan simulasi ke nomor telepon pengujian untuk memastikan gateway aktif.
                        </p>
                    </div>

                    <div className="flex items-center gap-2 w-full sm:w-auto">
                        <input
                            type="tel"
                            value={testPhone}
                            onChange={(e) => setTestPhone(e.target.value)}
                            placeholder="628xxxxxxxxxx"
                            className="flex-1 sm:w-64 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1e3a6e] dark:focus:ring-cyan-500 font-mono"
                        />
                        <Button onClick={handleTestWhatsApp} variant="secondary" size="sm" disabled={testing.whatsapp}>
                            {testing.whatsapp ? "Mengirim…" : "Kirim Uji"}
                        </Button>
                    </div>
                </div>
            </ModernPageCard>

            {/* Bottom Single Unified Save Bar */}
            <div className="bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700/80 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-4">
                <p className="text-xs text-slate-500 dark:text-slate-400 text-center sm:text-left">
                    Pastikan konfigurasi diuji sebelum disimpan agar layanan notifikasi tidak terganggu.
                </p>
                <Button onClick={handleSave} variant="primary" size="md" disabled={saving}>
                    <i className="ti ti-device-floppy mr-1.5"></i>
                    {saving ? "Menyimpan Konfigurasi…" : "Simpan Semua Konfigurasi"}
                </Button>
            </div>
        </div>
    );
}
