<?php

declare(strict_types=1);

namespace App\View\Composers;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

// Vetted by AI - Manual Review Required by Senior Engineer/Manager
class MenuComposer
{
    public function compose(View $view): void
    {
        $user = Auth::user();
        $cacheKey = 'menu_items_'.($user ? $user->id : 'guest').'_'.(active_role() ?? 'none');

        $items = Cache::remember($cacheKey, 60, function () use ($user) {
            return $this->menuItems($user);
        });

        $view->with('headerMenuItems', $items);
        $view->with('userActiveTheme', session('sim_lppm_app_theme', request()->cookie('sim_lppm_app_theme', 'modern')));
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

        $activeRole = active_role();

        $roadmapActive = Setting::get('feature_roadmap_active', false);
        $kaprodiValidationActive = Setting::get('feature_kaprodi_validation', false);
        $letteringActive = Setting::get('module_persuratan_active', false);

        $items = [
            // Dashboard - available for all roles
            [
                'title' => 'Dashboard',
                'icon' => 'home',
                'route' => 'dashboard',
            ],
            // Manual Book / Panduan - admin/superadmin ke CRUD, lainnya ke viewer
            [
                'title' => 'Panduan',
                'icon' => 'book-2',
                'route' => 'admin-lppm.manual-books.index',
                'roles' => ['dosen', 'kepala lppm', 'admin lppm', 'superadmin', 'rektor', 'dekan', 'kaprodi', 'reviewer'],
            ],
            // Dosen menu (+ dekan for monitoring their faculty proposals)
            [
                'title' => 'Penelitian',
                'icon' => 'flask',
                'roles' => ['dosen', 'kepala lppm', 'admin lppm', 'rektor', 'dekan', 'kaprodi'],
                'children' => [
                    [
                        'title' => 'Usulan',
                        'icon' => 'file-text',
                        'route' => 'research.proposal.index',
                    ],
                    [
                        'title' => 'Perbaikan Usulan',
                        'icon' => 'file-pencil',
                        'route' => 'research.proposal-revision.index',
                    ],
                    [
                        'title' => 'Laporan Akhir',
                        'icon' => 'report',
                        'route' => 'research.final-report.index',
                    ],
                    [
                        'title' => 'Realisasi Anggaran',
                        'icon' => 'wallet',
                        'route' => 'research.daily-note.index',
                    ],
                ],
            ],
            [
                'title' => 'Pengabdian',
                'icon' => 'users-group',
                'roles' => ['dosen', 'kepala lppm', 'admin lppm', 'rektor', 'dekan', 'kaprodi'],
                'children' => [
                    [
                        'title' => 'Usulan',
                        'icon' => 'file-text',
                        'route' => 'community-service.proposal.index',
                    ],
                    [
                        'title' => 'Perbaikan Usulan',
                        'icon' => 'file-pencil',
                        'route' => 'community-service.proposal-revision.index',
                    ],
                    [
                        'title' => 'Laporan Akhir',
                        'icon' => 'report',
                        'route' => 'community-service.final-report.index',
                    ],
                    [
                        'title' => 'Realisasi Anggaran',
                        'icon' => 'wallet',
                        'route' => 'community-service.daily-note.index',
                    ],
                ],
            ],
            ...($kaprodiValidationActive ? [[
                'title' => 'Persetujuan Kaprodi',
                'icon' => 'shield-check',
                'route' => 'kaprodi.proposals.index',
                'roles' => ['kaprodi'],
            ]] : []),
            // Kaprodi - Monitoring Laporan Akhir
            // Vetted by AI - Manual Review Required by Senior Engineer/Manager
            [
                'title' => 'Monitoring Laporan',
                'icon' => 'eye',
                'route' => 'kaprodi.report-approval',
                'roles' => ['kaprodi'],
            ],
            // Kaprodi - Conditional Roadmap
            ...($roadmapActive ? [[
                'title' => 'Kelola Peta Jalan',
                'icon' => 'target-arrow',
                'route' => 'settings.master-data',
                'params' => ['group' => 'academic-structure', 'tab' => 'study-program-roadmaps'],
                'roles' => ['kaprodi'],
            ]] : []),
            // Dekan menu - Vetted by AI - Manual Review Required by Senior Engineer/Manager
            [
                'title' => 'Persetujuan Proposal',
                'icon' => 'shield-check',
                'route' => 'dekan.proposals.index',
                'roles' => ['dekan'],
            ],
            [
                'title' => 'Persetujuan Laporan',
                'icon' => 'clipboard-check',
                'route' => 'dekan.reports.index',
                'roles' => ['dekan'],
            ],
            // Dekan - Conditional Roadmap
            ...($roadmapActive ? [[
                'title' => 'Kelola Peta Jalan',
                'icon' => 'target-arrow',
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
                'icon' => 'shield-check',
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
                'icon' => 'clipboard-check',
                'route' => 'kepala-lppm.report-approval',
                'roles' => ['kepala lppm'],
            ],
            [
                'title' => 'Laporan Keuangan',
                'icon' => 'wallet',
                'route' => 'kepala-lppm.financial-approval',
                'roles' => ['kepala lppm', 'admin lppm'],
            ],
            ...($letteringActive ? [[
                'title' => 'Persetujuan Surat',
                'icon' => 'file-text',
                'route' => 'kepala-lppm.letter-approval',
                'roles' => ['kepala lppm'],
            ]] : []),
            // Dosen - Persuratan
            ...($letteringActive ? [[
                'title' => 'Surat',
                'icon' => 'file-text',
                'route' => 'dashboard.dosen.surat.dashboard',
                'roles' => ['dosen'],
            ]] : []),
            // Admin LPPM - Persuratan
            // Vetted by AI - Manual Review Required by Senior Engineer/Manager
            ...($letteringActive ? [[
                'title' => 'Surat',
                'icon' => 'file-text',
                'route' => 'admin-lppm.letters.dashboard',
                'roles' => ['admin lppm'],
            ]] : []),
            // Admin LPPM - Reviewer Management Group
            [
                'title' => 'Reviewer',
                'icon' => 'users',
                'roles' => ['admin lppm', 'kepala lppm', 'rektor'],
                'children' => [
                    [
                        'title' => 'Penugasan Reviewer',
                        'icon' => 'user-plus',
                        'route' => 'admin-lppm.assign-reviewers',
                        'roles' => ['admin lppm'],
                    ],
                    [
                        'title' => 'Beban Kerja Reviewer',
                        'icon' => 'chart-pie',
                        'route' => 'admin-lppm.reviewer-workload',
                        'roles' => ['admin lppm'],
                    ],
                    [
                        'title' => 'Monitoring Review',
                        'icon' => 'eye',
                        'route' => 'admin-lppm.review-monitoring',
                        'roles' => ['admin lppm', 'kepala lppm', 'rektor'],
                    ],
                    [
                        'title' => 'Log Notifikasi',
                        'icon' => 'list',
                        'route' => 'admin-lppm.notification-log',
                        'roles' => ['admin lppm', 'kepala lppm', 'rektor'],
                    ],
                ],
            ],
            [
                'title' => 'Monev',
                'icon' => 'presentation-analytics',
                'route' => 'admin-lppm.monev.index',
                'roles' => ['admin lppm'],
            ],
            // Admin LPPM - Monitoring Laporan Akhir
            // Vetted by AI - Manual Review Required by Senior Engineer/Manager
            [
                'title' => 'Monitoring Laporan',
                'icon' => 'eye',
                'route' => 'admin-lppm.report-approval',
                'roles' => ['admin lppm'],
            ],
            // Reviewer menu
            [
                'title' => 'Review Penelitian',
                'icon' => 'flask',
                'route' => 'review.research',
                'roles' => ['reviewer'],
            ],
            [
                'title' => 'Review Pengabdian',
                'icon' => 'users-group',
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
                'icon' => 'presentation-analytics',
                'route' => 'review.monev',
                'roles' => ['reviewer'],
            ],
            [
                'title' => 'IKU & Luaran',
                'icon' => 'award',
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
            // Laporan - Reports menu (+ dekan + kepala lppm for decision-making)
            [
                'title' => 'Laporan',
                'icon' => 'report',
                'roles' => ['admin lppm', 'rektor', 'kepala lppm', 'dekan'],
                'children' => [
                    [
                        'title' => 'Monitoring Laporan',
                        'icon' => 'eye',
                        'route' => 'reports.monitoring',
                    ],
                    [
                        'title' => 'Laporan Penelitian',
                        'icon' => 'flask',
                        'route' => 'reports.research',
                    ],
                    [
                        'title' => 'Laporan PKM',
                        'icon' => 'users-group',
                        'route' => 'reports.pkm',
                    ],
                    [
                        'title' => 'Laporan Luaran',
                        'icon' => 'star',
                        'route' => 'reports.outputs',
                    ],
                    [
                        'title' => 'Laporan Kerjasama Mitra',
                        'icon' => 'users-group',
                        'route' => 'reports.partners',
                    ],
                    [
                        'title' => 'Laporan IKU',
                        'icon' => 'award',
                        'route' => 'reports.iku',
                    ],
                    [
                        'title' => 'Laporan Monev',
                        'icon' => 'presentation-analytics',
                        'route' => 'reports.monev',
                    ],
                    // Vetted by AI - Manual Review Required by Senior Engineer/Manager
                    [
                        'title' => 'Laporan Reviewer',
                        'icon' => 'users',
                        'route' => 'reports.reviewer',
                        'roles' => ['admin lppm', 'superadmin', 'rektor', 'kepala lppm'],
                    ],
                    [
                        'title' => 'Rekap Monev',
                        'icon' => 'chart-bar',
                        'route' => 'kepala-lppm.monev.recap',
                        'roles' => ['kepala lppm', 'rektor'],
                    ],
                    [
                        'title' => 'Capaian Monev',
                        'icon' => 'target-arrow',
                        'route' => 'kepala-lppm.rektor.monev-dashboard',
                        'roles' => ['kepala lppm', 'rektor'],
                    ],
                ],
            ],
            // Data Arsip - admin lppm
            // Vetted by AI - Manual Review Required by Senior Engineer/Manager
            [
                'title' => 'Arsip',
                'icon' => 'archive',
                'route' => 'admin.archives',
                'roles' => ['admin lppm', 'superadmin'],
            ],
            // settings:
            // - master data - admin lppm
            // Vetted by AI - Manual Review Required by Senior Engineer/Manager
            [
                'title' => 'Pengaturan',
                'icon' => 'settings',
                'roles' => ['admin lppm', 'superadmin'],
                'children' => [
                    [
                        'title' => 'Pengguna',
                        'icon' => 'users',
                        'children' => [
                            [
                                'title' => 'Daftar Pengguna',
                                'icon' => 'users',
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
                                'route' => 'users.sync-sinta',
                            ],
                        ],
                    ],
                    [
                        'title' => 'Integrasi SINTA',
                        'icon' => 'database-export',
                        'route' => 'export-sinta',
                    ],
                    [
                        'title' => 'Divider',
                        'type' => 'divider',
                    ],
                    [
                        'title' => 'Master Data',
                        'icon' => 'folders',
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
                        'icon' => 'wallet',
                        'route' => 'settings.master-data',
                        'params' => ['group' => 'budget'],
                        'active' => ['group=budget'],
                    ],
                    [
                        'title' => 'Kemitraan',
                        'icon' => 'users-group',
                        'route' => 'settings.master-data',
                        'params' => ['group' => 'partnership'],
                        'active' => ['group=partnership'],
                    ],
                    [
                        'title' => 'Jadwal Proposal',
                        'icon' => 'calendar-event',
                        'route' => 'settings.proposal-schedule',
                    ],
                    [
                        'title' => 'Template Proposal',
                        'icon' => 'file-text',
                        'route' => 'settings.proposal-template',
                    ],
                    [
                        'title' => 'Nomor Kontrak Usulan',
                        'icon' => 'file-pencil',
                        'route' => 'settings.contracts',
                        'roles' => ['admin lppm', 'superadmin'],
                    ],
                    [
                        'title' => 'Manual Book',
                        'icon' => 'book-2',
                        'route' => 'settings.manual-books',
                        'roles' => ['admin lppm', 'superadmin'],
                    ],
                ],
            ],
        ];

        if ($activeRole === 'admin lppm') {
            $newItems = [];
            $collectedItems = [];

            $reviewMonev = [
                'title' => 'Review & Monev',
                'icon' => 'presentation-analytics',
                'roles' => [$activeRole],
                'children' => [],
            ];
            $laporanArsip = [
                'title' => 'Laporan & Arsip',
                'icon' => 'report',
                'roles' => [$activeRole],
                'children' => [],
            ];
            $sistemPengaturan = [
                'title' => 'Sistem & Pengaturan',
                'icon' => 'settings-2',
                'roles' => [$activeRole],
                'children' => [],
            ];

            $panduanItem = null;

            foreach ($items as $item) {
                if ($item['title'] === 'Panduan') {
                    $panduanItem = $item;
                } elseif (in_array($item['title'], ['Reviewer', 'Monev'])) {
                    $reviewMonev['children'][] = $item;
                } elseif (in_array($item['title'], ['Laporan', 'Arsip', 'Riwayat Persetujuan'])) {
                    $laporanArsip['children'][] = $item;
                } elseif (in_array($item['title'], ['Pengaturan', 'Kelola Peta Jalan'])) {
                    if ($item['title'] === 'Pengaturan') {
                        foreach ($item['children'] as $child) {
                            if ($child['title'] === 'Pengguna' && isset($child['children'])) {
                                foreach ($child['children'] as $subChild) {
                                    $sistemPengaturan['children'][] = $subChild;
                                }
                            } elseif ($child['title'] !== 'Divider') {
                                $sistemPengaturan['children'][] = $child;
                            }
                        }
                    } else {
                        $sistemPengaturan['children'][] = $item;
                    }
                } else {
                    $collectedItems[$item['title']] = $item;
                }
            }

            if (isset($collectedItems['Dashboard'])) {
                $newItems[] = $collectedItems['Dashboard'];
            }
            if (isset($collectedItems['Penelitian'])) {
                $newItems[] = $collectedItems['Penelitian'];
            }
            if (isset($collectedItems['Pengabdian'])) {
                $newItems[] = $collectedItems['Pengabdian'];
            }
            if (isset($collectedItems['Surat'])) {
                $newItems[] = $collectedItems['Surat'];
            }
            if (isset($collectedItems['IKU & Luaran'])) {
                $newItems[] = $collectedItems['IKU & Luaran'];
            }

            if (! empty($reviewMonev['children'])) {
                $newItems[] = $reviewMonev;
            }
            if (! empty($laporanArsip['children'])) {
                $newItems[] = $laporanArsip;
            }
            if (! empty($sistemPengaturan['children'])) {
                $newItems[] = $sistemPengaturan;
            }

            foreach ($collectedItems as $title => $item) {
                if (! in_array($title, ['Dashboard', 'Penelitian', 'Pengabdian', 'Surat', 'IKU & Luaran'])) {
                    $newItems[] = $item;
                }
            }

            if ($panduanItem) {
                $newItems[] = $panduanItem;
            }

            $items = $newItems;
        }

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

    // Vetted by AI - Manual Review Required by Senior Engineer/Manager
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
            'type' => $item['type'] ?? ($children ? 'dropend' : 'link'),
            'label' => $item['title'],
            'href' => $this->resolveHref($item),
            'prefix_icon' => $item['icon'] ?? null,
            'prefix_icon_class' => 'icon icon-2 icon-inline me-1',
            'route' => $routeName,
            'params' => $params,
            'active' => $this->isActive($item, $routeName) || $hasActiveChild,
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
