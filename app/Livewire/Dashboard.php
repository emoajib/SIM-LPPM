<?php

namespace App\Livewire;

use App\Models\MandatoryOutput;
use App\Models\Proposal;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Dashboard extends Component
{
    public $user;

    public $roleName;

    public $dashboardComponent;

    public string $dashboardTheme = 'modern';

    public function mount()
    {
        $this->user = Auth::user();
        $this->roleName = active_role();
        $this->dashboardTheme = session('sim_lppm_app_theme', session('sim_lppm_dashboard_theme', request()->cookie('sim_lppm_dashboard_theme', 'modern')));
        if (request()->has('theme') && in_array(request('theme'), ['dark', 'light'])) {
            session(['sim_lppm_color_mode' => request('theme')]);
        }

        $this->dashboardComponent = match ($this->roleName) {
            'superadmin', 'admin lppm', 'admin lppm saintek', 'admin lppm dekabita' => 'dashboard.admin-dashboard',
            'kepala lppm' => 'dashboard.kepala-lppm-dashboard',
            'dosen' => 'dashboard.dosen-dashboard',
            'reviewer' => 'dashboard.reviewer-dashboard',
            'rektor', 'dekan', 'kaprodi' => 'dashboard.exec-dashboard',
            default => 'dashboard.default-dashboard',
        };
    }

    public function setDashboardTheme(string $theme)
    {
        $this->dashboardTheme = in_array($theme, ['classic', 'modern']) ? $theme : 'classic';
        session(['sim_lppm_dashboard_theme' => $this->dashboardTheme]);
        session(['sim_lppm_app_theme' => $this->dashboardTheme]);
        cookie()->queue(cookie('sim_lppm_dashboard_theme', $this->dashboardTheme, 60 * 24 * 365));
        cookie()->queue(cookie('sim_lppm_app_theme', $this->dashboardTheme, 60 * 24 * 365));
    }

    protected function loadInfographicStats(): array
    {
        return Cache::remember('dashboard_modern_infographic_stats', 60, function () {
            try {
                $totalUsulan = Proposal::where('detailable_type', 'App\Models\Research')->count();
                $totalDidanai = Proposal::where('detailable_type', 'App\Models\Research')
                    ->whereIn('status', ['approved', 'completed', 'reviewed'])
                    ->count();

                $totalPkm = Proposal::where('detailable_type', 'App\Models\CommunityService')->count();
                $totalPkmDidanai = Proposal::where('detailable_type', 'App\Models\CommunityService')
                    ->whereIn('status', ['approved', 'completed', 'reviewed'])
                    ->count();

                $totalLuaran = class_exists(MandatoryOutput::class)
                    ? MandatoryOutput::count()
                    : 0;

                return [
                    'total_usulan' => $totalUsulan,
                    'total_didanai' => $totalDidanai,
                    'total_pkm' => $totalPkm,
                    'total_pkm_didanai' => $totalPkmDidanai,
                    'total_luaran' => $totalLuaran,
                ];
            } catch (\Throwable $e) {
                return [
                    'total_usulan' => 0,
                    'total_didanai' => 0,
                    'total_pkm' => 0,
                    'total_pkm_didanai' => 0,
                    'total_luaran' => 0,
                ];
            }
        });
    }

    protected function loadTrendData(): array
    {
        try {
            $rows = Proposal::query()
                ->select(['start_year', 'detailable_type', 'status'])
                ->get();

            $years = range((int) date('Y') - 5, (int) date('Y'));
            $data = [];
            foreach ($years as $y) {
                $yr = $rows->filter(fn ($r) => (int) $r->start_year === $y);
                $usulanPenelitian = $yr->filter(fn ($r) => str_contains($r->detailable_type ?? '', 'Research'))->count();
                $didanaiPenelitian = $yr->filter(fn ($r) => str_contains($r->detailable_type ?? '', 'Research') && in_array($r->status?->value ?? $r->status, ['approved', 'completed', 'reviewed']))->count();
                $usulanPengabdian = $yr->filter(fn ($r) => str_contains($r->detailable_type ?? '', 'CommunityService'))->count();
                $didanaiPengabdian = $yr->filter(fn ($r) => str_contains($r->detailable_type ?? '', 'CommunityService') && in_array($r->status?->value ?? $r->status, ['approved', 'completed', 'reviewed']))->count();
                $data[] = [
                    'year' => (string) $y,
                    'usulanPenelitian' => $usulanPenelitian,
                    'didanaiPenelitian' => $didanaiPenelitian,
                    'usulanPengabdian' => $usulanPengabdian,
                    'didanaiPengabdian' => $didanaiPengabdian,
                    'danaTotalJuta' => 0,
                    'catatan' => '',
                ];
            }

            return $data;
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function loadRecentProposals()
    {
        try {
            $query = Proposal::with(['submitter', 'detailable'])
                ->latest()
                ->take(6);

            if ($this->roleName === 'dosen') {
                $query->where('submitter_id', Auth::id());
            }

            $items = $query->get();

            if ($items->isNotEmpty()) {
                return $items;
            }
        } catch (\Throwable $e) {
            // fallback
        }

        return collect(); // no fabricated dummy data
    }

    public function render()
    {
        $titles = match ($this->roleName) {
            'superadmin', 'admin lppm', 'admin lppm saintek', 'admin lppm dekabita' => [
                'title' => 'Dashboard Admin LPPM',
            ],
            'kepala lppm' => [
                'title' => 'Dashboard Kepala LPPM',
                'subtitle' => 'Monitoring dan evaluasi strategi penelitian & PKM.',
            ],
            'dosen' => [
                'title' => 'Dashboard Dosen',
                'subtitle' => 'Kelola portofolio penelitian dan pengabdian masyarakat Anda.',
            ],
            'reviewer' => [
                'title' => 'Dashboard Reviewer',
                'subtitle' => 'Pantau tugas review proposal yang sedang berjalan.',
            ],
            'rektor', 'dekan', 'kaprodi' => [
                'title' => 'Dashboard Eksekutif',
                'subtitle' => 'Laporan ringkasan institusional dan pencapaian target.',
            ],
            default => [
                'title' => 'Dashboard Utama',
                'subtitle' => 'Selamat datang di Sistem Informasi LPPM.',
            ],
        };

        $infographicStats = $this->loadInfographicStats();
        $recentProposals = $this->loadRecentProposals();
        $trendData = $this->loadTrendData();

        return view('livewire.dashboard.main-dashboard', [
            'pageTitle' => $titles['title'],
            'pageSubtitle' => $titles['subtitle'] ?? null,
            'infographicStats' => $infographicStats,
            'recentProposals' => $recentProposals,
            'trendData' => $trendData,
        ])->layoutData([
            'pageTitle' => $titles['title'],
            'pageSubtitle' => $titles['subtitle'] ?? null,
        ]);
    }
}
