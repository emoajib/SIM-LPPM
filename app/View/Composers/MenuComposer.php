<?php

declare(strict_types=1);

namespace App\View\Composers;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class MenuComposer
{
    public function compose(View $view): void
    {
        $view->with('headerMenuItems', $this->menuItems(Auth::user()));
    }

    protected function menuItems(?User $user): array
    {
        // FORCE SET ACTIVE_ROLE IF EMPTY (Fix production)
        if ($user && ! session('active_role')) {
            $firstRole = $user->getRoleNames()->first();
            if ($firstRole) {
                session(['active_role' => $firstRole]);
                Log::info('MenuComposer: Force set active_role', [
                    'user' => $user->email,
                    'role' => $firstRole,
                ]);
            }
        }

        $roadmapActive = Setting::get('feature_roadmap_active', false);
        $kaprodiValidationActive = Setting::get('feature_kaprodi_validation', false);

        $items = [
            // Dashboard - available for all roles
            [
                'title' => 'Dashboard',
                'icon' => 'smart-home',
                'route' => 'dashboard',
            ],
            // Manual Book / Panduan - available for all authenticated roles
            [
                'title' => 'Panduan',
                'icon' => 'book',
                'route' => 'manual-books.index',
                'roles' => ['dosen', 'kepala lppm', 'admin lppm', 'superadmin', 'rektor', 'dekan', 'kaprodi', 'reviewer'],
            ],
            // Dosen menu (+ dekan for monitoring their faculty proposals)
            [
                'title' => 'Penelitian',
                'icon' => 'microscope',
                'roles' => ['dosen', 'kepala lppm', 'admin lppm', 'rektor', 'dekan'],
                'children' => [
                    [
                        'title' => 'Usulan',
                        'icon' => 'file-text',
                        'route' => 'research.proposal.index',
                    ],
                    [
                        'title' => 'Perbaikan Usulan',
                        'icon' => 'checkbox',
                        'route' => 'research.proposal-revision.index',
                    ],
                    [
                        'title' => 'Laporan Akhir',
                        'icon' => 'file-text',
                        'route' => 'research.final-report.index',
                    ],
                    [
                        'title' => 'Catatan Harian',
                        'icon' => 'layout-2',
                        'route' => 'research.daily-note.index',
                    ],
                ],
            ],
            [
                'title' => 'Pengabdian',
                'icon' => 'heart-handshake',
                'roles' => ['dosen', 'kepala lppm', 'admin lppm', 'rektor', 'dekan'],
                'children' => [
                    [
                        'title' => 'Usulan',
                        'icon' => 'file-text',
                        'route' => 'community-service.proposal.index',
                    ],
                    [
                        'title' => 'Perbaikan Usulan',
                        'icon' => 'checkbox',
                        'route' => 'community-service.proposal-revision.index',
                    ],
                    [
                        'title' => 'Laporan Akhir',
                        'icon' => 'file-text',
                        'route' => 'community-service.final-report.index',
                    ],
                    [
                        'title' => 'Catatan Harian',
                        'icon' => 'layout-2',
                        'route' => 'community-service.daily-note.index',
                    ],
                ],
            ],
            ...($kaprodiValidationActive ? [[
                'title' => 'Persetujuan Kaprodi',
                'icon' => 'clipboard-check',
                'route' => 'kaprodi.proposals.index',
                'roles' => ['kaprodi'],
            ]] : []),
            // Kaprodi - Conditional Roadmap
            ...($roadmapActive ? [[
                'title' => 'Kelola Peta Jalan',
                'icon' => 'map-2',
                'route' => 'settings.master-data',
                'params' => ['group' => 'academic-structure', 'tab' => 'study-program-roadmaps'],
                'roles' => ['kaprodi'],
            ]] : []),
            // Dekan menu
            [
                'title' => 'Persetujuan Dekan',
                'icon' => 'clipboard-check',
                'route' => 'dekan.proposals.index',
                'roles' => ['dekan'],
            ],
            [
                'title' => 'Persetujuan Laporan',
                'icon' => 'report-analytics',
                'route' => 'dekan.reports.index',
                'roles' => ['dekan'],
            ],
            // Dekan - Conditional Roadmap
            ...($roadmapActive ? [[
                'title' => 'Kelola Peta Jalan',
                'icon' => 'map-2',
                'route' => 'settings.master-data',
                'params' => ['group' => 'academic-structure', 'tab' => 'faculty-roadmaps'],
                'roles' => ['dekan'],
            ]] : []),
            [
                'title' => 'Riwayat Persetujuan',
                'icon' => 'history',
                'route' => 'dekan.approval-history',
                'roles' => ['dekan'],
            ],
            // Kepala LPPM menus
            [
                'title' => 'Persetujuan (Awal)',
                'icon' => 'checkbox',
                'route' => 'kepala-lppm.initial-approval',
                'roles' => ['kepala lppm'],
            ],
            [
                'title' => 'Persetujuan (Akhir)',
                'icon' => 'circle-check',
                'route' => 'kepala-lppm.final-decision',
                'roles' => ['kepala lppm'],
            ],
            [
                'title' => 'Persetujuan Laporan',
                'icon' => 'report-analytics',
                'route' => 'kepala-lppm.report-approval',
                'roles' => ['kepala lppm'],
            ],
            // Admin LPPM - Reviewer Management Group
            [
                'title' => 'Reviewer',
                'icon' => 'clipboard-check',
                'roles' => ['admin lppm'],
                'children' => [
                    [
                        'title' => 'Penugasan Reviewer',
                        'icon' => 'user-plus',
                        'route' => 'admin-lppm.assign-reviewers',
                    ],
                    [
                        'title' => 'Beban Kerja Reviewer',
                        'icon' => 'chart-bar',
                        'route' => 'admin-lppm.reviewer-workload',
                    ],
                    [
                        'title' => 'Monitoring Review',
                        'icon' => 'eye',
                        'route' => 'admin-lppm.review-monitoring',
                    ],
                ],
            ],
            [
                'title' => 'Monev',
                'icon' => 'chart-infographic',
                'route' => 'admin-lppm.monev.index',
                'roles' => ['admin lppm'],
            ],
            // Reviewer menu
            [
                'title' => 'Review Penelitian',
                'icon' => 'lifebuoy',
                'route' => 'review.research',
                'roles' => ['reviewer'],
            ],
            [
                'title' => 'Review Pengabdian',
                'icon' => 'lifebuoy',
                'route' => 'review.community-service',
                'roles' => ['reviewer'],
            ],
            [
                'title' => 'Riwayat Review',
                'icon' => 'history',
                'route' => 'review.review-history',
                'roles' => ['reviewer'],
            ],
            [
                'title' => 'Monev Laporan',
                'icon' => 'chart-infographic',
                'route' => 'review.monev',
                'roles' => ['reviewer'],
            ],
            [
                'title' => 'IKU & Luaran',
                'icon' => 'target-arrow',
                'roles' => ['admin lppm', 'rektor', 'kepala lppm'],
                'children' => [
                    [
                        'title' => 'Dashboard IKU',
                        'icon' => 'chart-pie',
                        'route' => 'accreditation.hub',
                    ],
                    [
                        'title' => 'Verifikasi Luaran',
                        'icon' => 'checkbox',
                        'route' => 'accreditation.verification',
                    ],
                ],
            ],
            // Laporan - Reports menu (+ kepala lppm for decision-making)
            [
                'title' => 'Laporan',
                'icon' => 'report-analytics',
                'roles' => ['admin lppm', 'rektor', 'kepala lppm', 'dekan'],
                'children' => [
                    [
                        'title' => 'Monitoring Laporan',
                        'icon' => 'list-search',
                        'route' => 'reports.monitoring',
                    ],
                    [
                        'title' => 'Laporan Penelitian',
                        'icon' => 'report',
                        'route' => 'reports.research',
                    ],
                    [
                        'title' => 'Laporan PKM',
                        'icon' => 'report',
                        'route' => 'reports.pkm',
                    ],
                    [
                        'title' => 'Laporan Luaran',
                        'icon' => 'award',
                        'route' => 'reports.outputs',
                    ],
                    [
                        'title' => 'Laporan Kerjasama Mitra',
                        'icon' => 'handshake',
                        'route' => 'reports.partners',
                    ],
                    [
                        'title' => 'Laporan IKU',
                        'icon' => 'file-text',
                        'route' => 'reports.iku',
                    ],
                    [
                        'title' => 'Laporan Monev',
                        'icon' => 'clipboard-data',
                        'route' => 'reports.monev',
                    ],
                    [
                        'title' => 'Rekap Monev',
                        'icon' => 'chart-dots',
                        'route' => 'kepala-lppm.monev.recap',
                        'roles' => ['kepala lppm', 'rektor'],
                    ],
                    [
                        'title' => 'Capaian Monev',
                        'icon' => 'dashboard',
                        'route' => 'kepala-lppm.rektor.monev-dashboard',
                        'roles' => ['kepala lppm', 'rektor'],
                    ],
                ],
            ],
            // kelola pengguna - admin lppm
            [
                'title' => 'Pengguna',
                'icon' => 'users-group',
                'roles' => ['admin lppm', 'superadmin'],
                'children' => [
                    [
                        'title' => 'Daftar Pengguna',
                        'icon' => 'list',
                        'route' => 'users.index',
                        'active' => ['users.index', 'users.show', 'users.edit'],
                        'expand_index' => false,
                    ],
                    [
                        'title' => 'Buat Pengguna',
                        'icon' => 'user-plus',
                        'route' => 'users.create',
                    ],
                    [
                        'title' => 'Import Pengguna',
                        'icon' => 'upload',
                        'route' => 'users.import',
                    ],
                    [
                        'title' => 'Sinkronisasi SINTA',
                        'icon' => 'database-import',
                        'route' => 'sync-sinta',
                    ],
                ],
            ],
            // Data Arsip - admin lppm
            [
                'title' => 'Arsip',
                'icon' => 'folders',
                'route' => 'admin.archives',
                'roles' => ['admin lppm', 'superadmin'],
            ],
            // Export SINTA - admin lppm
            [
                'title' => 'SINTA',
                'icon' => 'database-export',
                'route' => 'export-sinta',
                'roles' => ['admin lppm', 'superadmin'],
            ],
            // settings:
            // - master data - admin lppm
            [
                'title' => 'Pengaturan',
                'icon' => 'settings-2',
                'roles' => ['admin lppm', 'superadmin'],
                'children' => [
                    [
                        'title' => 'Master Data',
                        'icon' => 'layers',
                        'route' => 'settings.master-data',
                        'params' => ['group' => 'academic-content'],
                        'active' => ['group=academic-content'],
                    ],
                    [
                        'title' => 'Struktur Akademik',
                        'icon' => 'building-2',
                        'route' => 'settings.master-data',
                        'params' => ['group' => 'academic-structure'],
                        'active' => ['group=academic-structure'],
                    ],
                    [
                        'title' => 'Anggaran & RAB',
                        'icon' => 'archive',
                        'route' => 'settings.master-data',
                        'params' => ['group' => 'budget'],
                        'active' => ['group=budget'],
                    ],
                    [
                        'title' => 'Kemitraan',
                        'icon' => 'users',
                        'route' => 'settings.master-data',
                        'params' => ['group' => 'partnership'],
                        'active' => ['group=partnership'],
                    ],
                    [
                        'title' => 'Jadwal Proposal',
                        'icon' => 'calendar-time',
                        'route' => 'settings.proposal-schedule',
                    ],
                    [
                        'title' => 'Template Proposal',
                        'icon' => 'file-download',
                        'route' => 'settings.proposal-template',
                    ],
                ],
            ],
        ];

        return array_values(array_filter(array_map(
            fn (array $item) => $this->formatItem($item, $user),
            $items,
        )));
    }

