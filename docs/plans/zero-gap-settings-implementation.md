# Zero-Gap Settings Implementation Plan

**Target:** Eliminate ALL gaps between Classic & Modern themes for:
1. **Konfigurasi Notifikasi** (SMTP + WhatsApp) — Critical infrastructure
2. **Profil Saya** (User + Identity) — Used everywhere

**Constraint:** Follow AGENTS.md workflow strictly.

---

## 🔍 EKOSISTEM INTEGRATION MAP

### 1. NotificationConfig — "Jantung Notifikasi"

```
┌─────────────────────────────────────────────────────────────────┐
│                    AppServiceProvider::boot()                   │
│  (runs EVERY request after storage/app/.installed exists)      │
│  → NotificationConfigService::get()                             │
│    → NotificationSetting::get() [DB, decrypted]                │
│  → config(['mail.mailers.smtp.*']) ← OVERRIDES .env            │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│              NotificationService::send()                        │
│    → Notification::send() via ['database', HybridChannel]      │
│    → 30+ Notification classes in app/Notifications/            │
│    → HybridNotificationChannel::send()                          │
│       → Try WhatsApp (placeholder) → Fallback: EMAIL           │
│          → Uses mail config set by AppServiceProvider          │
└─────────────────────────────────────────────────────────────────┘
                              │
        ┌─────────────────────┼─────────────────────┐
        ▼                     ▼                     ▼
┌───────────────┐    ┌───────────────┐    ┌───────────────┐
│  Auth/Reset   │    │  Proposal     │    │  Scheduled    │
│  Password     │    │  Workflow     │    │  Summaries    │
│  2FA          │    │  Submit/      │    │  Daily/Weekly │
│  Verify Email │    │  Review/      │    │  Reminders    │
│               │    │  Approve      │    │               │
└───────────────┘    └───────────────┘    └───────────────┘

CRITICAL: If SMTP not configured → ALL notifications fail silently
```

### 2. Profile/Identity — "Tulang Punggung Data"

```
User Profile Fields → Identity Fields → Used in:
├── name, email, whatsapp_number
│   └── Notifications, Auth, Team members, All displays
├── identity_id (NIDN/NUPTK)
│   └── PDF signatures, Authorization scoping (Dekan/Kaprodi),
│       Team member forms, Historical imports, Excel exports
├── sinta_id, scopus_id, google_scholar_id, wos_id
│   └── Dashboard links, PDF exports, Eligibility checks,
│       SINTA sync, IKU dashboards
├── title_prefix, title_suffix
│   └── PDF signatures, Formal displays, format_name()
├── faculty_id, study_program_id
│   └── Authorization scoping (Dekan/Kaprodi), Filtering,
│       Dashboard grouping
├── sinta_score_v3_overall, scopus_h_index, wos_h_index
│   └── Dashboard displays, Eligibility checks, Sidebar
└── functional_position, address, etc.
    └── PDF exports, User management
```

---

## 🎯 IMPLEMENTATION STRATEGY: SHARED BACKEND API + MODERN FRONTEND

### Architecture Decision
**Do NOT duplicate business logic.** Create API endpoints that wrap existing services, then build React components consuming them.

```
┌────────────────────────────────────────────────────────────┐
│                    EXISTING (Classic)                      │
│  NotificationConfig Livewire ──────► NotificationConfigService │
│  ProfileForm Livewire ──────────► User/Identity models     │
└────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌────────────────────────────────────────────────────────────┐
│                    NEW (Shared API)                        │
│  GET/PUT   /api/v1/settings/notification                   │
│  POST      /api/v1/settings/notification/test-email        │
│  POST      /api/v1/settings/notification/test-whatsapp     │
│  GET/PUT   /api/v1/settings/profile                        │
│  POST      /api/v1/settings/profile/photo                  │
│  DELETE    /api/v1/settings/profile/photo                  │
└────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌────────────────────────────────────────────────────────────┐
│                    NEW (Modern React)                      │
│  NotificationConfigIndex.jsx                               │
│  ProfileIndex.jsx                                          │
└────────────────────────────────────────────────────────────┘
```

