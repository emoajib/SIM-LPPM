<?php

namespace App\Livewire\Dashboard;

use App\Enums\InstitutionalReportStatus;
use App\Enums\ProposalStatus;
use App\Enums\ReportStatus;
use App\Models\AdditionalOutput;
use App\Models\CommunityServiceScheme;
use App\Models\Faculty;
use App\Models\InstitutionalReport;
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

class ExecDashboard extends Component
{
    public $user;

    public $roleName;

    public $stats = [];

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

        if (! $this->isDekanRestricted()) {
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

        $this->loadRecentProposals($yearFilter);

        $this->periodicSummary = $this->getPeriodicSummary();

        $this->loadChartData($yearFilter);
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
        $research = $raw->filter(fn ($r) => str_contains($r->detailable_type ?? '', 'Research'));
        $communityService = $raw->filter(fn ($r) => str_contains($r->detailable_type ?? '', 'CommunityService'));

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

    public function render()
    {
        return view('livewire.dashboard.exec-dashboard');
    }
}
