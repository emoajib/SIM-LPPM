<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Proposal;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Halaman tema modern yang dirender server (Blade + root React).
 *
 * Sebelumnya logika ini ditulis sebagai closure duplikat di routes/web.php
 * untuk modul penelitian dan pengabdian — sumber divergensi (BC-01/BC-03).
 * Sekarang satu implementasi, modul ditentukan lewat route default 'module'.
 */
class ModernPageController extends Controller
{
    public function verifikasiLuaran(Request $request): View|RedirectResponse
    {
        if (! is_modern_theme()) {
            return redirect()->route('accreditation.verification');
        }

        $isCommunityService = $this->module($request) === 'community-service';

        return view($isCommunityService ? 'modern.verifikasi-luaran-pkm' : 'modern.verifikasi-luaran', [
            'title' => 'Verifikasi Luaran IKU',
            'pageSubtitle' => $isCommunityService
                ? 'Pemantauan dan verifikasi target output pengabdian masyarakat'
                : 'Pemantauan dan verifikasi target output penelitian & pengabdian',
        ]);
    }

    public function dailyNote(Request $request, Proposal $proposal): View|RedirectResponse
    {
        if (! is_modern_theme()) {
            return redirect()->route($this->routeName($request, 'daily-note.show'), $proposal);
        }

        $user = $request->user();
        Gate::authorize('viewDailyNote', $proposal);

        return view('modern.daily-note', [
            'title' => 'Realisasi Anggaran',
            'pageSubtitle' => 'Catatan harian aktivitas dan pengeluaran dana kegiatan',
            'proposal' => $proposal,
            'canManage' => $this->canManageProposal($user, $proposal),
            'isEditable' => ! $proposal->logbook_approved_at,
        ]);
    }

    public function proposalShow(Request $request, Proposal $proposal): View|RedirectResponse
    {
        if (! is_modern_theme()) {
            return redirect()->route($this->routeName($request, 'proposal.show'), $proposal);
        }

        Gate::authorize('view', $proposal);

        return view('modern.proposal-show', [
            'title' => 'Detail Proposal',
            'pageSubtitle' => $proposal->title,
            'proposal' => $proposal,
        ]);
    }

    /**
     * Nama modul dari route default ('research' | 'community-service').
     */
    private function module(Request $request): string
    {
        $module = $request->route()?->defaults['module'] ?? 'research';

        return $module === 'community-service' ? 'community-service' : 'research';
    }

    /**
     * Bentuk nama route lengkap sesuai modul, mis. research.daily-note.show.
     */
    private function routeName(Request $request, string $suffix): string
    {
        return $this->module($request).'.'.$suffix;
    }

    /**
     * Apakah user adalah pengusul atau anggota tim (boleh mengelola catatan/realisasi).
     */
    private function canManageProposal(User $user, Proposal $proposal): bool
    {
        return $proposal->submitter_id === $user->id
            || $proposal->teamMembers()->where('user_id', $user->id)->exists();
    }

    /**
     * Modern theme: Konfigurasi Notifikasi (admin LPPM/superadmin only).
     */
    public function settingsNotificationConfig(Request $request): View|RedirectResponse
    {
        if (! is_modern_theme()) {
            return redirect()->route('settings.notification-config');
        }

        abort_unless($request->user()?->activeHasAnyRole(['admin lppm', 'superadmin']), 403);

        return view('modern.settings-notification-config', [
            'title' => 'Konfigurasi Notifikasi',
            'pageSubtitle' => 'Kelola SMTP & WhatsApp untuk notifikasi email/WA (admin LPPM & super admin)',
        ]);
    }

    /**
     * Modern theme: Profil Saya (all authenticated users).
     */
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

    /**
     * Modern theme: Pusat Pengaturan (all authenticated users).
     */
    public function settingsHub(Request $request): View|RedirectResponse
    {
        if (! is_modern_theme()) {
            return redirect()->route('settings');
        }

        return view('modern.settings-index', [
            'title' => 'Pengaturan',
            'pageSubtitle' => 'Kelola akun, sistem, dan master data aplikasi',
        ]);
    }

    /**
     * Modern theme: Ubah Kata Sandi (all authenticated users).
     */
    public function settingsPassword(Request $request): View|RedirectResponse
    {
        if (! is_modern_theme()) {
            return redirect()->route('settings.password');
        }

        return view('modern.settings-password', [
            'title' => 'Ubah Kata Sandi',
            'pageSubtitle' => 'Perbarui kata sandi akun Anda',
        ]);
    }

    /**
     * Modern theme: Feature Flags (admin LPPM/superadmin only).
     */
    public function settingsFeatureFlags(Request $request): View|RedirectResponse
    {
        if (! is_modern_theme()) {
            return redirect()->route('settings');
        }

        abort_unless($request->user()?->activeHasAnyRole(['admin lppm', 'superadmin']), 403);

        return view('modern.settings-feature-flags', [
            'title' => 'Feature Flags',
            'pageSubtitle' => 'Aktifkan atau nonaktifkan modul dan fitur opsional',
        ]);
    }

    /**
     * Modern theme: Keamanan CAPTCHA (admin LPPM/superadmin only).
     */
    public function settingsCaptcha(Request $request): View|RedirectResponse
    {
        if (! is_modern_theme()) {
            return redirect()->route('settings');
        }

        abort_unless($request->user()?->activeHasAnyRole(['admin lppm', 'superadmin']), 403);

        return view('modern.settings-captcha', [
            'title' => 'Keamanan CAPTCHA',
            'pageSubtitle' => 'Atur ketersediaan dan jenis suara audio CAPTCHA',
        ]);
    }
}