---

## 📋 DETAILED TASK BREAKDOWN

### PHASE 1: API LAYER (Foundation) — ~2 jam

#### 1.1 Create `NotificationConfigApiController`
```php
// app/Http/Controllers/Api/Settings/NotificationConfigApiController.php

class NotificationConfigApiController extends Controller
{
    public function __construct(
        private NotificationConfigService $service
    ) {}

    // GET /api/v1/settings/notification
    public function show(): JsonResponse
    {
        abort_unless(auth()->user()?->activeHasAnyRole(['admin lppm', 'superadmin']), 403);
        
        return response()->json([
            'smtp' => [
                'host' => $this->service->get('MAIL_HOST'),
                'port' => $this->service->get('MAIL_PORT'),
                'username' => $this->service->get('MAIL_USERNAME'),
                'encryption' => $this->service->get('MAIL_ENCRYPTION'),
                'from_address' => $this->service->get('MAIL_FROM_ADDRESS'),
                'from_name' => $this->service->get('MAIL_FROM_NAME'),
                // password NEVER returned (security)
            ],
            'whatsapp' => [
                'api_url' => $this->service->get('WHATSAPP_API_URL'),
                'sender_id' => $this->service->get('WHATSAPP_SENDER_ID'),
                // api_key NEVER returned
            ],
        ]);
    }

    // PUT /api/v1/settings/notification
    public function update(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->activeHasAnyRole(['admin lppm', 'superadmin']), 403);
        
        $validated = $request->validate([
            'smtp.host' => 'nullable|string|max:255',
            'smtp.port' => 'nullable|integer|min:1|max:65535',
            'smtp.username' => 'nullable|string|max:255',
            'smtp.password' => 'nullable|string', // empty = don't change
            'smtp.encryption' => 'nullable|in:ssl,tls,none',
            'smtp.from_address' => 'nullable|email|max:255',
            'smtp.from_name' => 'nullable|string|max:255',
            'whatsapp.api_url' => 'nullable|url|max:500',
            'whatsapp.api_key' => 'nullable|string', // empty = don't change
            'whatsapp.sender_id' => 'nullable|string|max:50',
        ]);

        $this->service->save($validated);
        
        return response()->json(['message' => 'Konfigurasi notifikasi disimpan.']);
    }

    // POST /api/v1/settings/notification/test-email
    public function testEmail(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->activeHasAnyRole(['admin lppm', 'superadmin']), 403);
        
        $request->validate(['email' => 'required|email']);
        
        $result = $this->service->sendTestEmail($request->email);
        
        return response()->json($result);
    }

    // POST /api/v1/settings/notification/test-whatsapp
    public function testWhatsApp(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->activeHasAnyRole(['admin lppm', 'superadmin']), 403);
        
        $request->validate(['phone' => 'required|string|regex:/^62[0-9]{9,12}$/']);
        
        $result = $this->service->sendTestWhatsApp($request->phone);
        
        return response()->json($result);
    }
}
```

