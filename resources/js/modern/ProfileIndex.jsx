// Vetted by SOSIOMEN - Manual Review Required by Senior Engineer/Manager
import React, { useEffect, useState } from "react";
import Button from "./components/Button.jsx";
import ModernPageCard from "./components/ModernPageCard.jsx";

export default function ProfileIndex() {
    const [user, setUser] = useState({ name: '', email: '', username: '', whatsapp_number: '', photo_url: null });
    const [identity, setIdentity] = useState(null);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [uploading, setUploading] = useState(false);
    const [toast, setToast] = useState(null);
    const [photoFile, setPhotoFile] = useState(null);
    const [photoPreview, setPhotoPreview] = useState(null);

    const showToast = (type, message) => {
        setToast({ type, message });
        setTimeout(() => setToast(null), 4000);
    };

    const load = async () => {
        setLoading(true);
        try {
            const res = await fetch('/api/v1/settings/profile', {
                credentials: 'same-origin', cache: 'no-store'
            });
            if (!res.ok) throw new Error('Gagal memuat profil');
            const data = await res.json();
            setUser(data.user || {});
            setIdentity(data.identity || {});
            if (data.user?.photo_url) setPhotoPreview(data.user.photo_url);
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
            const payload = {
                user: {
                    name: user.name,
                    email: user.email,
                    whatsapp_number: user.whatsapp_number,
                },
                identity: identity,
            };
            const res = await fetch('/api/v1/settings/profile', {
                method: 'PUT', credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
                body: JSON.stringify(payload),
            });
            if (!res.ok) throw new Error('Gagal menyimpan profil');
            showToast('success', 'Profil diperbarui.');
            load();
        } catch (e) {
            showToast('error', e.message);
        } finally {
            setSaving(false);
        }
    };

    const handlePhotoChange = (e) => {
        const file = e.target.files?.[0];
        if (file) {
            setPhotoFile(file);
            setPhotoPreview(URL.createObjectURL(file));
        }
    };

    const handleUploadPhoto = async () => {
        if (!photoFile) return showToast('error', 'Pilih file foto terlebih dahulu');
        setUploading(true);
        try {
            const formData = new FormData();
            formData.append('photo', photoFile);
            const res = await fetch('/api/v1/settings/profile/photo', {
                method: 'POST', credentials: 'same-origin',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' },
                body: formData,
            });
            if (!res.ok) throw new Error('Gagal upload foto');
            showToast('success', 'Foto profil diperbarui.');
            setPhotoFile(null);
            load();
        } catch (e) {
            showToast('error', e.message);
        } finally {
            setUploading(false);
        }
    };

    const handleDeletePhoto = async () => {
        if (!window.confirm('Hapus foto profil?')) return;
        try {
            const res = await fetch('/api/v1/settings/profile/photo', {
                method: 'DELETE', credentials: 'same-origin',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' },
            });
            if (!res.ok) throw new Error('Gagal hapus foto');
            showToast('success', 'Foto profil dihapus.');
            setPhotoPreview(null);
            load();
        } catch (e) {
            showToast('error', e.message);
        }
    };

    const identityFields = [
        { key: 'identity_id', label: 'NIDN / NUPTK', type: 'text' },
        { key: 'type', label: 'Tipe Identitas', type: 'select', options: ['dosen', 'mahasiswa', 'reviewer'] },
        { key: 'title_prefix', label: 'Gelar Depan', type: 'text' },
        { key: 'title_suffix', label: 'Gelar Belakang', type: 'text' },
        { key: 'sinta_id', label: 'SINTA ID', type: 'text' },
        { key: 'scopus_id', label: 'Scopus ID', type: 'text' },
        { key: 'google_scholar_id', label: 'Google Scholar ID', type: 'text' },
        { key: 'wos_id', label: 'WoS ID', type: 'text' },
        { key: 'orcid_id', label: 'ORCID ID', type: 'text' },
        { key: 'gender', label: 'Jenis Kelamin', type: 'select', options: ['L', 'P'] },
        { key: 'address', label: 'Alamat', type: 'textarea' },
        { key: 'birthdate', label: 'Tanggal Lahir', type: 'date' },
        { key: 'birthplace', label: 'Tempat Lahir', type: 'text' },
        { key: 'last_education', label: 'Pendidikan Terakhir', type: 'text' },
        { key: 'functional_position', label: 'Jabatan Fungsional', type: 'text' },
    ];

    if (loading) return <div className="flex items-center justify-center h-64">Memuat…</div>;

    return (
        <div className="space-y-4">
            {toast && (
                <div className={`px-4 py-3 rounded-lg border text-sm flex items-center gap-2 ${
                    toast.type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800 dark:bg-emerald-950/50 dark:border-emerald-900/50 dark:text-emerald-300' :
                    'bg-rose-50 border-rose-200 text-rose-800 dark:bg-rose-950/50 dark:border-rose-900/50 dark:text-rose-300'
                }`}>
                    {toast.type === 'success' ? '✓' : '✕'} {toast.message}
                </div>
            )}

            <nav aria-label="Breadcrumb" className="text-xs text-slate-500 dark:text-slate-400 mb-1">
                <a href="/dashboard" className="hover:underline">Dashboard</a>
                <span className="mx-1.5 opacity-60">/</span>
                <a href="/settings" className="hover:underline">Pengaturan</a>
                <span className="mx-1.5 opacity-60">/</span>
                <span className="font-semibold text-slate-700 dark:text-slate-200">Profil Saya</span>
            </nav>

            <ModernPageCard>
                <div className="mb-4">
                    <h2 className="text-xl font-bold text-slate-800 dark:text-slate-100 mb-1">Profil Saya</h2>
                    <p className="text-slate-500 dark:text-slate-400">Kelola data profil dan identitas akademik Anda.</p>
                </div>

                {/* Photo Section */}
                <div className="mb-6 flex items-center gap-6">
                    <div className="relative w-24 h-24">
                        {photoPreview ? (
                            <img src={photoPreview} alt="Profile" className="w-full h-full rounded-full object-cover border-2 border-slate-200 dark:border-slate-700" />
                        ) : (
                            <div className="w-full h-full rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center text-3xl font-bold text-slate-500">
                                {user.name?.charAt(0)?.toUpperCase() || '?'}
                            </div>
                        )}
                    </div>
                    <div className="flex flex-col gap-2">
                        {photoFile && (
                            <Button onClick={handleUploadPhoto} variant="primary" size="sm" disabled={uploading}>
                                {uploading ? 'Mengunggah…' : 'Unggah Foto'}
                            </Button>
                        )}
                        {photoPreview && !photoFile && (
                            <Button onClick={handleDeletePhoto} variant="ghost" size="sm" className="text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20">
                                Hapus Foto
                            </Button>
                        )}
                        <input type="file" id="photo-upload" accept="image/jpeg,image/png" onChange={handlePhotoChange} className="hidden" />
                        <label htmlFor="photo-upload" className="cursor-pointer text-sm text-[#1e3a6e] hover:underline">
                            {photoFile ? 'Ganti Foto' : 'Pilih Foto'}
                        </label>
                        <p className="text-xs text-slate-500">Maks 2MB, format JPG/PNG. Rasio 1:1 direkomendasikan.</p>
                    </div>
                </div>

                {/* User Data Section */}
                <div className="mb-6 p-4 bg-slate-50 dark:bg-slate-800/50 rounded-lg">
                    <h3 className="font-semibold text-slate-800 dark:text-slate-100 mb-4">Data Akun</h3>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div className="flex flex-col gap-1">
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Nama Lengkap</label>
                            <input type="text" value={user.name} onChange={e => setUser({...user, name: e.target.value})}
                                className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm" required />
                        </div>
                        <div className="flex flex-col gap-1">
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Username</label>
                            <input type="text" value={user.username} readOnly
                                className="bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-500" />
                        </div>
                        <div className="flex flex-col gap-1">
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Email</label>
                            <input type="email" value={user.email} onChange={e => setUser({...user, email: e.target.value})}
                                className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm" required />
                        </div>
                        <div className="flex flex-col gap-1">
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Nomor WhatsApp</label>
                            <input type="tel" value={user.whatsapp_number} onChange={e => setUser({...user, whatsapp_number: e.target.value})}
                                placeholder="628xxxxxxxxxx"
                                className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm" />
                        </div>
                    </div>
                </div>

                {/* Identity Section */}
                {identity && (
                    <div className="mb-6 p-4 bg-slate-50 dark:bg-slate-800/50 rounded-lg">
                        <h3 className="font-semibold text-slate-800 dark:text-slate-100 mb-4">Identitas Akademik</h3>
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            {identityFields.map(field => (
                                <div key={field.key} className="flex flex-col gap-1">
                                    <label className="text-sm font-medium text-slate-700 dark:text-slate-200">{field.label}</label>
                                    {field.type === 'select' ? (
                                        <select value={identity[field.key] || ''}
                                            onChange={e => setIdentity({...identity, [field.key]: e.target.value})}
                                            className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm">
                                            <option value="">-- Pilih --</option>
                                            {field.options.map(opt => (
                                                <option key={opt} value={opt}>{opt}</option>
                                            ))}
                                        </select>
                                    ) : field.type === 'textarea' ? (
                                        <textarea value={identity[field.key] || ''}
                                            onChange={e => setIdentity({...identity, [field.key]: e.target.value})}
                                            rows={2}
                                            className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm" />
                                    ) : (
                                        <input type={field.type} value={identity[field.key] || ''}
                                            onChange={e => setIdentity({...identity, [field.key]: e.target.value})}
                                            className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm" />
                                    )}
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* Save Button */}
                <div className="flex justify-end">
                    <Button onClick={handleSave} variant="primary" size="md" disabled={saving}>
                        {saving ? 'Menyimpan…' : 'Simpan Perubahan'}
                    </Button>
                </div>
            </ModernPageCard>
        </div>
    );
}