<?php

// Vetted by SOSIOMEN - Manual Review Required by Senior Engineer/Manager

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

/**
 * Single source of truth for which settings are available to the current user
 * in the Modern theme, and where each one lives.
 *
 * Modern pages are React; the remaining ones fall back to the Classic Livewire
 * screens so nothing becomes unreachable (zero functional gap).
 */
class SettingsHubApiController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($user !== null, 401);

        $isAdmin = $user->activeHasAnyRole(['admin lppm', 'superadmin']);
        $isMasterData = $user->activeHasAnyRole(['admin lppm', 'superadmin', 'dekan', 'kaprodi']);

        $sections = [
            [
                'title' => 'Akun',
                'items' => array_values(array_filter([
                    $this->item('profile', 'Profil Saya', 'Kelola data profil dan identitas akademik.', 'user', true, 'settings.profile.page', 'settings.profile'),
                    $this->item('password', 'Ubah Kata Sandi', 'Perbarui kata sandi akun Anda.', 'lock', true, 'settings.password.page', 'settings.password'),
                    $this->item('two-factor', 'Autentikasi Dua Faktor', 'Amankan akun dengan verifikasi tambahan.', 'shield-lock', false, null, 'two-factor.show', Features::canManageTwoFactorAuthentication()),
                    $this->item('security', 'Log Keamanan', 'Riwayat aktivitas keamanan akun.', 'shield-check', false, null, 'settings', true, 'security'),
                ])),
            ],
            [
                'title' => 'Sistem',
                'items' => array_values(array_filter([
                    $this->item('notification-config', 'Konfigurasi Notifikasi', 'Kelola SMTP email dan gateway WhatsApp.', 'bell', true, 'settings.notification-config.page', 'admin-lppm.notification-config', $isAdmin),
                    $this->item('feature-flags', 'Feature Flags', 'Aktifkan/nonaktifkan modul dan fitur opsional.', 'toggle-right', true, 'settings.feature-flags.page', 'settings', $isAdmin),
                    $this->item('captcha', 'Keamanan CAPTCHA', 'Atur audio dan suara CAPTCHA.', 'shield', true, 'settings.captcha.page', 'settings', $isAdmin),
                    $this->item('appearance', 'Tampilan', 'Nama dashboard, judul login, dan logo.', 'palette', false, null, 'settings.appearance', $isAdmin),
                    $this->item('pdf-export', 'PDF & Ekspor', 'Preferensi tipografi, tata letak, dan logo PDF.', 'file-type-pdf', false, null, 'settings', $isAdmin, 'pdf-export'),
                    $this->item('tkt-manager', 'Manajemen TKT', 'Kelola tingkat kesiapan teknologi (TKT).', 'chart-histogram', false, null, 'settings', $isAdmin, 'tkt-manager'),
                    $this->item('audit', 'Audit Log', 'Lihat jejak audit sistem.', 'list-details', false, null, 'admin-lppm.audit-log', $isAdmin),
                    $this->item('sync', 'Sinkronisasi Data', 'Sinkronisasi data SINTA dan eksternal.', 'refresh', false, null, 'settings', $isAdmin, 'sync'),
                    $this->item('backup', 'Backup Data', 'Buat dan unduh cadangan data.', 'database-export', false, null, 'settings', $isAdmin, 'backup'),
                    $this->item('restore', 'Pulihkan Data', 'Pulihkan data dari cadangan.', 'database-import', false, null, 'settings', $isAdmin, 'restore'),
                ])),
            ],
            [
                'title' => 'Master Data & Konfigurasi Akademik',
                'items' => array_values(array_filter([
                    $this->item('master-data', 'Master Data', 'Fakultas, prodi, skema, anggaran, tema, dan lainnya.', 'folders', false, null, 'settings.master-data', $isMasterData),
                    $this->item('proposal-schedule', 'Jadwal Proposal', 'Atur jadwal pembukaan dan penutupan usulan.', 'calendar-event', false, null, 'settings.proposal-schedule', $isAdmin),
                    $this->item('proposal-template', 'Template Proposal', 'Kelola template dokumen proposal.', 'file-text', false, null, 'settings.proposal-template', $isAdmin),
                    $this->item('contracts', 'Nomor Kontrak Usulan', 'Kelola penomoran kontrak usulan.', 'file-pencil', false, null, 'settings.contracts', $isAdmin),
                    $this->item('manual-books', 'Manual Book', 'Kelola panduan pengguna.', 'book-2', false, null, 'settings.manual-books', $isAdmin),
                ])),
            ],
        ];

        // Drop empty sections.
        $sections = array_values(array_filter($sections, static fn (array $section): bool => $section['items'] !== []));

        return response()->json(['sections' => $sections]);
    }

    /**
     * Build a hub item. Returns null when the user may not access it.
     *
     * @return array{key: string, title: string, description: string, icon: string, href: string, modern: bool}|null
     */
    private function item(
        string $key,
        string $title,
        string $description,
        string $icon,
        bool $modern,
        ?string $modernRoute,
        string $classicRoute,
        bool $allowed = true,
        ?string $classicTab = null,
    ): ?array {
        if (! $allowed) {
            return null;
        }

        $href = null;
        $isModern = $modern && $modernRoute !== null && Route::has($modernRoute);

        if ($isModern) {
            $href = route($modernRoute);
        } elseif (Route::has($classicRoute)) {
            // Deep-link into the Classic settings tab so the user lands on the
            // exact panel instead of the default "Profil Saya" tab.
            $href = $classicTab !== null
                ? route($classicRoute, ['tab' => $classicTab])
                : route($classicRoute);
        }

        if ($href === null) {
            return null;
        }

        return [
            'key' => $key,
            'title' => $title,
            'description' => $description,
            'icon' => $icon,
            'href' => $href,
            'modern' => $isModern,
        ];
    }
}