#### 1.2 Create `ProfileApiController`
```php
// app/Http/Controllers/Api/Settings/ProfileApiController.php

class ProfileApiController extends Controller
{
    public function __construct(
        private HandleHybridInstitution $institutionHandler
    ) {}

    // GET /api/v1/settings/profile
    public function show(): JsonResponse
    {
        $user = auth()->user()->load('identity');
        
        return response()->json([
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'whatsapp_number' => $user->whatsapp_number,
                'photo_url' => $user->profilePicture(),
            ],
            'identity' => $user->identity ? [
                'identity_id' => $user->identity->identity_id,
                'type' => $user->identity->type,
                'title_prefix' => $user->identity->title_prefix,
                'title_suffix' => $user->identity->title_suffix,
                'sinta_id' => $user->identity->sinta_id,
                'scopus_id' => $user->identity->scopus_id,
                'google_scholar_id' => $user->identity->google_scholar_id,
                'wos_id' => $user->identity->wos_id,
                'orcid_id' => $user->identity->orcid_id,
                'gender' => $user->identity->gender,
                'address' => $user->identity->address,
                'birthdate' => $user->identity->birthdate?->format('Y-m-d'),
                'birthplace' => $user->identity->birthplace,
                'last_education' => $user->identity->last_education,
                'functional_position' => $user->identity->functional_position,
                'institution_id' => $user->identity->institution_id,
                'institution_name' => $user->identity->institution_name,
                'faculty_id' => $user->identity->faculty_id,
                'study_program_id' => $user->identity->study_program_id,
                'science_cluster_id' => $user->identity->science_cluster_id,
            ] : null,
            'permissions' => [
                'can_edit_identity' => $user->activeHasRole('dosen'), // or all?
            ],
        ]);
    }

    // PUT /api/v1/settings/profile
    public function update(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'user.name' => 'required|string|max:255',
            'user.email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'user.whatsapp_number' => 'nullable|string|max:20|regex:/^62[0-9]{9,12}$/',
            'identity.identity_id' => 'nullable|string|max:50',
            'identity.type' => 'nullable|in:dosen,mahasiswa,reviewer',
            'identity.title_prefix' => 'nullable|string|max:50',
            'identity.title_suffix' => 'nullable|string|max:50',
            'identity.sinta_id' => 'nullable|string|max:100',
            'identity.scopus_id' => 'nullable|string|max:100',
            'identity.google_scholar_id' => 'nullable|string|max:100',
            'identity.wos_id' => 'nullable|string|max:100',
            'identity.orcid_id' => 'nullable|string|max:100',
            'identity.gender' => 'nullable|in:L,P',
            'identity.address' => 'nullable|string|max:500',
            'identity.birthdate' => 'nullable|date',
            'identity.birthplace' => 'nullable|string|max:100',
            'identity.last_education' => 'nullable|string|max:100',
            'identity.functional_position' => 'nullable|string|max:100',
            'identity.faculty_id' => 'nullable|exists:faculties,id',
            'identity.study_program_id' => 'nullable|exists:study_programs,id',
            'identity.science_cluster_id' => 'nullable|exists:science_clusters,id',
        ]);

        $user->update($validated['user'] ?? []);
        
        if (isset($validated['identity'])) {
            $user->identity()->updateOrCreate(
                ['user_id' => $user->id],
                $validated['identity']
            );
            $this->institutionHandler->handle($user);
        }
        
        activity()->log('Profile updated via API');
        
        return response()->json(['message' => 'Profil diperbarui.']);
    }

    // POST /api/v1/settings/profile/photo
    public function uploadPhoto(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $user = auth()->user();
        $path = $request->file('photo')->store('profile-photos', 'public');
        
        // Delete old photo if exists
        if ($user->photo) {
            Storage::disk('public')->delete($user->photo);
        }
        
        $user->update(['photo' => $path]);
        
        return response()->json([
            'message' => 'Foto profil diperbarui.',
            'photo_url' => Storage::url($path),
        ]);
    }

    // DELETE /api/v1/settings/profile/photo
    public function deletePhoto(): JsonResponse
    {
        $user = auth()->user();
        
        if ($user->photo) {
            Storage::disk('public')->delete($user->photo);
            $user->update(['photo' => null]);
        }
        
        return response()->json(['message' => 'Foto profil dihapus.']);
    }
}
```

#### 1.3 Register API Routes
```php
// routes/api.php (or routes/web.php with api prefix)

Route::middleware(['auth', 'verified'])->prefix('settings')->group(function () {
    // Notification Config (admin only - middleware enforced in controller)
    Route::get('notification', [NotificationConfigApiController::class, 'show']);
    Route::put('notification', [NotificationConfigApiController::class, 'update']);
    Route::post('notification/test-email', [NotificationConfigApiController::class, 'testEmail']);
    Route::post('notification/test-whatsapp', [NotificationConfigApiController::class, 'testWhatsApp']);
    
    // Profile (all authenticated users)
    Route::get('profile', [ProfileApiController::class, 'show']);
    Route::put('profile', [ProfileApiController::class, 'update']);
    Route::post('profile/photo', [ProfileApiController::class, 'uploadPhoto']);
    Route::delete('profile/photo', [ProfileApiController::class, 'deletePhoto']);
});
```

