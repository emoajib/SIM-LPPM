<?php

namespace App\Livewire\Dashboard;

use App\Enums\ProposalStatus;
use App\Enums\ReportStatus;
use App\Models\AdditionalOutput;
use App\Models\CommunityServiceScheme;
use App\Models\Faculty;
use App\Models\MandatoryOutput;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\ResearchScheme;
use App\Models\StudyProgram;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class KepalaLppmDashboard extends Component
{
    public $user;

    public $roleName;

    public $stats = [];

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
        $years = Proposal::select('start_year as year')
            ->whereNotNull('start_year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
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

        // OPTIMIZED: Single aggregated query for all stats
        $this->loadStats($yearFilter);

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
     * Replaces 9+ separate count queries with 1 grouped query.
     */
    private function loadStats(string $yearFilter): void
    {
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

        $this->stats = $this->transformStats($statsRaw, $yearFilter);
    }

    /**
     * Transform raw stats query result into stats array.
     */
    private function transformStats(Collection $raw, string $yearFilter): array
    {
        $research = $raw->filter(fn ($r) => str_contains($r->detailable_type ?? '', 'Research'));
        $communityService = $raw->filter(fn ($r) => str_contains($r->detailable_type ?? '', 'CommunityService'));

        $researchPending = $research->filter(fn ($r) => ($r->status->value ?? '') === 'reviewed')->sum('count');
        $communityServicePending = $communityService->filter(fn ($r) => ($r->status->value ?? '') === 'reviewed')->sum('count');

        return [
            'total_research' => $research->sum('count'),
            'total_community_service' => $communityService->sum('count'),
            'research_pending' => $researchPending,
            'community_service_pending' => $communityServicePending,
            'research_approved' => $research->filter(fn ($r) => ($r->status->value ?? '') === 'approved')->sum('count'),
            'community_service_approved' => $communityService->filter(fn ($r) => ($r->status->value ?? '') === 'approved')->sum('count'),
            'research_completed' => $research->filter(fn ($r) => ($r->status->value ?? '') === 'completed')->sum('count'),
            'community_service_completed' => $communityService->filter(fn ($r) => ($r->status->value ?? '') === 'completed')->sum('count'),
            'pending_initial_approval' => $raw->filter(fn ($r) => ($r->status->value ?? '') === 'submitted')->sum('count'),
            'pending_final_decision' => $researchPending + $communityServicePending,
            'final_report_pending' => ProgressReport::query()
                ->where('reporting_period', 'final')
                ->where('status', ReportStatus::APPROVED_BY_DEKAN)
                ->whereYear('created_at', $yearFilter)
                ->count(),
            'total_outputs' => MandatoryOutput::whereHas('progressReport', function ($q) use ($yearFilter) {
                $q->whereYear('created_at', $yearFilter);
            })->count() +
                AdditionalOutput::whereHas('progressReport', function ($q) use ($yearFilter) {
                    $q->whereYear('created_at', $yearFilter);
                })->count(),
        ];
    }

    /**
     * Load recent proposals in a single query.
     */
    private function loadRecentProposals(string $yearFilter): void
    {
        $baseQuery = Proposal::with(['submitter', 'focusArea', 'researchScheme', 'communityServiceScheme'])
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

    public function render()
    {
        return view('livewire.dashboard.kepala-lppm-dashboard');
    }
}