    protected function formatItem(array $item, ?User $user): ?array
    {
        $allowedRoles = $item['roles'] ?? null;

        if ($allowedRoles !== null && (! $user || ! active_has_any_role($allowedRoles))) {
            return null;
        }

        $routeName = $item['route'] ?? null;

        // Format child items if they exist
        $children = null;
        if (isset($item['children']) && is_array($item['children'])) {
            $children = array_values(array_filter(array_map(
                fn (array $child) => $this->formatDropdownItem($child, $user),
                $item['children'],
            )));
        }

        // Check if any child route is active
        $hasActiveChild = false;
        if ($children) {
            foreach ($children as $child) {
                if ($child['active'] ?? false) {
                    $hasActiveChild = true;
                    break;
                }
            }
        }

        $formatted = [
            'type' => isset($item['children']) && count($children ?? []) > 0 ? 'dropdown' : 'link',
            'title' => $item['title'],
            'href' => $this->resolveHref($item),
            'icon' => $item['icon'] ?? null,
            'active' => $this->isActive($item, $routeName) || $hasActiveChild,
            'navigate' => $item['navigate'] ?? true,
        ];

        if ($children) {
            $formatted['dropdown'] = [
                'auto_close' => 'outside',
                'items' => $children,
            ];
            $formatted['children'] = $children;
        }

        return $formatted;
    }

