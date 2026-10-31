<?php

namespace App\Livewire;

use App\Models\MandatoryOutput;
use App\Models\Proposal;
use App\Models\ReviewLog;
use Illuminate\Database\Eloquent\Builder;
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

    public string $dashboardTheme = 'classic';

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

    /**
     * Role yang boleh melihat data seluruh institusi.
     */
    private const INSTITUTION_WIDE_ROLES = [
        'superadmin',
        'admin lppm',
        'admin lppm saintek',
        'admin lppm dekabita',
        'kepala lppm',
        'rektor',
        'dekan',
    ];

    /**
     * Terapkan scoping proposal sesuai role aktif pengguna.
     *
     * - Role institusi : tanpa filter (seluruh data).
     * - Kaprodi        : proposal sendiri/tim + seluruh proposal program studinya.
     * - Reviewer       : proposal yang ditugaskan untuk direview.
     * - Dosen          : proposal sendiri atau sebagai anggota tim.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    private function applyProposalScope($query, $user)
    {
        if ($user->activeHasAnyRole(self::INSTITUTION_WIDE_ROLES)) {
            return $query;
        }

        $studyProgramId = $user->activeHasRole('kaprodi')
            ? $user->identity?->study_program_id
            : null;

        return $query->where(function ($q) use ($user, $studyProgramId) {
            $q->where('submitter_id', $user->id)
                ->orWhereHas('teamMembers', fn ($t) => $t->where('user_id', $user->id))
                ->orWhereHas('reviewers', fn ($r) => $r->where('user_id', $user->id));

            if ($studyProgramId) {
                $q->orWhereHas('submitter.identity', fn ($si) => $si->where('study_program_id', $studyProgramId));
            }
        });
    }

    /**
     * Hitung jumlah proposal yang sudah melewati desk review (punya ReviewLog).
     */
    private function countReviewedProposals(string $detailableType, $user): int
    {
        $query = ReviewLog::query()->whereHas(
            'proposal',
            fn ($q) => $q->where('detailable_type', $detailableType)
        );

        if (! $user->activeHasAnyRole(self::INSTITUTION_WIDE_ROLES)) {
            $query->whereHas('proposal', function ($pq) use ($user) {
                $this->applyProposalScope($pq, $user);
            });
        }

        return $query->distinct()->count('proposal_id');
    }

    protected function loadInfographicStats(): array
    {
        return Cache::remember('dashboard_modern_infographic_stats_'.$this->user->id, 60, function (): array {
            try {
                $user = $this->user;
                $isLeader = $user->activeHasAnyRole(self::INSTITUTION_WIDE_ROLES);

                $statusDidanai = ['approved', 'completed', 'reviewed'];

                $research = $this->applyProposalScope(
                    Proposal::where('detailable_type', 'App\Models\Research'),
                    $user
                );
                $pkm = $this->applyProposalScope(
                    Proposal::where('detailable_type', 'App\Models\CommunityService'),
                    $user
                );

                $researchUsulan = (clone $research)->count();
                $researchDidanai = (clone $research)->whereIn('status', $statusDidanai)->count();
                $researchReview = $this->countReviewedProposals('App\Models\Research', $user);
                $researchDana = (clone $research)->whereIn('status', $statusDidanai)
                    ->get()->sum(fn ($p) => $p->approved_budget_total);

                $pkmUsulan = (clone $pkm)->count();
                $pkmDidanai = (clone $pkm)->whereIn('status', $statusDidanai)->count();
                $pkmReview = $this->countReviewedProposals('App\Models\CommunityService', $user);
                $pkmDana = (clone $pkm)->whereIn('status', $statusDidanai)
                    ->get()->sum(fn ($p) => $p->approved_budget_total);

                $luaranResearch = 0;
                $luaranPkm = 0;
                if (class_exists(MandatoryOutput::class)) {
                    $luaranBase = MandatoryOutput::query();
                    if (! $isLeader) {
                        $luaranBase->whereHas('proposalOutput.proposal', function ($pq) use ($user) {
                            $this->applyProposalScope($pq, $user);
                        });
                    }
                    $luaranResearch = (clone $luaranBase)
                        ->whereHas('proposalOutput.proposal', fn ($q) => $q->where('detailable_type', 'App\Models\Research'))
                        ->count();
                    $luaranPkm = (clone $luaranBase)
                        ->whereHas('proposalOutput.proposal', fn ($q) => $q->where('detailable_type', 'App\Models\CommunityService'))
                        ->count();
                }

                $totalUsulan = $researchUsulan + $pkmUsulan;
                $totalDidanai = $researchDidanai + $pkmDidanai;
                $totalDeskReview = $researchReview + $pkmReview;
                $totalLuaran = $luaranResearch + $luaranPkm;
                $totalDana = $researchDana + $pkmDana;

                $scheme = [
                    'all' => [
                        'usulan' => $totalUsulan,
                        'didanai' => $totalDidanai,
                        'desk_review' => $totalDeskReview,
                        'dana_m' => round($totalDana / 1000000, 1),
                        'luaran' => $totalLuaran,
                    ],
                    'penelitian' => [
                        'usulan' => $researchUsulan,
                        'didanai' => $researchDidanai,
                        'desk_review' => $researchReview,
                        'dana_m' => round($researchDana / 1000000, 1),
                        'luaran' => $luaranResearch,
                    ],
                    'pengabdian' => [
                        'usulan' => $pkmUsulan,
                        'didanai' => $pkmDidanai,
                        'desk_review' => $pkmReview,
                        'dana_m' => round($pkmDana / 1000000, 1),
                        'luaran' => $luaranPkm,
                    ],
                ];

                return [
                    'total_usulan' => $totalUsulan,
                    'total_didanai' => $totalDidanai,
                    'total_desk_review' => $totalDeskReview,
                    'total_pkm' => $pkmUsulan,
                    'total_pkm_didanai' => $pkmDidanai,
                    'total_luaran' => $totalLuaran,
                    'total_dana_m' => round($totalDana / 1000000, 1),
                    'scheme' => $scheme,
                ];
            } catch (\Throwable $e) {
                $emptyScheme = [
                    'usulan' => 0,
                    'didanai' => 0,
                    'desk_review' => 0,
                    'dana_m' => 0,
                    'luaran' => 0,
                ];

                return [
                    'total_usulan' => 0,
                    'total_didanai' => 0,
                    'total_desk_review' => 0,
                    'total_pkm' => 0,
                    'total_pkm_didanai' => 0,
                    'total_luaran' => 0,
                    'total_dana_m' => 0,
                    'scheme' => [
                        'all' => $emptyScheme,
                        'penelitian' => $emptyScheme,
                        'pengabdian' => $emptyScheme,
                    ],
                ];
            }
        });
    }

    protected function loadTrendData(): array
    {
        try {
            $user = $this->user;

            $query = $this->applyProposalScope(
                Proposal::query()
                    ->select(['id', 'start_year', 'detailable_type', 'status', 'sbk_value'])
                    ->with('activeBudgetItems:id,proposal_id,total_price'),
                $user
            );

            $rows = $query->get();

            $years = range((int) date('Y') - 5, (int) date('Y'));
            $data = [];
            foreach ($years as $y) {
                $yr = $rows->filter(fn ($r) => (int) $r->start_year === $y);
                $usulanPenelitian = $yr->filter(fn ($r) => str_contains($r->detailable_type ?? '', 'Research'))->count();
                $didanaiPenelitian = $yr->filter(fn ($r) => str_contains($r->detailable_type ?? '', 'Research') && in_array($r->status instanceof \BackedEnum ? $r->status->value : $r->status, ['approved', 'completed', 'reviewed']))->count();
                $usulanPengabdian = $yr->filter(fn ($r) => str_contains($r->detailable_type ?? '', 'CommunityService'))->count();
                $didanaiPengabdian = $yr->filter(fn ($r) => str_contains($r->detailable_type ?? '', 'CommunityService') && in_array($r->status instanceof \BackedEnum ? $r->status->value : $r->status, ['approved', 'completed', 'reviewed']))->count();
                $danaPenelitian = $yr->filter(fn ($r) => str_contains($r->detailable_type ?? '', 'Research') && in_array($r->status instanceof \BackedEnum ? $r->status->value : $r->status, ['approved', 'completed', 'reviewed']))
                    ->sum(fn ($r) => $r->approved_budget_total);
                $danaPengabdian = $yr->filter(fn ($r) => str_contains($r->detailable_type ?? '', 'CommunityService') && in_array($r->status instanceof \BackedEnum ? $r->status->value : $r->status, ['approved', 'completed', 'reviewed']))
                    ->sum(fn ($r) => $r->approved_budget_total);
                $data[] = [
                    'year' => (string) $y,
                    'usulanPenelitian' => $usulanPenelitian,
                    'didanaiPenelitian' => $didanaiPenelitian,
                    'usulanPengabdian' => $usulanPengabdian,
                    'didanaiPengabdian' => $didanaiPengabdian,
                    'danaPenelitianJuta' => round($danaPenelitian / 1000000, 1),
                    'danaPengabdianJuta' => round($danaPengabdian / 1000000, 1),
                    'danaTotalJuta' => round(($danaPenelitian + $danaPengabdian) / 1000000, 1),
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
            $query = $this->applyProposalScope(
                Proposal::with(['submitter', 'detailable']),
                $this->user
            )->latest()->take(6);

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

        $isModernDashboard = $this->dashboardTheme === 'modern';

        return view('livewire.dashboard.main-dashboard', [
            'pageTitle' => $isModernDashboard ? null : $titles['title'],
            'pageSubtitle' => $isModernDashboard ? null : ($titles['subtitle'] ?? null),
            'infographicStats' => $infographicStats,
            'recentProposals' => $recentProposals,
            'trendData' => $trendData,
        ])->layoutData([
            'pageTitle' => $isModernDashboard ? null : $titles['title'],
            'pageSubtitle' => $isModernDashboard ? null : ($titles['subtitle'] ?? null),
        ]);
    }
}
