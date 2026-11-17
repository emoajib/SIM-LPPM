// Vetted by SOSIOMEN - Manual Review Required by Senior Engineer/Manager
import React, { useEffect, useState } from "react";
import Button from "./components/Button.jsx";
import ModernPageCard from "./components/ModernPageCard.jsx";

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? "";

export default function CaptchaIndex() {
    const [audioEnabled, setAudioEnabled] = useState(true);
    const [audioVoice, setAudioVoice] = useState("");
    const [voices, setVoices] = useState([]);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [toast, setToast] = useState(null);

    const showToast = (type, message) => {
        setToast({ type, message });
        setTimeout(() => setToast(null), 4000);
    };

    useEffect(() => {
        let alive = true;
        (async () => {
            try {
                const res = await fetch("/api/v1/settings/captcha", {
                    credentials: "same-origin",
                    cache: "no-store",
                    headers: { Accept: "application/json" },
                });
                if (!res.ok) throw new Error("Gagal memuat pengaturan CAPTCHA.");
                const data = await res.json();
                if (alive) {
                    setAudioEnabled(Boolean(data.audio_enabled));
                    setAudioVoice(data.audio_voice || "");
                    setVoices(data.voices || []);
                }
            } catch (e) {
                if (alive) showToast("error", e.message);
            } finally {
                if (alive) setLoading(false);
            }
        })();
        return () => {
            alive = false;
        };
    }, []);

    const handleSave = async () => {
        setSaving(true);
        try {
            const res = await fetch("/api/v1/settings/captcha", {
                method: "PUT",
                credentials: "same-origin",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrf(),
                },
                body: JSON.stringify({ audio_enabled: audioEnabled, audio_voice: audioVoice }),
            });

            if (res.status === 422) {
                const data = await res.json();
                showToast("error", data.errors?.audio_voice?.[0] || data.message || "Isian tidak valid.");
                return;
            }

            if (!res.ok) throw new Error("Gagal menyimpan pengaturan CAPTCHA.");
            const data = await res.json();
            if (data.data) {
                setAudioEnabled(Boolean(data.data.audio_enabled));
                setAudioVoice(data.data.audio_voice || "");
                setVoices(data.data.voices || []);
            }
            showToast("success", data.message || "Pengaturan audio CAPTCHA diperbarui.");
        } catch (e) {
            showToast("error", e.message);
        } finally {
            setSaving(false);
        }
    };

    if (loading) {
        return <div className="flex items-center justify-center h-64 text-slate-500 dark:text-slate-400">Memuat…</div>;
    }

    return (
        <div className="max-w-4xl mx-auto space-y-4">
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
                <span className="font-semibold text-slate-700 dark:text-slate-200">Keamanan CAPTCHA</span>
            </nav>

            <ModernPageCard>
                <div className="mb-4">
                    <h2 className="text-xl font-bold text-slate-800 dark:text-slate-100 mb-1">Keamanan CAPTCHA</h2>
                    <p className="text-slate-500 dark:text-slate-400">
                        Audio hanya tampil di halaman login ketika Turnstile tidak dikonfigurasi.
                    </p>
                </div>

                <div className="flex items-start justify-between gap-4 py-3 border-b border-slate-100 dark:border-slate-700/60">
                    <div className="min-w-0">
                        <p className="text-sm font-medium text-slate-800 dark:text-slate-100">Aktifkan soal audio</p>
                        <p className="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Jika dimatikan, pengguna hanya dapat menyelesaikan soal bergambar.
                        </p>
                    </div>
                    <label htmlFor="audio_enabled" className="relative inline-flex items-center cursor-pointer shrink-0">
                        <input
                            id="audio_enabled"
                            type="checkbox"
                            checked={audioEnabled}
                            onChange={(e) => setAudioEnabled(e.target.checked)}
                            className="sr-only peer"
                        />
                        <span className="w-11 h-6 bg-slate-300 dark:bg-slate-600 peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-[#1e3a6e] rounded-full peer-checked:bg-[#1e3a6e] dark:peer-checked:bg-[#4a7bc8] transition-colors" />
                        <span className="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform peer-checked:translate-x-5" />
                    </label>
                </div>

                <fieldset className="mt-4">
                    <legend className="text-sm font-medium text-slate-800 dark:text-slate-100 mb-2">Jenis suara</legend>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-2">
                        {voices.map((voice) => (
                            <label
                                key={voice.key}
                                className={`block border rounded-lg p-3 cursor-pointer transition-colors ${
                                    audioVoice === voice.key
                                        ? "border-[#1e3a6e] bg-[#1e3a6e]/5 dark:border-[#4a7bc8] dark:bg-[#4a7bc8]/10"
                                        : "border-slate-200 dark:border-slate-700"
                                } ${voice.available ? "" : "opacity-50 cursor-not-allowed"}`}
                            >
                                <span className="flex items-start gap-2">
                                    <input
                                        type="radio"
                                        name="audio_voice"
                                        value={voice.key}
                                        checked={audioVoice === voice.key}
                                        disabled={!voice.available}
                                        onChange={() => setAudioVoice(voice.key)}
                                        className="mt-1"
                                    />
                                    <span>
                                        <span className="block text-sm font-medium text-slate-800 dark:text-slate-100">
                                            {voice.label}
                                        </span>
                                        <span className="block text-xs text-slate-500 dark:text-slate-400">
                                            {voice.locale ? voice.locale.toUpperCase() : "—"}
                                        </span>
                                        {!voice.available && (
                                            <span className="inline-block mt-1 text-[10px] font-semibold uppercase tracking-wide px-1.5 py-0.5 rounded bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">
                                                Aset tidak tersedia
                                            </span>
                                        )}
                                    </span>
                                </span>
                            </label>
                        ))}
                    </div>
                </fieldset>

                <div className="flex justify-end mt-5">
                    <Button onClick={handleSave} variant="primary" size="md" disabled={saving}>
                        {saving ? "Menyimpan…" : "Simpan Pengaturan"}
                    </Button>
                </div>
            </ModernPageCard>
        </div>
    );
}