    protected function formatDropdownItem(array $item, ?User $user): ?array
    {
        $allowedRoles = $item['roles'] ?? null;

        if ($allowedRoles !== null && (! $user || ! active_has_any_role($allowedRoles))) {
            return null;
        }

        $routeName = $item['route'] ?? null;
        $params = $item['params'] ?? [];

        $children = null;
        if (isset($item['children']) && is_array($item['children'])) {
            $children = array_values(array_filter(array_map(
                fn (array $child) => $this->formatDropdownItem($child, $user),
                $item['children'],
            )));
        }

        $formatted = [
            'label' => $item['title'],
            'href' => $this->resolveHref($item),
            'prefix_icon' => $item['icon'] ?? null,
            'prefix_icon_class' => 'icon icon-2 icon-inline me-1',
            'route' => $routeName,
            'params' => $params,
            'active' => $this->isActive($item, $routeName),
            'navigate' => $item['navigate'] ?? true,
        ];

        if ($children) {
            $formatted['children'] = $children;
        }

        return $formatted;
    }

    protected function resolveHref(array $item): string
    {
        $routeName = $item['route'] ?? null;
        $params = $item['params'] ?? [];

        if ($routeName && Route::has($routeName)) {
            return route($routeName, $params);
        }

        $href = $item['href'] ?? null;

        if (empty($href) || $href === '#') {
            return '#';
        }

        if (str_starts_with($href, 'http')) {
            return $href;
        }

        return url($href);
    }

    protected function isActive(array $item, ?string $routeName): bool
    {
        $patterns = (array) ($item['active'] ?? array_filter([$routeName]));
        $expandIndex = $item['expand_index'] ?? true;

        foreach ($patterns as $pattern) {
            if (empty($pattern)) {
                continue;
            }

            // Check for query parameter matches (e.g. group=academic-structure)
            if (str_contains($pattern, '=')) {
                // Ensure the route matches if one is defined
                if ($routeName && ! request()->routeIs($routeName)) {
                    continue;
                }

                [$key, $value] = explode('=', $pattern);
                $queryVal = request()->query($key);

                // Handle default values if query param is missing
                if ($queryVal === null && $key === 'group' && $value === 'academic-content') {
                    return true;
                }

                if ($queryVal === $value) {
                    return true;
                }

                continue;
            }

            if (request()->routeIs($pattern)) {
                return true;
            }

            // For index routes, also check all other actions in the same resource
            if ($expandIndex && str_ends_with($pattern, '.index')) {
                $resourceRoute = substr($pattern, 0, -6);

                if (request()->routeIs($resourceRoute.'.*')) {
                    return true;
                }
            }
        }

        return false;
    }
}
