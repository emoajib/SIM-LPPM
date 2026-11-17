// Vetted by SOSIOMEN - Manual Review Required by Senior Engineer/Manager
import React, { useEffect, useState } from "react";
import Button from "./components/Button.jsx";
import ModernPageCard from "./components/ModernPageCard.jsx";
import SettingsHeaderNav from "./components/SettingsHeaderNav.jsx";
import Skeleton from "./components/Skeleton.jsx";

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
            showToast("success", data.message || "Pengaturan audio CAPTCHA berhasil diperbarui.");
        } catch (e) {
            showToast("error", e.message);
        } finally {
            setSaving(false);
        }
    };

    if (loading) {
        return (
            <div className="space-y-6">
                <SettingsHeaderNav
                    title="Keamanan CAPTCHA"
                    subtitle="Atur preferensi audio dan suara verifikasi tantangan CAPTCHA pada halaman login pengguna."
                    categoryBadge="Keamanan Akses"
                    currentPath="/settings/captcha/modern"
                />
                <div className="grid grid-cols-1 gap-6">
                    <Skeleton className="h-44 w-full rounded-2xl" />
                    <Skeleton className="h-64 w-full rounded-2xl" />
                </div>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <SettingsHeaderNav
                title="Keamanan CAPTCHA"
                subtitle="Atur preferensi audio dan suara verifikasi tantangan CAPTCHA pada halaman login pengguna."
                categoryBadge="Keamanan Akses"
                currentPath="/settings/captcha/modern"
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

            {/* Accessibility Note */}
            <div className="p-4 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-700/80 text-xs text-slate-600 dark:text-slate-300 flex items-start gap-3">
                <i className="ti ti-info-circle text-lg shrink-0 mt-0.5 text-[#1e3a6e] dark:text-cyan-400"></i>
                <div className="leading-relaxed">
                    Tantangan audio CAPTCHA dirancang untuk aksesibilitas pengguna (disabilitas penglihatan). Modul audio otomatis aktif sebagai fallback saat proteksi Cloudflare Turnstile tidak dikonfigurasi.
                </div>
            </div>

            {/* Section 1: Toggle Audio Challenge */}
            <ModernPageCard>
                <div className="flex items-center justify-between pb-3 mb-4 border-b border-slate-100 dark:border-slate-700/60">
                    <div className="flex items-center gap-2.5">
                        <div className="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-[#1e3a6e] dark:text-cyan-400 flex items-center justify-center text-xl shadow-xs">
                            <i className="ti ti-volume"></i>
                        </div>
                        <div>
                            <h2 className="text-base font-bold text-slate-800 dark:text-slate-100">
                                Mode Audio Tantangan
                            </h2>
                            <p className="text-xs text-slate-500 dark:text-slate-400">
                                Mengaktifkan tombol putar suara verifikasi pada formulir login.
                            </p>
                        </div>
                    </div>
                </div>

                <div className="flex items-center justify-between gap-4 p-4 rounded-xl bg-slate-50/70 dark:bg-slate-900/40 border border-slate-200/70 dark:border-slate-700/60">
                    <div>
                        <div className="flex items-center gap-2">
                            <label htmlFor="audio_enabled" className="text-sm font-bold text-slate-800 dark:text-slate-100 cursor-pointer">
                                Aktifkan Soal Audio Tantangan
                            </label>
                            <span className={`px-2 py-0.5 rounded-full text-[10px] font-bold ${
                                audioEnabled
                                    ? "bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800"
                                    : "bg-slate-200/70 text-slate-600 dark:bg-slate-700/70 dark:text-slate-400 border border-slate-300/60 dark:border-slate-600"
                            }`}>
                                {audioEnabled ? "Aktif" : "Nonaktif"}
                            </span>
                        </div>
                        <p className="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            {audioEnabled
                                ? "Pengguna dapat mendengarkan pembacaan angka/karakter audio di samping gambar CAPTCHA."
                                : "Soal suara dinonaktifkan, verifikasi hanya berbasis tantangan gambar statis."}
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
                        <span className="w-11 h-6 bg-slate-400 dark:bg-slate-500 border border-slate-400 dark:border-slate-500 peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-[#1e3a6e] rounded-full peer-checked:bg-emerald-600 dark:peer-checked:bg-emerald-500 transition-colors" />
                        <span className="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow-md transition-transform peer-checked:translate-x-5" />
                    </label>
                </div>
            </ModernPageCard>

            {/* Section 2: Pilihan Suara (Voice Selector) */}
            <ModernPageCard>
                <div className="flex items-center justify-between pb-3 mb-4 border-b border-slate-100 dark:border-slate-700/60">
                    <div className="flex items-center gap-2.5">
                        <div className="w-9 h-9 rounded-xl bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-400 flex items-center justify-center text-xl shadow-xs">
                            <i className="ti ti-microphone-2"></i>
                        </div>
                        <div>
                            <h2 className="text-base font-bold text-slate-800 dark:text-slate-100">
                                Pilihan Narator Suara
                            </h2>
                            <p className="text-xs text-slate-500 dark:text-slate-400">
                                Pilih profil narator dan bahasa sintesis suara yang digunakan.
                            </p>
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-3.5">
                    {voices.map((voice) => {
                        const isSelected = audioVoice === voice.key;
                        return (
                            <label
                                key={voice.key}
                                className={`relative flex items-start gap-3.5 p-4 rounded-xl border transition-all cursor-pointer ${
                                    isSelected
                                        ? "bg-[#1e3a6e]/5 border-[#1e3a6e] dark:bg-cyan-950/30 dark:border-cyan-500 shadow-xs ring-1 ring-[#1e3a6e] dark:ring-cyan-500"
                                        : "bg-white dark:bg-slate-800 border-slate-200/80 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600"
                                } ${voice.available ? "" : "opacity-50 cursor-not-allowed bg-slate-50 dark:bg-slate-900/40"}`}
                            >
                                <input
                                    type="radio"
                                    name="audio_voice"
                                    value={voice.key}
                                    checked={isSelected}
                                    disabled={!voice.available}
                                    onChange={() => setAudioVoice(voice.key)}
                                    className="mt-1 text-[#1e3a6e] focus:ring-[#1e3a6e] dark:focus:ring-cyan-500"
                                />

                                <div className="flex-1 min-w-0">
                                    <div className="flex items-center justify-between gap-2 mb-1">
                                        <span className="text-sm font-bold text-slate-800 dark:text-slate-100">
                                            {voice.label}
                                        </span>
                                        <span className="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300">
                                            {voice.locale ? voice.locale.toUpperCase() : "ID"}
                                        </span>
                                    </div>

                                    <div className="flex items-center gap-2 mt-2">
                                        {voice.available ? (
                                            <span className="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400">
                                                <i className="ti ti-circle-check text-xs"></i>
                                                Aset Suara Siap
                                            </span>
                                        ) : (
                                            <span className="inline-flex items-center gap-1 text-[11px] font-semibold text-rose-600 dark:text-rose-400">
                                                <i className="ti ti-circle-x text-xs"></i>
                                                Aset Belum Terinstal
                                            </span>
                                        )}
                                    </div>
                                </div>
                            </label>
                        );
                    })}
                </div>
            </ModernPageCard>

            {/* Bottom Single Unified Save Action */}
            <div className="bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700/80 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-4">
                <p className="text-xs text-slate-500 dark:text-slate-400 text-center sm:text-left">
                    Pengaturan audio CAPTCHA diterapkan langsung pada form autentikasi publik.
                </p>
                <Button onClick={handleSave} variant="primary" size="md" disabled={saving}>
                    <i className="ti ti-device-floppy mr-1.5"></i>
                    {saving ? "Menyimpan Pengaturan…" : "Simpan Pengaturan CAPTCHA"}
                </Button>
            </div>
        </div>
    );
}
