<?php

namespace App\Livewire\Dashboard;

use App\Livewire\Concerns\HasToast;
use App\Models\AdditionalOutput;
use App\Models\CommunityServiceScheme;
use App\Models\MandatoryOutput;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\ProposalOutput;
use App\Models\ResearchScheme;
use App\Services\SintaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Dashboard Dosen', 'pageTitle' => 'Ruang Peneliti', 'pageSubtitle' => 'Kelola usulan, publikasi, dan kolaborasi riset Anda'])]
class DosenDashboard extends Component
{
    use HasToast;

    public $user;

    public $roleName;

    public $stats = [];

    public $chartData = [];

    // Vetted by AI - Manual Review Required by Senior Engineer/Manager
    public $processStats = [];

    // Edit Metrics State
    public $showEditMetricsModal = false;

    public $sinta_score_v3_overall;

    public $scopus_h_index;

    public $gs_h_index;

    public $wos_h_index;

    public $gender;

    // Zero Trust: Pastikan proses save mencakup validasi tipe data numerik

    public $recentResearch = [];

    public $recentCommunityService = [];

    public $selectedYear;

    public $availableYears = [];

    public function mount(): void
    {
        $this->user = Auth::user()->load('identity');
        $this->roleName = active_role();
        $this->selectedYear = date('Y');
        $this->availableYears = $this->getAvailableYears();

        $this->loadAnalytics();
    }

    public function updatedSelectedYear(): void
    {
        $this->loadAnalytics();
    }

    private function getAvailableYears(): array
    {
        // Use start_year (tahun pelaksanaan) as filter basis, not created_at
        // Vetted by AI - Manual Review Required by Senior Engineer/Manager
        $years = Proposal::query()
            ->distinct()
            ->whereNotNull('start_year')
            ->orderBy('start_year', 'desc')
            ->pluck('start_year')
            ->map(fn ($y) => (string) $y)
            ->toArray();

        if (empty($years)) {
            $years = [(string) date('Y')];
        }

        return $years;
    }

    public function loadAnalytics(): void
    {
        // Vetted by AI - Manual Review Required by Senior Engineer/Manager
        $yearFilter = $this->selectedYear;

        // OPTIMIZED: Single aggregated query for own proposals stats
        $this->loadStats($yearFilter);

        // Load as member counts (2 separate queries needed due to relationship)
        $this->loadMemberStats($yearFilter);

        // Load process monitoring stats
        $this->loadProcessStats($yearFilter);

        // Load recent proposals
        $this->loadRecentProposals($yearFilter);

        // Load historical chart data
        $this->loadChartData();

        // Dispatch updated chart event for Alpine.js reactive listener
        $this->dispatch('chart-updated', trendChart: $this->chartData);
    }

