<?php

namespace App\Livewire\Dashboard;

use App\Enums\ProposalStatus;
use App\Enums\ReportStatus;
use App\Models\AdditionalOutput;
use App\Models\BudgetItem;
use App\Models\CommunityServiceScheme;
use App\Models\Faculty;
use App\Models\MandatoryOutput;
use App\Models\MonevReview;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\ProposalMonev;
use App\Models\ProposalOutput;
use App\Models\ResearchScheme;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AdminDashboard extends Component
{
    public $user;

    public $roleName;

    public $stats = [];

    public $processStats = [];

    public $recentResearch = [];

    public $recentCommunityService = [];

    public $selectedYear;

    public $selectedStatus = 'all';

    public $selectedFaculty = 'all';

    public $selectedProdi = 'all';

    public $selectedSemester = 'all';

    public $selectedResearchScheme = 'all';

    public $selectedCommunityServiceScheme = 'all';

    public $availableYears = [];

    public $availableStatuses = [];

    public $availableFaculties = [];

    public $availableProdis = [];

    public $availableResearchSchemes = [];

    public $availableCommunityServiceSchemes = [];

    public $focusAreasChartData = [
        'labels' => [],
        'datasets' => [],
    ];

    public $facultyPerformanceChartData = [
        'labels' => [],
        'datasets' => [],
    ];

    public function mount(): void
    {
        $this->user = Auth::user();
        $this->roleName = active_role();
        $this->selectedYear = date('Y');
        $this->availableYears = $this->getAvailableYears();
        $this->availableStatuses = ProposalStatus::filterOptions();
        $this->availableFaculties = $this->getFaculties();
        $this->availableResearchSchemes = $this->getResearchSchemes();
        $this->availableCommunityServiceSchemes = $this->getCommunityServiceSchemes();

        $this->loadAnalytics();
    }

    public function updatedSelectedYear(): void
    {
        $this->loadAnalytics();
    }

    public function updatedSelectedStatus(): void
    {
        $this->loadAnalytics();
    }

    public function updatedSelectedFaculty(): void
    {
        $this->selectedProdi = 'all';
        $this->availableProdis = $this->getProdiByFaculty();
        $this->loadAnalytics();
    }

    public function updatedSelectedProdi(): void
    {
        $this->loadAnalytics();
    }

    public function updatedSelectedSemester(): void
    {
        $this->loadAnalytics();
    }

    public function updatedSelectedResearchScheme(): void
    {
        $this->loadAnalytics();
    }

    public function updatedSelectedCommunityServiceScheme(): void
    {
        $this->loadAnalytics();
    }

    /**
     * Reset semua filter ke status default.
     *
     * Vetted by AI - Manual Review Required by Senior Engineer/Manager
     */
    public function resetFilters(): void
    {
        $this->selectedSemester = 'all';
        $this->selectedStatus = 'all';
        $this->selectedFaculty = 'all';
        $this->selectedProdi = 'all';
        $this->availableProdis = $this->getProdiByFaculty();
        $this->selectedResearchScheme = 'all';
        $this->selectedCommunityServiceScheme = 'all';
        $this->loadAnalytics();
    }

    public function exportResearch(): void
    {
        $this->dispatch('download-file', url: route('admin.dashboard.export-research', [
            'period' => $this->selectedYear,
            'scheme' => $this->selectedResearchScheme,
        ]));
    }

    public function exportCommunityService(): void
    {
        $this->dispatch('download-file', url: route('admin.dashboard.export-community-service', [
            'period' => $this->selectedYear,
            'scheme' => $this->selectedCommunityServiceScheme,
        ]));
    }

    public function exportIkuPdf(): void
    {
        $this->dispatch('download-file', url: route('admin.iku.export-pdf', ['period' => $this->selectedYear]));
    }

    public function exportIkuExcel(): void
    {
        $this->dispatch('download-file', url: route('admin.iku.export-excel', ['period' => $this->selectedYear]));
    }

    private function getAvailableYears(): array
    {
        // Use start_year (tahun pelaksanaan) as filter basis, not created_at
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

    private function getFaculties(): array
    {
        return Faculty::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->prepend('Semua Fakultas', 'all')
            ->toArray();
    }

    public function getProdiByFaculty(): array
    {
        $query = StudyProgram::query()->orderBy('name');

        if ($this->selectedFaculty !== 'all') {
            $query->where('faculty_id', $this->selectedFaculty);
        }

        return $query->pluck('name', 'id')
            ->prepend('Semua Prodi', 'all')
            ->toArray();
    }

    private function getResearchSchemes(): array
    {
        return ResearchScheme::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->prepend('Semua Skema Penelitian', 'all')
            ->toArray();
    }

    private function getCommunityServiceSchemes(): array
    {
        return CommunityServiceScheme::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->prepend('Semua Skema PKM', 'all')
            ->toArray();
    }

    private function applyCommonFilters(Builder $query): Builder
    {
        if ($this->selectedStatus !== 'all') {
            $query->where('status', $this->selectedStatus);
        }

        if ($this->selectedFaculty !== 'all') {
            $query->whereHas('submitter.identity', fn ($q) => $q->where('faculty_id', $this->selectedFaculty));
        }

        if ($this->selectedProdi !== 'all') {
            $query->whereHas('submitter.identity', fn ($q) => $q->where('study_program_id', $this->selectedProdi));
        }

        if ($this->selectedSemester !== 'all') {
            $query->where('semester', $this->selectedSemester);
        }

        return $query;
    }

    /**
     * Apply type-aware scheme filters to a specific query.
     */
    private function applySchemeFilter(Builder $query, string $type): Builder
    {
        if ($type === 'research' && $this->selectedResearchScheme !== 'all') {
            $query->where('research_scheme_id', $this->selectedResearchScheme);
        }

        if ($type === 'community_service' && $this->selectedCommunityServiceScheme !== 'all') {
            $query->where('community_service_scheme_id', $this->selectedCommunityServiceScheme);
        }

        return $query;
    }

    public function loadAnalytics(): void
    {
        $yearFilter = $this->selectedYear;

        // OPTIMIZED: Single aggregated query for all stats (replaces 9 separate count queries)
        $this->loadStats($yearFilter);

        // Load process monitoring stats (Review, Monev, Reporting)
        $this->loadProcessStats($yearFilter);

        // Load recent proposals
        $this->loadRecentProposals($yearFilter);

        $this->loadChartData($yearFilter);

        $this->dispatch('chart-updated',
            focusAreas: $this->focusAreasChartData,
            facultyPerformance: $this->facultyPerformanceChartData
        );
    }

    /**
     * Load chart data for focus areas and faculties.
     * Vetted by AI - Manual Review Required by Senior Engineer/Manager
     */
    private function loadChartData(string $yearFilter): void
    {
        // 1. Focus Areas (Doughnut Chart)
        $focusAreasQuery = Proposal::query()
            ->tap(fn ($q) => $this->applyCommonFilters($q))
            ->with('focusArea')
            ->get()
            ->groupBy(function ($proposal) {
                return $proposal->focusArea->name ?? 'Belum Ditentukan';
            })
            ->map(fn ($group) => $group->count());

        $focusLabels = $focusAreasQuery->keys()->toArray();
        $focusCounts = $focusAreasQuery->values()->toArray();

        $this->focusAreasChartData = [
            'labels' => $focusLabels,
            'datasets' => [
                [
                    'label' => 'Jumlah Proposal',
                    'data' => $focusCounts,
                    'backgroundColor' => [
                        '#206bc4', '#4299e1', '#4263eb', '#ae3ec9', '#d6336c',
                        '#f76707', '#f59f00', '#74b816', '#2fb344', '#0ca678',
                    ],
                ],
            ],
        ];

        // 2. Faculty Performance (Bar Chart)
        $facultyQuery = Proposal::query()
            ->tap(fn ($q) => $this->applyCommonFilters($q))
            ->with('submitter.identity.faculty')
            ->get()
            ->groupBy(function ($proposal) {
                return $proposal->submitter->identity->faculty->name ?? 'Lainnya';
            })
            ->map(fn ($group) => $group->count());

        $facultyLabels = $facultyQuery->keys()->toArray();
        $facultyCounts = $facultyQuery->values()->toArray();

        $this->facultyPerformanceChartData = [
            'labels' => $facultyLabels,
            'datasets' => [
                [
                    'label' => 'Jumlah Proposal',
                    'data' => $facultyCounts,
                    'backgroundColor' => '#206bc4',
                    'borderColor' => '#206bc4',
                    'borderWidth' => 1,
                ],
            ],
        ];
    }

    /**
     * Load all stats in a single aggregated query.
     * Replaces 9 separate count queries with 1 grouped query.
     */
    private function loadStats(string $yearFilter): void
    {
        // Filter by start_year (tahun pelaksanaan kegiatan), bukan created_at
        $statsRaw = Proposal::query()
            ->where('start_year', $yearFilter)
            ->tap(fn ($q) => $this->applyCommonFilters($q))
            ->select([
                'detailable_type',
                'status',
                DB::raw('COUNT(*) as count'),
            ])
            ->groupBy('detailable_type', 'status')
            ->get();

        $this->stats = $this->transformStats($statsRaw);
    }

    /**
     * Transform raw stats query result into stats array.
     */
    private function transformStats(Collection $raw): array
    {
        $research = $raw->filter(fn ($r) => str_contains($r->detailable_type, 'Research'));
        $communityService = $raw->filter(fn ($r) => str_contains($r->detailable_type, 'CommunityService'));

        // Get total dosen count (single query, cached)
        $totalDosen = User::role('dosen')->count();

        // Budget from budget_items (sbk_value is always null/0, real budget lives in budget_items)
        // Filter by start_year (tahun pelaksanaan) — konsisten dengan filter utama
        $researchBudget = (int) BudgetItem::query()
            ->whereHas(
                'proposal',
                fn ($q) => $q
                    ->where('detailable_type', 'App\Models\Research')
                    ->where('start_year', $this->selectedYear)
                    ->whereIn('status', ['approved', 'completed'])
            )->sum('total_price');

        $pkmBudget = (int) BudgetItem::query()
            ->whereHas(
                'proposal',
                fn ($q) => $q
                    ->where('detailable_type', 'App\Models\CommunityService')
                    ->where('start_year', $this->selectedYear)
                    ->whereIn('status', ['approved', 'completed'])
            )->sum('total_price');

        // FIX ENUM BUG: Laravel Collection whereIn compares by ==, but status
        // is a PHP 8.1 Enum. Must use ->value to get the string for comparison.
        $researchApproved = $research->filter(fn ($r) => in_array($r->status?->value, ['approved', 'completed']))->sum('count');
        $pkmApproved = $communityService->filter(fn ($r) => in_array($r->status?->value, ['approved', 'completed']))->sum('count');
        $researchCompleted = $research->filter(fn ($r) => $r->status?->value === 'completed')->sum('count');
        $pkmCompleted = $communityService->filter(fn ($r) => $r->status?->value === 'completed')->sum('count');

        $totalResearch = $research->sum('count');
        $totalPkm = $communityService->sum('count');

        return [
            'total_research' => $totalResearch,
            'total_community_service' => $totalPkm,
            'total_proposals' => $totalResearch + $totalPkm,
            'research_pending' => $research->filter(fn ($r) => $r->status?->value === 'submitted')->sum('count'),
            'community_service_pending' => $communityService->filter(fn ($r) => $r->status?->value === 'submitted')->sum('count'),
            'research_approved' => $researchApproved,
            'community_service_approved' => $pkmApproved,
            'research_completed' => $researchCompleted,
            'community_service_completed' => $pkmCompleted,
            'research_rejected' => $research->filter(fn ($r) => $r->status?->value === 'rejected')->sum('count'),
            'community_service_rejected' => $communityService->filter(fn ($r) => $r->status?->value === 'rejected')->sum('count'),
            'research_budget' => $researchBudget,
            'pkm_budget' => $pkmBudget,
            'total_dosen' => $totalDosen,
        ];
    }

    private function loadProcessStats(string $yearFilter): void
    {
        // Baseline: Retrieve all proposals for the selected start_year
        $proposalsThisYear = Proposal::where('start_year', $yearFilter)
            ->tap(fn ($q) => $this->applyCommonFilters($q))
            ->get();
        $proposalsThisYearIds = $proposalsThisYear->pluck('id');

        // New Metrics: Draft & Approval Stages
        $totalDraft = $proposalsThisYear->filter(fn ($p) => ($p->status->value ?? '') === 'draft')->count();
        $waitingDean = $proposalsThisYear->filter(fn ($p) => ($p->status->value ?? '') === 'submitted')->count();
        $waitingLppm = $proposalsThisYear->filter(fn ($p) => in_array($p->status->value ?? '', ['approved', 'reviewed']))->count();

        // 1. Review Status
        // Total Review = Proposals that have progressed past submission (i.e. currently in review or decided)
        $totalReview = Proposal::whereIn('id', $proposalsThisYearIds)
            ->whereIn('status', ['reviewed', 'approved', 'rejected', 'completed'])
            ->count();

        // Completed Review = Proposals that have a final decision
        $completedReview = Proposal::whereIn('id', $proposalsThisYearIds)
            ->whereIn('status', ['approved', 'rejected', 'completed'])
            ->count();

        // 2 & 3. activeProposals: Only funded proposals (approved/completed) require Monev, Reports, and Outputs
        $activeProposals = $proposalsThisYear->filter(function ($p) {
            return in_array($p->status->value, ['approved', 'completed']);
        });
        $activeProposalIds = $activeProposals->pluck('id');

        // 2. Monev Status (Integrated with new MonevReview system)
        $totalMonev = $activeProposals->count();
        $completedMonev = MonevReview::whereIn('proposal_id', $activeProposalIds)
            ->whereNotNull('reviewed_at')
            ->distinct()
            ->count('proposal_id');

        // If no new reviews, fallback to legacy ProposalMonev for backward compatibility
        if ($completedMonev === 0) {
            $completedMonev = ProposalMonev::whereIn('proposal_id', $activeProposalIds)->distinct()->count('proposal_id');
        }

        // 3. Reporting Status (Progress & Final Report)
        $totalReports = $activeProposals->count();
        $submittedReports = ProgressReport::whereIn('proposal_id', $activeProposalIds)
            ->whereIn('status', [ReportStatus::SUBMITTED, ReportStatus::APPROVED, ReportStatus::APPROVED_BY_DEKAN])
            ->distinct()
            ->count('proposal_id');

        // 4. Output Tracking (Luaran)
        // Target: Total outputs promised in funded proposals
        $targetOutputs = ProposalOutput::whereIn('proposal_id', $activeProposalIds)->count();

        // Achieved: Total outputs uploaded for funded proposals (via progress reports)
        $progressReportIds = ProgressReport::whereIn('proposal_id', $activeProposalIds)->pluck('id');
        $achievedOutputs = MandatoryOutput::whereIn('progress_report_id', $progressReportIds)->count()
            + AdditionalOutput::whereIn('progress_report_id', $progressReportIds)->count();

        $this->processStats = [
            'draft_total' => $totalDraft,
            'dean_waiting' => $waitingDean,
            'lppm_waiting' => $waitingLppm,

            'review_total' => $totalReview,
            'review_completed' => $completedReview,
            'review_progress' => $totalReview > 0 ? ($completedReview / $totalReview) * 100 : 0,

            'monev_total' => $totalMonev,
            'monev_completed' => $completedMonev,
            'monev_progress' => $totalMonev > 0 ? ($completedMonev / $totalMonev) * 100 : 0,

            'report_total' => $totalReports,
            'report_submitted' => $submittedReports,
            'report_progress' => $totalReports > 0 ? ($submittedReports / $totalReports) * 100 : 0,

            'output_target' => $targetOutputs,
            'output_achieved' => $achievedOutputs,
            'output_progress' => $targetOutputs > 0 ? min(100, ($achievedOutputs / $targetOutputs) * 100) : 0,

            // Fallbacks for total proposals if needed in other view logic
            'total_proposals' => $proposalsThisYear->count(),
        ];
    }

    /**
     * Load recent proposals in a single query.
     */
    private function loadRecentProposals(string $yearFilter): void
    {
        $baseQuery = Proposal::with(['submitter.identity', 'focusArea', 'researchScheme', 'communityServiceScheme'])
            ->where('start_year', $yearFilter);

        $this->applyCommonFilters($baseQuery);

        // Research proposals with scheme filter
        $researchQuery = clone $baseQuery;
        $researchQuery->where('detailable_type', 'App\Models\Research');
        $this->applySchemeFilter($researchQuery, 'research');

        $this->recentResearch = $researchQuery->latest()
            ->take(10)
            ->get()
            ->values();

        // Community Service proposals with scheme filter
        $csQuery = clone $baseQuery;
        $csQuery->where('detailable_type', 'App\Models\CommunityService');
        $this->applySchemeFilter($csQuery, 'community_service');

        $this->recentCommunityService = $csQuery->latest()
            ->take(10)
            ->get()
            ->values();
    }

    /**
     * Sinkronisasi data dari server produksi (Hanya di LOCAL)
     */
    public function syncFromProduction(): void
    {
        if (config('app.env') !== 'local') {
            abort(403, 'Fitur ini hanya untuk environment lokal');
        }

        try {
            Artisan::call('app:sync-production', ['--force' => true]);
            $this->loadAnalytics();
            $this->dispatch('swal', title: 'Berhasil!', text: 'Data dari website berhasil ditarik ke laptop.', icon: 'success');
        } catch (\Throwable $e) {
            $this->dispatch('swal', title: 'Gagal!', text: 'Terjadi kesalahan saat sinkronisasi: '.$e->getMessage(), icon: 'error');
        }
    }

    public function render()
    {
        return view('livewire.dashboard.admin-dashboard');
    }
}
