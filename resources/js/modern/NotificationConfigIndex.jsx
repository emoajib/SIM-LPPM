// Vetted by SOSIOMEN - Manual Review Required by Senior Engineer/Manager
import React, { useEffect, useState } from "react";
import Button from "./components/Button.jsx";
import ModernPageCard from "./components/ModernPageCard.jsx";

export default function NotificationConfigIndex() {
    const [smtp, setSmtp] = useState({
        host: '', port: '587', username: '', password: '',
        encryption: 'tls', from_address: '', from_name: ''
    });
    const [whatsapp, setWhatsapp] = useState({
        api_url: '', api_key: '', sender_id: ''
    });
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [testing, setTesting] = useState({ email: false, whatsapp: false });
    const [toast, setToast] = useState(null);
    const [testEmail, setTestEmail] = useState('');
    const [testPhone, setTestPhone] = useState('');

    const showToast = (type, message) => {
        setToast({ type, message });
        setTimeout(() => setToast(null), 4000);
    };

    const load = async () => {
        setLoading(true);
        try {
            const res = await fetch('/api/v1/settings/notification', {
                credentials: 'same-origin', cache: 'no-store'
            });
            if (!res.ok) throw new Error('Gagal memuat konfigurasi');
            const data = await res.json();
            setSmtp(data.smtp || {});
            setWhatsapp(data.whatsapp || {});
        } catch (e) {
            showToast('error', e.message);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => { load(); }, []);

    const handleSave = async () => {
        setSaving(true);
        try {
            const res = await fetch('/api/v1/settings/notification', {
                method: 'PUT', credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
                body: JSON.stringify({ smtp, whatsapp }),
            });
            if (!res.ok) throw new Error('Gagal menyimpan');
            showToast('success', 'Konfigurasi disimpan.');
        } catch (e) {
            showToast('error', e.message);
        } finally {
            setSaving(false);
        }
    };

    const handleTestEmail = async () => {
        if (!testEmail) return showToast('error', 'Masukkan email tujuan');
        setTesting(e => ({ ...e, email: true }));
        try {
            const res = await fetch('/api/v1/settings/notification/test-email', {
                method: 'POST', credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
                body: JSON.stringify({ email: testEmail }),
            });
            const data = await res.json();
            showToast(data.success ? 'success' : 'error', data.message);
        } catch (e) {
            showToast('error', e.message);
        } finally {
            setTesting(e => ({ ...e, email: false }));
        }
    };

    const handleTestWhatsApp = async () => {
        if (!testPhone) return showToast('error', 'Masukkan nomor WhatsApp');
        setTesting(e => ({ ...e, whatsapp: true }));
        try {
            const res = await fetch('/api/v1/settings/notification/test-whatsapp', {
                method: 'POST', credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
                body: JSON.stringify({ phone: testPhone }),
            });
            const data = await res.json();
            showToast(data.success ? 'success' : 'error', data.message);
        } catch (e) {
            showToast('error', e.message);
        } finally {
            setTesting(e => ({ ...e, whatsapp: false }));
        }
    };

    if (loading) return <div className="flex items-center justify-center h-64">Memuat…</div>;

    return (
        <div className="max-w-4xl mx-auto space-y-4">
            {toast && (
                <div className={`px-4 py-3 rounded-lg border text-sm flex items-center gap-2 ${
                    toast.type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800 dark:bg-emerald-950/50 dark:border-emerald-900/50 dark:text-emerald-300' :
                    toast.type === 'error' ? 'bg-rose-50 border-rose-200 text-rose-800 dark:bg-rose-950/50 dark:border-rose-900/50 dark:text-rose-300' :
                    'bg-sky-50 border-sky-200 text-sky-800 dark:bg-sky-950/50 dark:border-sky-900/50 dark:text-sky-300'
                }`}>
                    {toast.type === 'success' ? '✓' : toast.type === 'error' ? '✕' : 'ℹ'} {toast.message}
                </div>
            )}

            <nav aria-label="Breadcrumb" className="text-xs text-slate-500 dark:text-slate-400 mb-1">
                <a href="/dashboard" className="hover:underline">Dashboard</a>
                <span className="mx-1.5 opacity-60">/</span>
                <a href="/settings" className="hover:underline">Pengaturan</a>
                <span className="mx-1.5 opacity-60">/</span>
                <span className="font-semibold text-slate-700 dark:text-slate-200">Konfigurasi Notifikasi</span>
            </nav>

            <ModernPageCard>
                <div className="mb-4">
                    <h2 className="text-xl font-bold text-slate-800 dark:text-slate-100 mb-1">Konfigurasi Notifikasi</h2>
                    <p className="text-slate-500 dark:text-slate-400">Kelola SMTP & WhatsApp untuk notifikasi email/WA (admin LPPM & super admin).</p>
                </div>

                {/* SMTP Section */}
                <div className="mb-6 p-4 bg-slate-50 dark:bg-slate-800/50 rounded-lg">
                    <h3 className="font-semibold text-slate-800 dark:text-slate-100 mb-4 flex items-center gap-2">
                        <span className="text-lg">📧</span> SMTP Email
                    </h3>
                    <p className="text-sm text-slate-500 dark:text-slate-400 mb-4">
                        Gunakan SMTP kampus (host: mail.itsnu.ac.id, port 465/SSL atau 587/TLS) atau Gmail (smtp.gmail.com, port 587/TLS, aktifkan App Password).
                        Username = email pengirim, password = kata sandi email/App Password.
                    </p>
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div className="flex flex-col gap-1">
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Host</label>
                            <input type="text" value={smtp.host} onChange={e => setSmtp({...smtp, host: e.target.value})}
                                placeholder="smtp.gmail.com / smtp.itsnu.ac.id"
                                className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div className="flex flex-col gap-1">
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Port</label>
                            <input type="number" value={smtp.port} onChange={e => setSmtp({...smtp, port: e.target.value})}
                                className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div className="flex flex-col gap-1">
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Enkripsi</label>
                            <select value={smtp.encryption} onChange={e => setSmtp({...smtp, encryption: e.target.value})}
                                className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm">
                                <option value="tls">TLS (587)</option>
                                <option value="ssl">SSL (465)</option>
                                <option value="none">None</option>
                            </select>
                        </div>
                        <div className="flex flex-col gap-1 md:col-span-2">
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Username (Email Pengirim)</label>
                            <input type="email" value={smtp.username} onChange={e => setSmtp({...smtp, username: e.target.value})}
                                placeholder="noreply@domain.com"
                                className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div className="flex flex-col gap-1 md:col-span-2">
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Password</label>
                            <input type="password" value={smtp.password} onChange={e => setSmtp({...smtp, password: e.target.value})}
                                placeholder="kosongkan jika tidak diubah"
                                className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div className="flex flex-col gap-1 md:col-span-2">
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-200">From Address</label>
                            <input type="email" value={smtp.from_address} onChange={e => setSmtp({...smtp, from_address: e.target.value})}
                                placeholder="noreply@domain.com"
                                className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div className="flex flex-col gap-1 md:col-span-2">
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-200">From Name</label>
                            <input type="text" value={smtp.from_name} onChange={e => setSmtp({...smtp, from_name: e.target.value})}
                                placeholder="SIM LPPM ITSNU Pekalongan"
                                className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm" />
                        </div>
                    </div>
                </div>

                {/* WhatsApp Section */}
                <div className="mb-6 p-4 bg-slate-50 dark:bg-slate-800/50 rounded-lg">
                    <h3 className="font-semibold text-slate-800 dark:text-slate-100 mb-4 flex items-center gap-2">
                        <span className="text-lg">💬</span> WhatsApp
                    </h3>
                    <p className="text-sm text-slate-500 dark:text-slate-400 mb-4">
                        Dapatkan API URL & Key dari provider WA gateway (mis. Fonnte/Wablas).
                        Sender ID = nomor WA pengirim terdaftar. Pastikan nomor tidak spam untuk hindari banned.
                    </p>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div className="flex flex-col gap-1 md:col-span-2">
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-200">API URL</label>
                            <input type="url" value={whatsapp.api_url} onChange={e => setWhatsapp({...whatsapp, api_url: e.target.value})}
                                placeholder="https://api.fonnte.com/send"
                                className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div className="flex flex-col gap-1 md:col-span-2">
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-200">API Key</label>
                            <input type="password" value={whatsapp.api_key} onChange={e => setWhatsapp({...whatsapp, api_key: e.target.value})}
                                placeholder="kosongkan jika tidak diubah"
                                className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div className="flex flex-col gap-1">
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Sender ID</label>
                            <input type="text" value={whatsapp.sender_id} onChange={e => setWhatsapp({...whatsapp, sender_id: e.target.value})}
                                placeholder="628xxxxxxxxxx"
                                className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm" />
                        </div>
                    </div>
                </div>

                {/* Save Button */}
                <div className="mb-6">
                    <Button onClick={handleSave} variant="primary" size="md" disabled={saving}>
                        {saving ? 'Menyimpan…' : 'Simpan Konfigurasi'}
                    </Button>
                </div>

                {/* Test Section */}
                <div className="border-t border-slate-200 dark:border-slate-700 pt-6">
                    <h3 className="font-semibold text-slate-800 dark:text-slate-100 mb-4">Test Pengiriman</h3>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div className="p-4 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg">
                            <h4 className="font-medium text-emerald-800 dark:text-emerald-300 mb-3">Test Email</h4>
                            <p className="text-sm text-emerald-700 dark:text-emerald-400 mb-3">
                                Masukkan email tujuan untuk menguji pengiriman. Pastikan konfigurasi SMTP sudah disimpan terlebih dahulu.
                            </p>
                            <div className="flex gap-2">
                                <input type="email" value={testEmail} onChange={e => setTestEmail(e.target.value)}
                                    placeholder="email@contoh.com"
                                    className="flex-1 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm" />
                                <Button onClick={handleTestEmail} variant="secondary" disabled={testing.email}>
                                    {testing.email ? 'Mengirim…' : 'Kirim Test'}
                                </Button>
                            </div>
                        </div>
                        <div className="p-4 bg-sky-50 dark:bg-sky-900/20 rounded-lg">
                            <h4 className="font-medium text-sky-800 dark:text-sky-300 mb-3">Test WhatsApp</h4>
                            <p className="text-sm text-sky-700 dark:text-sky-400 mb-3">
                                Masukkan nomor WhatsApp tujuan (format internasional, mis. 628xxx). Pastikan API URL & Key sudah diisi.
                            </p>
                            <div className="flex gap-2">
                                <input type="tel" value={testPhone} onChange={e => setTestPhone(e.target.value)}
                                    placeholder="628xxxxxxxxxx"
                                    className="flex-1 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm" />
                                <Button onClick={handleTestWhatsApp} variant="secondary" disabled={testing.whatsapp}>
                                    {testing.whatsapp ? 'Mengirim…' : 'Kirim Test'}
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </ModernPageCard>
        </div>
    );
}