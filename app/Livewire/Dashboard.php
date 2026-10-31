<?php

namespace App\Livewire;

use App\Models\MandatoryOutput;
use App\Models\Proposal;
use App\Models\ReviewLog;
use App\Support\AcademicPeriod;
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
     * Bangun baris data per (tahun akademik, semester) untuk dashboard modern.
     *
     * Angka usulan/didanai/desk review/dana/luaran dipisah untuk penelitian
     * dan pengabdian sehingga filter skema dapat dihitung di sisi klien
     * tanpa query tambahan.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildPeriodRows($user): array
    {
        $proposals = $this->applyProposalScope(
            Proposal::query()
                ->select(['id', 'start_year', 'semester', 'detailable_type', 'status', 'sbk_value'])
                ->with('activeBudgetItems:id,proposal_id,total_price'),
            $user
        )->get();

        if ($proposals->isEmpty()) {
            return [];
        }

        $proposalIds = $proposals->pluck('id');

        $reviewedIds = ReviewLog::query()
            ->whereIn('proposal_id', $proposalIds)
            ->distinct()
            ->pluck('proposal_id')
            ->flip();

        $luaranCounts = collect();
        if (class_exists(MandatoryOutput::class)) {
            $luaranCounts = MandatoryOutput::query()
                ->join('proposal_outputs', 'proposal_outputs.id', '=', 'mandatory_outputs.proposal_output_id')
                ->whereIn('proposal_outputs.proposal_id', $proposalIds)
                ->selectRaw('proposal_outputs.proposal_id as proposal_id, count(*) as total')
                ->groupBy('proposal_outputs.proposal_id')
                ->pluck('total', 'proposal_id');
        }

        $statusDidanai = ['approved', 'completed', 'reviewed'];
        $rows = [];

        foreach ($proposals as $proposal) {
            $status = $proposal->status instanceof \BackedEnum
                ? $proposal->status->value
                : (string) $proposal->status;

            $schemeKey = $proposal->detailable_type === 'App\Models\Research' ? 'penelitian' : 'pengabdian';
            $academicYear = $proposal->academic_year ?? (string) ($proposal->start_year ?? '');
            $semester = $proposal->semester === 'genap' ? 'genap' : 'ganjil';
            $periodKey = $academicYear.'|'.$semester;

            $rows[$periodKey] ??= [
                'academic_year' => $academicYear,
                'semester' => $semester,
                'catatan' => '',
                'usulan_penelitian' => 0,
                'didanai_penelitian' => 0,
                'review_penelitian' => 0,
                'dana_penelitian' => 0.0,
                'luaran_penelitian' => 0,
                'usulan_pengabdian' => 0,
                'didanai_pengabdian' => 0,
                'review_pengabdian' => 0,
                'dana_pengabdian' => 0.0,
                'luaran_pengabdian' => 0,
            ];

            $rows[$periodKey]['usulan_'.$schemeKey] += 1;
            $rows[$periodKey]['luaran_'.$schemeKey] += (int) ($luaranCounts[$proposal->id] ?? 0);

            if (isset($reviewedIds[$proposal->id])) {
                $rows[$periodKey]['review_'.$schemeKey] += 1;
            }

            if (in_array($status, $statusDidanai, true)) {
                $rows[$periodKey]['didanai_'.$schemeKey] += 1;
                $rows[$periodKey]['dana_'.$schemeKey] += (float) ((float) ($proposal->sbk_value ?? 0) > 0
                    ? $proposal->sbk_value
                    : $proposal->activeBudgetItems->sum('total_price'));
            }
        }

        $periods = array_values($rows);

        usort($periods, fn (array $a, array $b): int => strcmp(
            $a['academic_year'].'-'.($a['semester'] === 'ganjil' ? '0' : '1'),
            $b['academic_year'].'-'.($b['semester'] === 'ganjil' ? '0' : '1')
        ));

        return $periods;
    }

    /**
     * Jumlahkan baris periode untuk skema tertentu.
     *
     * @param  array<int, array<string, mixed>>  $periods
     * @param  array<int, string>  $suffixes
     * @return array{usulan: int, didanai: int, desk_review: int, dana_m: float, luaran: int}
     */
    private function sumPeriodRows(array $periods, array $suffixes): array
    {
        $usulan = 0;
        $didanai = 0;
        $review = 0;
        $luaran = 0;
        $dana = 0.0;

        foreach ($periods as $row) {
            foreach ($suffixes as $suffix) {
                $usulan += $row['usulan_'.$suffix];
                $didanai += $row['didanai_'.$suffix];
                $review += $row['review_'.$suffix];
                $luaran += $row['luaran_'.$suffix];
                $dana += $row['dana_'.$suffix];
            }
        }

        return [
            'usulan' => $usulan,
            'didanai' => $didanai,
            'desk_review' => $review,
            'dana_m' => round($dana / 1000000, 1),
            'luaran' => $luaran,
        ];
    }

    protected function loadInfographicStats(): array
    {
        return Cache::remember('dashboard_modern_infographic_stats_'.$this->user->id, 60, function (): array {
            try {
                $periods = $this->buildPeriodRows($this->user);

                $scheme = [
                    'all' => $this->sumPeriodRows($periods, ['penelitian', 'pengabdian']),
                    'penelitian' => $this->sumPeriodRows($periods, ['penelitian']),
                    'pengabdian' => $this->sumPeriodRows($periods, ['pengabdian']),
                ];

                $academicYears = array_values(array_unique(array_map(
                    fn (array $row): string => (string) $row['academic_year'],
                    $periods
                )));
                rsort($academicYears);

                return [
                    'total_usulan' => $scheme['all']['usulan'],
                    'total_didanai' => $scheme['all']['didanai'],
                    'total_desk_review' => $scheme['all']['desk_review'],
                    'total_pkm' => $scheme['pengabdian']['usulan'],
                    'total_pkm_didanai' => $scheme['pengabdian']['didanai'],
                    'total_luaran' => $scheme['all']['luaran'],
                    'total_dana_m' => $scheme['all']['dana_m'],
                    'scheme' => $scheme,
                    'periods' => $periods,
                    'academic_years' => $academicYears,
                    'current_period' => [
                        'academic_year' => AcademicPeriod::current(),
                        'semester' => AcademicPeriod::currentSemester(),
                    ],
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
                    'periods' => [],
                    'academic_years' => [],
                    'current_period' => null,
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
