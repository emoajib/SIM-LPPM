<?php

namespace App\Livewire\Dashboard;

use App\Enums\InstitutionalReportStatus;
use App\Enums\ProposalStatus;
use App\Enums\ReportStatus;
use App\Models\AdditionalOutput;
use App\Models\BudgetItem;
use App\Models\CommunityServiceScheme;
use App\Models\Faculty;
use App\Models\InstitutionalReport;
use App\Models\MandatoryOutput;
use App\Models\MonevReview;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\ProposalMonev;
use App\Models\ProposalOutput;
use App\Models\ResearchScheme;
use App\Models\StudyProgram;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ExecDashboard extends Component
{
    public $user;

    public $roleName;

    public $stats = [];

    public $processStats = [];

    public $recentResearch = [];

    public $recentCommunityService = [];

    public $selectedYear;

    public $selectedSemester = 'all';

    public $selectedStatus = 'all';

    public $selectedFaculty = 'all';

    public $selectedProdi = 'all';

    public $selectedResearchScheme = 'all';

    public $selectedCommunityServiceScheme = 'all';

    public $availableYears = [];

    public $availableStatuses = [];

    public $availableFaculties = [];

    public $availableProdis = [];

    public $availableResearchSchemes = [];

    public $availableCommunityServiceSchemes = [];

    public $periodicSummary = [];

    public $focusAreasChartData = [
        'labels' => [],
        'datasets' => [],
    ];

    public $facultyPerformanceChartData = [
        'labels' => [],
        'datasets' => [],
    ];

    public $scienceClustersChartData = [
        'labels' => [],
        'datasets' => [],
    ];

    public $tktChartData = [
        'labels' => [],
        'datasets' => [],
    ];

    public $themesChartData = [
        'labels' => [],
        'datasets' => [],
    ];

    public $topicsChartData = [
        'labels' => [],
        'datasets' => [],
    ];

    public function mount(): void
    {
        $this->user = Auth::user();
        $this->roleName = active_role();
        $this->selectedYear = (int) date('Y');
        $this->availableYears = $this->getAvailableYears();
        $this->availableStatuses = ProposalStatus::filterOptions();
        $this->availableFaculties = $this->getFaculties();

        if ($this->roleName === 'dekan') {
            $facultyId = $this->user->identity?->faculty_id;
            if ($facultyId) {
                $this->selectedFaculty = (string) $facultyId;
                $this->availableProdis = $this->getProdiByFaculty();
            }
        } elseif ($this->roleName === 'kaprodi') {
            // Vetted by AI - Manual Review Required by Senior Engineer/Manager
            $studyProgram = StudyProgram::where('kaprodi_user_id', $this->user->id)->first();
            if ($studyProgram) {
                $this->selectedProdi = (string) $studyProgram->id;
                $this->selectedFaculty = (string) $studyProgram->faculty_id;
                $this->availableProdis = [$studyProgram->id => $studyProgram->name];
            }
        }

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

    public function updatedSelectedSemester(): void
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

    public function updatedSelectedResearchScheme(): void
    {
        $this->loadAnalytics();
    }

    public function updatedSelectedCommunityServiceScheme(): void
    {
        $this->loadAnalytics();
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
            ->map(fn ($y) => (int) $y)
            ->toArray();

        if (empty($years)) {
            $years = [(int) date('Y')];
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

    /**
     * Get research schemes, scoped by faculty for Dekan role.
     */
    private function getResearchSchemes(): array
    {
        $query = ResearchScheme::query()->orderBy('name');

        if ($this->isDekanRestricted()) {
            $facultyId = $this->user->identity?->faculty_id;
            if ($facultyId) {
                $query->whereHas('proposals.submitter.identity', fn ($q) => $q->where('faculty_id', $facultyId));
            }
        }

        return $query->pluck('name', 'id')
            ->prepend('Semua Skema Penelitian', 'all')
            ->toArray();
    }

    /**
     * Get community service schemes, scoped by faculty for Dekan role.
     */
    private function getCommunityServiceSchemes(): array
    {
        $query = CommunityServiceScheme::query()->orderBy('name');

        if ($this->isDekanRestricted()) {
            $facultyId = $this->user->identity?->faculty_id;
            if ($facultyId) {
                $query->whereHas('proposals.submitter.identity', fn ($q) => $q->where('faculty_id', $facultyId));
            }
        }

        return $query->pluck('name', 'id')
            ->prepend('Semua Skema PKM', 'all')
            ->toArray();
    }

    public function isDekanRestricted(): bool
    {
        return $this->roleName === 'dekan';
    }

    public function isKaprodiRestricted(): bool
    {
        return $this->roleName === 'kaprodi';
    }

    /**
     * Hitung jumlah filter lanjutan yang sedang aktif (selain filter Tahun).
     * Digunakan untuk menampilkan badge indikator di tombol "Filter Lanjutan".
     *
     * Vetted by AI - Manual Review Required by Senior Engineer/Manager
     */
    public function getActiveFilterCountProperty(): int
    {
        $count = 0;
        if ($this->selectedSemester !== 'all') {
            $count++;
        }
        if ($this->selectedStatus !== 'all') {
            $count++;
        }
        if ($this->selectedFaculty !== 'all') {
            $count++;
        }
        if ($this->selectedProdi !== 'all') {
            $count++;
        }
        if ($this->selectedResearchScheme !== 'all') {
            $count++;
        }
        if ($this->selectedCommunityServiceScheme !== 'all') {
            $count++;
        }

        return $count;
    }

    /**
     * Reset semua filter lanjutan ke nilai default.
     * Kembalikan Prodi ke 'all' dan refresh prodis list sesuai state saat ini.
     *
     * Vetted by AI - Manual Review Required by Senior Engineer/Manager
     */
    public function resetFilters(): void
    {
        $this->selectedSemester = 'all';
        $this->selectedStatus = 'all';

        if (! $this->isDekanRestricted() && ! $this->isKaprodiRestricted()) {
            $this->selectedFaculty = 'all';
            $this->selectedProdi = 'all';
            $this->availableProdis = $this->getProdiByFaculty();
        }

        $this->selectedResearchScheme = 'all';
        $this->selectedCommunityServiceScheme = 'all';
        $this->loadAnalytics();
    }

    public function loadAnalytics(): void
    {
        $yearFilter = $this->selectedYear;

        $this->loadStats($yearFilter);

        $this->loadProcessStats((string) $yearFilter);

        $this->loadRecentProposals($yearFilter);

        $this->periodicSummary = $this->getPeriodicSummary();

        $this->loadChartData($yearFilter);

        $this->dispatch('chart-updated',
            focusAreas: $this->focusAreasChartData,
            facultyPerformance: $this->facultyPerformanceChartData,
            scienceClusters: $this->scienceClustersChartData,
            tkt: $this->tktChartData,
            themes: $this->themesChartData,
            topics: $this->topicsChartData
        );
    }

    /**
     * Load chart data for focus areas and faculties.
     * Vetted by AI - Manual Review Required by Senior Engineer/Manager
     */
    private function loadChartData(int $yearFilter): void
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

        // 3. Rumpun Ilmu (Science Clusters)
        $scienceClustersQuery = Proposal::query()
            ->tap(fn ($q) => $this->applyCommonFilters($q))
            ->with('clusterLevel1')
            ->get()
            ->groupBy(function ($proposal) {
                return $proposal->clusterLevel1->name ?? 'Belum Ditentukan';
            })
            ->map(fn ($group) => $group->count());

        $scienceLabels = $scienceClustersQuery->keys()->toArray();
        $scienceCounts = $scienceClustersQuery->values()->toArray();

        $this->scienceClustersChartData = [
            'labels' => $scienceLabels,
            'datasets' => [
                [
                    'label' => 'Jumlah Proposal',
                    'data' => $scienceCounts,
                    'backgroundColor' => [
                        '#2fb344', '#ae3ec9', '#d6336c', '#f76707', '#206bc4',
                        '#f59f00', '#74b816', '#4299e1', '#4263eb', '#0ca678',
                    ],
                ],
            ],
        ];

        // 4. TKT Levels (TKT)
        $tktQuery = Proposal::query()
            ->tap(fn ($q) => $this->applyCommonFilters($q))
            ->where('detailable_type', 'App\Models\Research')
            ->with('detailable.tktLevels')
            ->get()
            ->flatMap(function ($proposal) {
                return $proposal->detailable->tktLevels ?? collect();
            })
            ->groupBy(function ($tktLevel) {
                return 'TKT '.($tktLevel->level ?? $tktLevel->name);
            })
            ->map(fn ($group) => $group->count());

        $tktLabels = $tktQuery->keys()->toArray();
        $tktCounts = $tktQuery->values()->toArray();

        $this->tktChartData = [
            'labels' => $tktLabels,
            'datasets' => [
                [
                    'label' => 'Jumlah Proposal',
                    'data' => $tktCounts,
                    'backgroundColor' => '#f76707',
                    'borderColor' => '#f76707',
                    'borderWidth' => 1,
                ],
            ],
        ];

        // Vetted by AI - Manual Review Required by Senior Engineer/Manager
        // 5. Themes (Tema)
        $themesQuery = Proposal::query()
            ->tap(fn ($q) => $this->applyCommonFilters($q))
            ->with('theme')
            ->get()
            ->groupBy(function ($proposal) {
                return $proposal->theme->name ?? 'Belum Ditentukan';
            })
            ->map(fn ($group) => $group->count());

        $themeLabels = $themesQuery->keys()->toArray();
        $themeCounts = $themesQuery->values()->toArray();

        $this->themesChartData = [
            'labels' => $themeLabels,
            'datasets' => [
                [
                    'label' => 'Jumlah Proposal',
                    'data' => $themeCounts,
                    'backgroundColor' => [
                        '#4263eb', '#f59f00', '#ae3ec9', '#2fb344', '#d6336c',
                        '#f76707', '#74b816', '#4299e1', '#206bc4', '#0ca678',
                    ],
                ],
            ],
        ];

        // 6. Topics (Topik)
        $topicsQuery = Proposal::query()
            ->tap(fn ($q) => $this->applyCommonFilters($q))
            ->with('topic')
            ->get()
            ->groupBy(function ($proposal) {
                return $proposal->topic->name ?? 'Belum Ditentukan';
            })
            ->map(fn ($group) => $group->count());

        $topicLabels = $topicsQuery->keys()->toArray();
        $topicCounts = $topicsQuery->values()->toArray();

        $this->topicsChartData = [
            'labels' => $topicLabels,
            'datasets' => [
                [
                    'label' => 'Jumlah Proposal',
                    'data' => $topicCounts,
                    'backgroundColor' => [
                        '#d6336c', '#f76707', '#4299e1', '#0ca678', '#206bc4',
                        '#f59f00', '#74b816', '#4263eb', '#2fb344', '#ae3ec9',
                    ],
                ],
            ],
        ];
    }

    private function applyCommonFilters(Builder $query): Builder
    {
        $query->where('start_year', $this->selectedYear);

        if ($this->selectedStatus !== 'all') {
            $query->where('status', $this->selectedStatus);
        }

        if ($this->isDekanRestricted()) {
            $facultyId = $this->user->identity?->faculty_id;
            if (! $facultyId) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereHas('submitter.identity', fn ($q) => $q->where('faculty_id', $facultyId));
            }
        } elseif ($this->isKaprodiRestricted()) {
            // Vetted by AI - Manual Review Required by Senior Engineer/Manager
            $studyProgram = StudyProgram::where('kaprodi_user_id', $this->user->id)->first();
            if (! $studyProgram) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereHas('submitter.identity', fn ($q) => $q->where('study_program_id', $studyProgram->id));
            }
        } else {
            if ($this->selectedFaculty !== 'all') {
                $query->whereHas('submitter.identity', fn ($q) => $q->where('faculty_id', $this->selectedFaculty));
            }

            if ($this->selectedProdi !== 'all') {
                $query->whereHas('submitter.identity', fn ($q) => $q->where('study_program_id', $this->selectedProdi));
            }
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

    private function loadStats(int $yearFilter): void
    {
        $statsRaw = Proposal::query()
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

    private function transformStats(Collection $raw): array
    {
        // Vetted by AI - Manual Review Required by Senior Engineer/Manager
        $research = $raw->filter(fn ($r) => str_contains($r->detailable_type ?? '', 'Research'));
        $communityService = $raw->filter(fn ($r) => str_contains($r->detailable_type ?? '', 'CommunityService'));

        $researchBudget = (int) BudgetItem::query()
            ->whereHas(
                'proposal',
                fn ($q) => $q
                    ->where('detailable_type', 'App\Models\Research')
                    ->whereIn('status', ['approved', 'completed'])
                    ->tap(fn ($subQ) => $this->applyCommonFilters($subQ))
            )->sum('total_price');

        $pkmBudget = (int) BudgetItem::query()
            ->whereHas(
                'proposal',
                fn ($q) => $q
                    ->where('detailable_type', 'App\Models\CommunityService')
                    ->whereIn('status', ['approved', 'completed'])
                    ->tap(fn ($subQ) => $this->applyCommonFilters($subQ))
            )->sum('total_price');

        return [
            'total_research' => $research->sum('count'),
            'total_community_service' => $communityService->sum('count'),
            'research_approved' => $research->filter(fn ($r) => in_array($r->status->value ?? '', ['approved', 'completed']))->sum('count'),
            'community_service_approved' => $communityService->filter(fn ($r) => in_array($r->status->value ?? '', ['approved', 'completed']))->sum('count'),
            'faculty_name' => $this->isDekanRestricted() ? $this->user->identity?->faculty?->name : null,
            'final_report_pending' => $this->roleName === 'rektor'
                ? InstitutionalReport::where('status', InstitutionalReportStatus::SUBMITTED)->count()
                : ProgressReport::query()
                    ->where('reporting_period', 'final')
                    ->where('status', ReportStatus::SUBMITTED)
                    ->whereYear('created_at', $this->selectedYear)
                    ->count(),
            'total_outputs' => MandatoryOutput::whereHas('progressReport', fn ($q) => $q->whereYear('created_at', $this->selectedYear))->count()
                + AdditionalOutput::whereHas('progressReport', fn ($q) => $q->whereYear('created_at', $this->selectedYear))->count(),
            'research_budget' => $researchBudget,
            'pkm_budget' => $pkmBudget,
        ];
    }

    private function loadRecentProposals(int $yearFilter): void
    {
        $baseQuery = Proposal::with(['submitter', 'researchScheme', 'communityServiceScheme']);

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

    private function getPeriodicSummary(): array
    {
        $currentYear = (int) $this->selectedYear;
        $summary = [];

        $query = Proposal::query()
            ->where('start_year', '>=', $currentYear - 4)
            ->where('start_year', '<=', $currentYear);

        if ($this->selectedStatus !== 'all') {
            $query->where('status', $this->selectedStatus);
        }

        if ($this->isDekanRestricted()) {
            $facultyId = $this->user->identity?->faculty_id;
            if (! $facultyId) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereHas('submitter.identity', fn ($q) => $q->where('faculty_id', $facultyId));
            }
        } else {
            if ($this->selectedFaculty !== 'all') {
                $query->whereHas('submitter.identity', fn ($q) => $q->where('faculty_id', $this->selectedFaculty));
            }

            if ($this->selectedProdi !== 'all') {
                $query->whereHas('submitter.identity', fn ($q) => $q->where('study_program_id', $this->selectedProdi));
            }
        }

        $data = $query->select([
            'start_year',
            'semester',
            'detailable_type',
            'status',
            DB::raw('COUNT(*) as count'),
        ])
            ->groupBy('start_year', 'semester', 'detailable_type', 'status')
            ->get();

        for ($year = $currentYear; $year >= $currentYear - 4; $year--) {
            foreach (['ganjil', 'genap', null] as $semester) {
                $yearData = $data->where('start_year', $year);

                if ($semester === null) {
                    $yearData = $yearData->whereNull('semester');
                } else {
                    $yearData = $yearData->where('semester', $semester);
                }

                $researchTotal = $yearData->filter(fn ($d) => str_contains($d->detailable_type ?? '', 'Research'))->sum('count');
                $researchApproved = $yearData->filter(fn ($d) => str_contains($d->detailable_type ?? '', 'Research') && in_array($d->status->value ?? '', ['approved', 'completed']))->sum('count');
                $pkmTotal = $yearData->filter(fn ($d) => str_contains($d->detailable_type ?? '', 'CommunityService'))->sum('count');
                $pkmApproved = $yearData->filter(fn ($d) => str_contains($d->detailable_type ?? '', 'CommunityService') && in_array($d->status->value ?? '', ['approved', 'completed']))->sum('count');

                if ($researchTotal > 0 || $pkmTotal > 0) {
                    $summary[] = [
                        'year' => $year,
                        'semester' => $semester,
                        'research_total' => $researchTotal,
                        'research_approved' => $researchApproved,
                        'pkm_total' => $pkmTotal,
                        'pkm_approved' => $pkmApproved,
                    ];
                }
            }
        }

        return $summary;
    }

    private function loadProcessStats(string $yearFilter): void
    {
        // Vetted by AI - Manual Review Required by Senior Engineer/Manager
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
        $totalReview = Proposal::whereIn('id', $proposalsThisYearIds)
            ->whereIn('status', ['reviewed', 'approved', 'rejected', 'completed'])
            ->count();

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
        $targetOutputs = ProposalOutput::whereIn('proposal_id', $activeProposalIds)->count();

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

            'total_proposals' => $proposalsThisYear->count(),
        ];
    }

    public function render()
    {
        return view('livewire.dashboard.exec-dashboard');
    }
}