    /**
     * Load historical chart data for the last 5 years.
     * Vetted by AI - Manual Review Required by Senior Engineer/Manager
     */
    private function loadChartData(): void
    {
        // Vetted by AI - Manual Review Required by Senior Engineer/Manager
        $currentYear = (int) date('Y');
        $startYear = $currentYear - 4; // Last 5 years
        $years = range($startYear, $currentYear);

        $proposalsData = Proposal::query()
            ->where('submitter_id', $this->user->id)
            ->where('start_year', '>=', $startYear)
            ->select([
                'start_year as year',
                'status',
                DB::raw('COUNT(*) as count'),
            ])
            ->groupBy('start_year', 'status')
            ->get();

        $usulanData = [];
        $didanaiData = [];

        foreach ($years as $year) {
            // Total Usulan in this year
            $usulanCount = $proposalsData->filter(fn ($p) => (int) $p->getAttribute('year') === $year)->sum('count');
            $usulanData[] = $usulanCount;

            // Didanai (status: approved or completed) in this year
            $didanaiCount = $proposalsData->filter(fn ($p) => (int) $p->getAttribute('year') === $year && in_array($p->status->value ?? '', ['approved', 'completed']))->sum('count');
            $didanaiData[] = $didanaiCount;
        }

        $this->chartData = [
            'labels' => array_map('strval', $years),
            'datasets' => [
                [
                    'label' => 'Usulan',
                    'data' => $usulanData,
                    'borderColor' => '#206bc4',
                    'backgroundColor' => 'rgba(32, 107, 196, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Didanai',
                    'data' => $didanaiData,
                    'borderColor' => '#2fb344',
                    'backgroundColor' => 'rgba(47, 179, 68, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
        ];
    }

    /**
     * Load all stats in a single aggregated query.
     * Replaces 6 separate count queries with 1 grouped query.
     */
    private function loadStats(string $yearFilter): void
    {
        // Vetted by AI - Manual Review Required by Senior Engineer/Manager
        $statsRaw = Proposal::query()
            ->where('submitter_id', $this->user->id)
            ->where('start_year', $yearFilter)
            ->select([
                'detailable_type',
                'status',
                DB::raw('COUNT(*) as count'),
            ])
            ->groupBy('detailable_type', 'status')
            ->get();

        $research = $statsRaw->filter(fn ($r) => str_contains($r->detailable_type ?? '', 'Research'));
        $communityService = $statsRaw->filter(fn ($r) => str_contains($r->detailable_type ?? '', 'CommunityService'));

        $this->stats = [
            'my_research' => $research->sum('count'),
            'my_community_service' => $communityService->sum('count'),
            'research_pending' => $research->filter(fn ($r) => ($r->status->value ?? '') === 'submitted')->sum('count'),
            'community_service_pending' => $communityService->filter(fn ($r) => ($r->status->value ?? '') === 'submitted')->sum('count'),
            'research_approved' => $research->filter(fn ($r) => in_array($r->status->value ?? '', ['approved', 'completed']))->sum('count'),
            'community_service_approved' => $communityService->filter(fn ($r) => in_array($r->status->value ?? '', ['approved', 'completed']))->sum('count'),
            'research_schemes_count' => ResearchScheme::count(),
            'community_service_schemes_count' => CommunityServiceScheme::count(),
        ];
    }

    /**
     * Load member stats in optimized queries.
     * Uses raw query to avoid pivot column conflicts with GROUP BY.
     */
    private function loadMemberStats(string $yearFilter): void
    {
        // Use raw query builder to avoid pivot columns being auto-included
        // Vetted by AI - Manual Review Required by Senior Engineer/Manager
        $memberStats = DB::table('proposals')
            ->join('proposal_user', 'proposals.id', '=', 'proposal_user.proposal_id')
            ->where('proposal_user.user_id', $this->user->id)
            ->where('proposal_user.role', '!=', 'ketua') // Hanya hitung jika sebagai Anggota
            ->where('proposal_user.status', 'accepted') // Hanya hitung yang sudah dikonfirmasi
            ->where('proposals.start_year', $yearFilter)
            ->select([
                'proposals.detailable_type',
                DB::raw('COUNT(*) as count'),
            ])
            ->groupBy('proposals.detailable_type')
            ->get();

        $this->stats['research_as_member'] = $memberStats
            ->filter(fn ($r) => str_contains($r->detailable_type ?? '', 'Research'))
            ->sum('count');

        $this->stats['community_service_as_member'] = $memberStats
            ->filter(fn ($r) => str_contains($r->detailable_type ?? '', 'CommunityService'))
            ->sum('count');
    }

    /**
     * Load recent proposals in a single query.
     */
    /**
     * Load recent proposals ordered by updated_at descending.
     * Vetted by AI - Manual Review Required by Senior Engineer/Manager
     */
    private function loadRecentProposals(string $yearFilter): void
    {
        // Vetted by AI - Manual Review Required by Senior Engineer/Manager
        $recentProposals = Proposal::query()
            ->with(['submitter.identity', 'researchScheme', 'communityServiceScheme'])
            ->where('submitter_id', $this->user->id)
            ->where('start_year', $yearFilter)
            ->latest('updated_at')
            ->limit(20)
            ->get();

        $this->recentResearch = $recentProposals
            ->filter(fn ($p) => str_contains($p->detailable_type, 'Research'))
            ->take(10)
            ->values();

        $this->recentCommunityService = $recentProposals
            ->filter(fn ($p) => str_contains($p->detailable_type, 'CommunityService'))
            ->take(10)
            ->values();
    }

    /**
     * Load process stats (review progress, monev progress, outputs achieved).
     * Vetted by AI - Manual Review Required by Senior Engineer/Manager
     */
    private function loadProcessStats(string $yearFilter): void
    {
        // Vetted by AI - Manual Review Required by Senior Engineer/Manager
        $proposalsThisYear = Proposal::where('submitter_id', $this->user->id)
            ->where('start_year', $yearFilter)
            ->get();
        $proposalsThisYearIds = $proposalsThisYear->pluck('id');

        // 1. Review Status based on proposal_reviewer table
        $totalReview = DB::table('proposal_reviewer')
            ->whereIn('proposal_id', $proposalsThisYearIds)
            ->count();

        $completedReview = DB::table('proposal_reviewer')
            ->whereIn('proposal_id', $proposalsThisYearIds)
            ->where('status', 'completed')
            ->count();

        // 2 & 3. activeProposals: Only funded proposals (approved/completed) require Monev, Reports, and Outputs
        $activeProposals = $proposalsThisYear->filter(function ($p) {
            return in_array($p->status->value, ['approved', 'completed']);
        });
        $activeProposalIds = $activeProposals->pluck('id');

        // 2. Monev Status
        $totalMonev = $activeProposals->count();

        $monevReviewCompleted = DB::table('monev_reviews')
            ->whereIn('proposal_id', $activeProposalIds)
            ->whereNotNull('reviewed_at')
            ->distinct()
            ->count('proposal_id');

        $monevReviewAny = DB::table('monev_reviews')
            ->whereIn('proposal_id', $activeProposalIds)
            ->distinct()
            ->count('proposal_id');

        $monevLegacy = DB::table('proposal_monevs')
            ->whereIn('proposal_id', $activeProposalIds)
            ->distinct()
            ->count('proposal_id');

        $completedMonev = max($monevReviewCompleted, $monevReviewAny, $monevLegacy);

        // 3. Output Tracking (Luaran)
        $targetOutputs = ProposalOutput::whereIn('proposal_id', $activeProposalIds)->count();

        $progressReportIds = ProgressReport::whereIn('proposal_id', $activeProposalIds)->pluck('id');
        $achievedViaReport = MandatoryOutput::whereIn('progress_report_id', $progressReportIds)->count()
            + AdditionalOutput::whereIn('progress_report_id', $progressReportIds)->count();

        $targetOutputIds = ProposalOutput::whereIn('proposal_id', $activeProposalIds)->pluck('id');
        $achievedViaOutput = MandatoryOutput::whereIn('proposal_output_id', $targetOutputIds)->count()
            + AdditionalOutput::whereIn('proposal_output_id', $targetOutputIds)->count();

        $achievedOutputs = max($achievedViaReport, $achievedViaOutput);

        $this->processStats = [
            'review_total' => $totalReview,
            'review_completed' => $completedReview,
            'review_progress' => $totalReview > 0 ? ($completedReview / $totalReview) * 100 : 0,

            'monev_total' => $totalMonev,
            'monev_completed' => $completedMonev,
            'monev_progress' => $totalMonev > 0 ? ($completedMonev / $totalMonev) * 100 : 0,

            'output_target' => $targetOutputs,
            'output_achieved' => $achievedOutputs,
            'output_progress' => $targetOutputs > 0 ? min(100, ($achievedOutputs / $targetOutputs) * 100) : 0,
        ];
    }

    public function syncSinta(SintaService $sintaService): void
    {
        $result = $sintaService->syncAuthorProfile($this->user);

        if ($result['success']) {
            $this->toastSuccess($result['message']);
            $this->user->refresh();
        } else {
            $this->toastError($result['message']);
        }
    }

    public function openEditMetricsModal(): void
    {
        $identity = $this->user->identity;
        $this->sinta_score_v3_overall = $identity->sinta_score_v3_overall ?? 0;
        $this->scopus_h_index = $identity->scopus_h_index;
        $this->gs_h_index = $identity->gs_h_index;
        $this->wos_h_index = $identity->wos_h_index;
        $this->gender = $identity->gender;

        $this->showEditMetricsModal = true;
    }

    public function saveMetrics(): void
    {
        $this->validate([
            'sinta_score_v3_overall' => 'required|numeric|min:0',
            'scopus_h_index' => 'nullable|numeric|min:0',
            'gs_h_index' => 'nullable|numeric|min:0',
            'wos_h_index' => 'nullable|numeric|min:0',
            'gender' => 'nullable|in:L,P',
        ]);

        $identity = $this->user->identity;
        if ($identity) {
            $identity->update([
                'sinta_score_v3_overall' => $this->sinta_score_v3_overall,
                'scopus_h_index' => $this->scopus_h_index,
                'gs_h_index' => $this->gs_h_index,
                'wos_h_index' => $this->wos_h_index,
                'gender' => $this->gender,
            ]);
            $this->toastSuccess('Metrik publikasi berhasil diperbarui secara manual.');
            $this->showEditMetricsModal = false;
            $this->user->refresh();
        } else {
            $this->toastError('Harap lengkapi Profil Identitas Dasar Anda terlebih dahulu.');
        }
    }

    public function render()
    {
        return view('livewire.dashboard.dosen-dashboard');
    }
}
