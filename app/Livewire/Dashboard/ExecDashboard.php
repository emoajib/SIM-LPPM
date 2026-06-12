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
use Illuminate\Support\Facades\Cache;
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

    public $chartData = [];

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
        // Vetted by AI - Manual Review Required by Senior Engineer/Manager
        $yearFilter = $this->selectedYear;
        $version = Cache::rememberForever('dashboard.cache_version', fn () => time());

        $cacheKey = "dashboard.exec.v{$version}.".auth()->id().'.'.$this->roleName.'.'.md5(serialize([
            $yearFilter,
            $this->selectedStatus,
            $this->selectedFaculty,
            $this->selectedProdi,
            $this->selectedSemester,
            $this->selectedResearchScheme,
            $this->selectedCommunityServiceScheme,
        ]));

        $this->loadTrendChartData();

        $data = Cache::remember($cacheKey, 180, function () use ($yearFilter) {
            $this->loadStats($yearFilter);
            $this->loadProcessStats((string) $yearFilter);
            $this->loadRecentProposals($yearFilter);
            $periodicSummary = $this->getPeriodicSummary();
            $this->loadChartData($yearFilter);

            return [
                'stats' => $this->stats,
                'processStats' => $this->processStats,
                'recentResearch' => $this->recentResearch,
                'recentCommunityService' => $this->recentCommunityService,
                'periodicSummary' => $periodicSummary,
                'focusAreasChartData' => $this->focusAreasChartData,
                'facultyPerformanceChartData' => $this->facultyPerformanceChartData,
                'scienceClustersChartData' => $this->scienceClustersChartData,
                'tktChartData' => $this->tktChartData,
                'themesChartData' => $this->themesChartData,
                'topicsChartData' => $this->topicsChartData,
                'chartData' => $this->chartData,
            ];
        });

        $this->stats = $data['stats'];
        $this->processStats = $data['processStats'];
        $this->recentResearch = $data['recentResearch'];
        $this->recentCommunityService = $data['recentCommunityService'];
        $this->periodicSummary = $data['periodicSummary'];
        $this->focusAreasChartData = $data['focusAreasChartData'];
        $this->facultyPerformanceChartData = $data['facultyPerformanceChartData'];
        $this->scienceClustersChartData = $data['scienceClustersChartData'];
        $this->tktChartData = $data['tktChartData'];
        $this->themesChartData = $data['themesChartData'];
        $this->topicsChartData = $data['topicsChartData'];
        $this->chartData = $data['chartData'] ?? $this->chartData;

        $this->dispatch('chart-updated',
            focusAreas: $this->focusAreasChartData,
            facultyPerformance: $this->facultyPerformanceChartData,
            scienceClusters: $this->scienceClustersChartData,
            tkt: $this->tktChartData,
            themes: $this->themesChartData,
            topics: $this->topicsChartData,
            trendChart: $this->chartData
        );
    }

    /**
     * Load chart data for focus areas and faculties.
     * Vetted by AI - Manual Review Required by Senior Engineer/Manager
     */
    private function loadChartData(int $yearFilter): void
    {
        // Fetch filtered proposals eager loading relations
        $proposals = Proposal::query()
            ->tap(fn ($q) => $this->applyCommonFilters($q))
            ->with(['focusArea', 'submitter.identity.faculty', 'clusterLevel1', 'theme', 'topic'])
            ->get();

        // 1. Focus Areas (Pohon Penelitian)
        $focusResearch = $proposals->filter(fn ($p) => str_contains($p->detailable_type, 'Research'))
            ->groupBy(fn ($p) => $p->focusArea->name ?? 'Belum Ditentukan')
            ->map(fn ($g) => $g->count());
        $focusPkm = $proposals->filter(fn ($p) => str_contains($p->detailable_type, 'CommunityService'))
            ->groupBy(fn ($p) => $p->focusArea->name ?? 'Belum Ditentukan')
            ->map(fn ($g) => $g->count());

        $focusLabels = $proposals->groupBy(fn ($p) => $p->focusArea->name ?? 'Belum Ditentukan')->keys()->toArray();
        if (empty($focusLabels)) {
            $focusLabels = ['Belum Ditentukan'];
        }

        $focusResearchCounts = [];
        $focusPkmCounts = [];
        foreach ($focusLabels as $label) {
            $focusResearchCounts[] = $focusResearch->get($label, 0);
            $focusPkmCounts[] = $focusPkm->get($label, 0);
        }

        $this->focusAreasChartData = [
            'labels' => $focusLabels,
            'datasets' => [
                [
                    'label' => 'Penelitian',
                    'data' => $focusResearchCounts,
                    'backgroundColor' => '#206bc4',
                ],
                [
                    'label' => 'Pengabdian (PKM)',
                    'data' => $focusPkmCounts,
                    'backgroundColor' => '#2fb344',
                ],
            ],
        ];

        // 2. Faculty Performance (Bar Chart)
        $facultyResearch = $proposals->filter(fn ($p) => str_contains($p->detailable_type, 'Research'))
            ->groupBy(fn ($p) => $p->submitter->identity->faculty->name ?? 'Lainnya')
            ->map(fn ($g) => $g->count());
        $facultyPkm = $proposals->filter(fn ($p) => str_contains($p->detailable_type, 'CommunityService'))
            ->groupBy(fn ($p) => $p->submitter->identity->faculty->name ?? 'Lainnya')
            ->map(fn ($g) => $g->count());

        $facultyLabels = $proposals->groupBy(fn ($p) => $p->submitter->identity->faculty->name ?? 'Lainnya')->keys()->toArray();
        if (empty($facultyLabels)) {
            $facultyLabels = ['Lainnya'];
        }

        $facultyResearchCounts = [];
        $facultyPkmCounts = [];
        foreach ($facultyLabels as $label) {
            $facultyResearchCounts[] = $facultyResearch->get($label, 0);
            $facultyPkmCounts[] = $facultyPkm->get($label, 0);
        }

        $this->facultyPerformanceChartData = [
            'labels' => $facultyLabels,
            'datasets' => [
                [
                    'label' => 'Penelitian',
                    'data' => $facultyResearchCounts,
                    'backgroundColor' => '#206bc4',
                ],
                [
                    'label' => 'Pengabdian (PKM)',
                    'data' => $facultyPkmCounts,
                    'backgroundColor' => '#2fb344',
                ],
            ],
        ];

        // 3. Rumpun Ilmu (Science Clusters)
        $scienceResearch = $proposals->filter(fn ($p) => str_contains($p->detailable_type, 'Research'))
            ->groupBy(fn ($p) => $p->clusterLevel1->name ?? 'Belum Ditentukan')
            ->map(fn ($g) => $g->count());
        $sciencePkm = $proposals->filter(fn ($p) => str_contains($p->detailable_type, 'CommunityService'))
            ->groupBy(fn ($p) => $p->clusterLevel1->name ?? 'Belum Ditentukan')
            ->map(fn ($g) => $g->count());

        $scienceLabels = $proposals->groupBy(fn ($p) => $p->clusterLevel1->name ?? 'Belum Ditentukan')->keys()->toArray();
        if (empty($scienceLabels)) {
            $scienceLabels = ['Belum Ditentukan'];
        }

        $scienceResearchCounts = [];
        $sciencePkmCounts = [];
        foreach ($scienceLabels as $label) {
            $scienceResearchCounts[] = $scienceResearch->get($label, 0);
            $sciencePkmCounts[] = $sciencePkm->get($label, 0);
        }

        $this->scienceClustersChartData = [
            'labels' => $scienceLabels,
            'datasets' => [
                [
                    'label' => 'Penelitian',
                    'data' => $scienceResearchCounts,
                    'backgroundColor' => '#206bc4',
                ],
                [
                    'label' => 'Pengabdian (PKM)',
                    'data' => $sciencePkmCounts,
                    'backgroundColor' => '#2fb344',
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
                    'label' => 'Penelitian',
                    'data' => $tktCounts,
                    'backgroundColor' => '#f76707',
                    'borderColor' => '#f76707',
                    'borderWidth' => 1,
                ],
            ],
        ];

        // 5. Themes (Tema)
        $themesResearch = $proposals->filter(fn ($p) => str_contains($p->detailable_type, 'Research'))
            ->groupBy(fn ($p) => $p->theme->name ?? 'Belum Ditentukan')
            ->map(fn ($g) => $g->count());
        $themesPkm = $proposals->filter(fn ($p) => str_contains($p->detailable_type, 'CommunityService'))
            ->groupBy(fn ($p) => $p->theme->name ?? 'Belum Ditentukan')
            ->map(fn ($g) => $g->count());

        $themeLabels = $proposals->groupBy(fn ($p) => $p->theme->name ?? 'Belum Ditentukan')->keys()->toArray();
        if (empty($themeLabels)) {
            $themeLabels = ['Belum Ditentukan'];
        }

        $themeResearchCounts = [];
        $themePkmCounts = [];
        foreach ($themeLabels as $label) {
            $themeResearchCounts[] = $themesResearch->get($label, 0);
            $themePkmCounts[] = $themesPkm->get($label, 0);
        }

        $this->themesChartData = [
            'labels' => $themeLabels,
            'datasets' => [
                [
                    'label' => 'Penelitian',
                    'data' => $themeResearchCounts,
                    'backgroundColor' => '#206bc4',
                ],
                [
                    'label' => 'Pengabdian (PKM)',
                    'data' => $themePkmCounts,
                    'backgroundColor' => '#2fb344',
                ],
            ],
        ];

        // 6. Topics (Topik)
        $topicsResearch = $proposals->filter(fn ($p) => str_contains($p->detailable_type, 'Research'))
            ->groupBy(fn ($p) => $p->topic->name ?? 'Belum Ditentukan')
            ->map(fn ($g) => $g->count());
        $topicsPkm = $proposals->filter(fn ($p) => str_contains($p->detailable_type, 'CommunityService'))
            ->groupBy(fn ($p) => $p->topic->name ?? 'Belum Ditentukan')
            ->map(fn ($g) => $g->count());

        $topicLabels = $proposals->groupBy(fn ($p) => $p->topic->name ?? 'Belum Ditentukan')->keys()->toArray();
        if (empty($topicLabels)) {
            $topicLabels = ['Belum Ditentukan'];
        }

        $topicResearchCounts = [];
        $topicPkmCounts = [];
        foreach ($topicLabels as $label) {
            $topicResearchCounts[] = $topicsResearch->get($label, 0);
            $topicPkmCounts[] = $topicsPkm->get($label, 0);
        }

        $this->topicsChartData = [
            'labels' => $topicLabels,
            'datasets' => [
                [
                    'label' => 'Penelitian',
                    'data' => $topicResearchCounts,
                    'backgroundColor' => '#206bc4',
                ],
                [
                    'label' => 'Pengabdian (PKM)',
                    'data' => $topicPkmCounts,
                    'backgroundColor' => '#2fb344',
                ],
            ],
        ];
    }

    private function loadTrendChartData(): void
    {
        $currentYear = (int) date('Y');
        $startYear = $currentYear - 4;
        $years = range($startYear, $currentYear);

        $proposalsData = Proposal::query()
            ->tap(fn ($q) => $this->applyCommonFilters($q))
            ->whereYear('start_year', '>=', $startYear)
            ->select([
                DB::raw(sql_year('start_year').' as year'),
                'status',
                DB::raw('COUNT(*) as count'),
            ])
            ->groupBy('year', 'status')
            ->get();

        $usulanData = [];
        $didanaiData = [];
        foreach ($years as $year) {
            $usulanData[] = $proposalsData->filter(fn ($p) => (int) $p->getAttribute('year') === $year)->sum('count');
            $didanaiData[] = $proposalsData->filter(fn ($p) => (int) $p->getAttribute('year') === $year && ($p->status->value ?? '') === 'approved')->sum('count');
        }

        $this->chartData = [
            'labels' => array_map('strval', $years),
            'datasets' => [
                ['label' => 'Usulan', 'data' => $usulanData, 'borderColor' => '#206bc4', 'backgroundColor' => 'rgba(32, 107, 196, 0.1)', 'fill' => true, 'tension' => 0.4],
                ['label' => 'Didanai', 'data' => $didanaiData, 'borderColor' => '#2fb344', 'backgroundColor' => 'rgba(47, 179, 68, 0.1)', 'fill' => true, 'tension' => 0.4],
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