#### 1.4 Update NotificationConfigService for API compatibility
```php
// app/Services/NotificationConfigService.php - add public methods

public function getAll(): array
{
    return [
        'MAIL_HOST' => $this->get('MAIL_HOST'),
        'MAIL_PORT' => $this->get('MAIL_PORT'),
        'MAIL_USERNAME' => $this->get('MAIL_USERNAME'),
        'MAIL_ENCRYPTION' => $this->get('MAIL_ENCRYPTION'),
        'MAIL_FROM_ADDRESS' => $this->get('MAIL_FROM_ADDRESS'),
        'MAIL_FROM_NAME' => $this->get('MAIL_FROM_NAME'),
        'WHATSAPP_API_URL' => $this->get('WHATSAPP_API_URL'),
        'WHATSAPP_SENDER_ID' => $this->get('WHATSAPP_SENDER_ID'),
    ];
}

public function save(array $validated): void
{
    // SMTP
    if (isset($validated['smtp'])) {
        $this->set('MAIL_HOST', $validated['smtp']['host'] ?? '');
        $this->set('MAIL_PORT', $validated['smtp']['port'] ?? '587');
        $this->set('MAIL_USERNAME', $validated['smtp']['username'] ?? '');
        if (!empty($validated['smtp']['password'])) {
            $this->set('MAIL_PASSWORD', $validated['smtp']['password'], true);
        }
        $this->set('MAIL_ENCRYPTION', $validated['smtp']['encryption'] ?? 'tls');
        $this->set('MAIL_FROM_ADDRESS', $validated['smtp']['from_address'] ?? '');
        $this->set('MAIL_FROM_NAME', $validated['smtp']['from_name'] ?? '');
    }
    
    // WhatsApp
    if (isset($validated['whatsapp'])) {
        $this->set('WHATSAPP_API_URL', $validated['whatsapp']['api_url'] ?? '');
        if (!empty($validated['whatsapp']['api_key'])) {
            $this->set('WHATSAPP_API_KEY', $validated['whatsapp']['api_key'], true);
        }
        $this->set('WHATSAPP_SENDER_ID', $validated['whatsapp']['sender_id'] ?? '');
    }
    
    // Refresh mail config immediately
    $this->refreshMailConfig();
}

public function sendTestEmail(string $email): array
{
    try {
        Mail::to($email)->send(new \App\Mail\TestMail());
        return ['success' => true, 'message' => 'Email test terkirim.'];
    } catch (\Throwable $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

public function sendTestWhatsApp(string $phone): array
{
    try {
        $url = $this->get('WHATSAPP_API_URL');
        $key = $this->get('WHATSAPP_API_KEY');
        
        if (!$url || !$key) {
            return ['success' => false, 'message' => 'Konfigurasi WhatsApp belum lengkap.'];
        }
        
        $res = Http::withHeaders(['Authorization' => 'Bearer ' . $key])
            ->asForm()
            ->post($url, ['target' => $phone, 'message' => 'Test notifikasi SIM-LPPM']);
        
        if ($res->successful()) {
            return ['success' => true, 'message' => 'WhatsApp test terkirim.'];
        }
        
        return ['success' => false, 'message' => 'Gagal: ' . $res->body()];
    } catch (\Throwable $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
```

---

### PHASE 2: MODERN REACT COMPONENTS — ~3 jam

#### 2.1 `NotificationConfigIndex.jsx`
```jsx
// resources/js/modern/NotificationConfigIndex.jsx
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
        <div className="space-y-4">
            {toast && (
                <div className={`px-4 py-3 rounded-lg border text-sm flex items-center gap-2 ${
                    toast.type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' :
                    toast.type === 'error' ? 'bg-rose-50 border-rose-200 text-rose-800' :
                    'bg-sky-50 border-sky-200 text-sky-800'
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
```

#### 2.2 `ProfileIndex.jsx`
```jsx
// resources/js/modern/ProfileIndex.jsx
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
                    toast.type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' :
                    'bg-rose-50 border-rose-200 text-rose-800'
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
                            <Button onClick={handleDeletePhoto} variant="ghost" size="sm" className="text-rose-600 hover:bg-rose-50">
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
```

---

### PHASE 3: REGISTRATION & ROUTING — ~30 menit

#### 3.1 Update `config/modern-pages.php`
```php
'pages' => [
    // ... existing ...
    'settings.notification-config' => 'notification-config-index',
    'settings.profile' => 'profile-index',
],
```

#### 3.2 Update `resources/js/modern.jsx`
```javascript
import NotificationConfigIndex from "./modern/NotificationConfigIndex.jsx";
import ProfileIndex from "./modern/ProfileIndex.jsx";

const PAGES = {
    // ... existing ...
    "notification-config-index": NotificationConfigIndex,
    "profile-index": ProfileIndex,
};
```

#### 3.3 Update `routes/web.php` — Add modern routes
```php
// In the modern theme route group (around line 277, 337)
Route::get('settings/notification-config', [ModernPageController::class, 'settingsNotificationConfig'])
    ->name('settings.notification-config.page')
    ->middleware(['auth', 'verified', 'role:admin lppm|superadmin']);

Route::get('settings/profile', [ModernPageController::class, 'settingsProfile'])
    ->name('settings.profile.page')
    ->middleware(['auth', 'verified']);

// In ModernPageController
public function settingsNotificationConfig(Request $request): View|RedirectResponse
{
    if (! is_modern_theme()) {
        return redirect()->route('settings.notification-config');
    }
    return view('modern.settings-notification-config', [
        'title' => 'Konfigurasi Notifikasi',
        'pageSubtitle' => 'Kelola SMTP & WhatsApp untuk notifikasi email/WA',
    ]);
}

public function settingsProfile(Request $request): View|RedirectResponse
{
    if (! is_modern_theme()) {
        return redirect()->route('settings.profile');
    }
    return view('modern.settings-profile', [
        'title' => 'Profil Saya',
        'pageSubtitle' => 'Kelola data profil dan identitas akademik Anda',
    ]);
}
```

#### 3.4 Create Blade Views
```blade
<!-- resources/views/modern/settings-notification-config.blade.php -->
@extends('components.layouts.app')

@php
    $pageTitle = 'Konfigurasi Notifikasi';
    $pageSubtitle = 'Kelola SMTP & WhatsApp untuk notifikasi email/WA (admin LPPM & super admin)';
@endphp

@pushOnce('scripts')
    <script>
        window.__MODERN_PAGE__ = 'notification-config-index';
        window.__USER_ROLES__ = @json(auth()->user()?->getRoleNames() ?? []);
        window.__ACTIVE_ROLE__ = '{{ session('active_role') ?? '' }}';
    </script>
@endpushOnce
```

```blade
<!-- resources/views/modern/settings-profile.blade.php -->
@extends('components.layouts.app')

@php
    $pageTitle = 'Profil Saya';
    $pageSubtitle = 'Kelola data profil dan identitas akademik Anda';
@endphp

@pushOnce('scripts')
    <script>
        window.__MODERN_PAGE__ = 'profile-index';
        window.__USER_ID__ = '{{ auth()->id() }}';
    </script>
@endpushOnce
```

---

## ✅ VERIFICATION CHECKLIST (Zero-Gap)

### NotificationConfig Parity
| Feature | Classic | Modern | Test |
|---|---|---|---|
| Load SMTP config | ✅ | ✅ | `GET /api/v1/settings/notification` |
| Load WhatsApp config | ✅ | ✅ | Same API |
| Save SMTP (host, port, user, pass, encryption, from) | ✅ | ✅ | `PUT /api/v1/settings/notification` |
| Save WhatsApp (url, key, sender) | ✅ | ✅ | Same API |
| Test Email | ✅ | ✅ | `POST /api/v1/settings/notification/test-email` |
| Test WhatsApp | ✅ | ✅ | `POST /api/v1/settings/notification/test-whatsapp` |
| Validation | ✅ | ✅ | Both sides |
| Toast feedback | ✅ | ✅ | React state |
| Role check (admin lppm/superadmin) | ✅ | ✅ | Controller + middleware |
| Password/API Key masked | ✅ | ✅ | Input type=password |
| Empty password = don't change | ✅ | ✅ | Service logic |

### Profile Parity
| Feature | Classic | Modern | Test |
|---|---|---|---|
| Load user data (name, email, username, whatsapp, photo) | ✅ | ✅ | `GET /api/v1/settings/profile` |
| Load identity data (all 18 fields) | ✅ | ✅ | Same API |
| Save user data | ✅ | ✅ | `PUT /api/v1/settings/profile` |
| Save identity data | ✅ | ✅ | Same API |
| Upload photo (max 2MB, jpg/png) | ✅ | ✅ | `POST /api/v1/settings/profile/photo` |
| Delete photo | ✅ | ✅ | `DELETE /api/v1/settings/profile/photo` |
| Validation (email unique, WA format) | ✅ | ✅ | Both sides |
| Institution sync (HandleHybridInstitution) | ✅ | ✅ | Controller calls action |
| Toast feedback | ✅ | ✅ | React state |
| Role-based identity edit | ✅ | ✅ | Future: permission field |

### Ecosystem Impact Verification
| Integration Point | Depends On | Verified By |
|---|---|---|
| AppServiceProvider mail override | NotificationSetting DB | Test email sent |
| HybridNotificationChannel WhatsApp | WHATSAPP_API_URL/KEY | Test WA sent |
| All 30+ Notifications | Mail config | Trigger any notification |
| PDF Exports (signatures, cover) | Identity fields | Export PDF |
| Authorization scoping (Dekan/Kaprodi) | faculty_id/study_program_id | Access test |
| Eligibility checks | SINTA/Scopus scores | Submit proposal |
| Dashboard displays | sinta_score, scopus_h_index | View dashboard |
| Team member forms | NIDN/SINTA | Add team member |
| Excel exports | Identity data | Export proposals |

---

## 🧪 TESTS TO ADD

```php
// tests/Feature/Api/Settings/NotificationConfigApiTest.php
// tests/Feature/Api/Settings/ProfileApiTest.php

// Each covering:
// - Authorization (403 for non-admin, 200 for admin)
// - CRUD operations
// - Test email/whatsapp
// - Validation
// - Integration with existing services
```

---

## 🚀 EXECUTION ORDER (AGENTS.md Compliant)

1. **Create API Controllers + Routes** → `php artisan test` (new tests pass)
2. **Update NotificationConfigService** → `php artisan test` (existing + new pass)
3. **Create React Components** → `npm run build` → `php artisan modern:check`
4. **Register Modern Pages + Routes + Blade** → `php artisan modern:check`, `php artisan theme:check`
5. **Add Tests** → `php artisan test` (all pass)
6. **Run Full Suite** → `php artisan test`, `pint`, `phpstan`, `regression:check`, `copy:check`
7. **Commit & Push** → GH Actions green
8. **Deploy via update_production.sh**

---

## 📝 NOTES

- **No duplicate logic**: API controllers delegate to existing services (`NotificationConfigService`, `HandleHybridInstitution`)
- **Security**: Passwords/API keys never returned in GET; encrypted in DB
- **Backward compatible**: Classic theme continues working unchanged
- **Single source of truth**: DB settings via `NotificationSetting` model
- **Immediate effect**: `refreshMailConfig()` called after save