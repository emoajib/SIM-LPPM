<?php

namespace App\Livewire\Reports;

use App\Enums\ProposalStatus;
use App\Enums\ReportStatus;
use App\Livewire\Concerns\HasToast;
use App\Livewire\Traits\WithInstitutionalApproval;
use App\Models\AdditionalOutput;
use App\Models\Faculty;
use App\Models\MandatoryOutput;
use App\Models\Proposal;
use App\Models\ResearchScheme;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

// Vetted by AI - Manual Review Required by Senior Engineer/Manager
abstract class AbstractInstitutionalReport extends Component
{
    use HasToast, WithInstitutionalApproval, WithPagination;

    public string $period;

    public string $search = '';

    public string $selectedScheme = 'all';

    public string $selectedFaculty = 'all';

    public string $selectedSemester = 'all';

    public string $selectedReportStatus = 'all';

    abstract protected function displayName(): string;

    abstract protected function detailableType(): string;

    abstract protected function schemeColumn(): string;

    abstract protected function schemeRelation(): string;

    abstract protected function reportType(): string;

    abstract protected function viewName(): string;

    abstract protected function pdfRoute(): string;

    abstract protected function excelRoute(): string;

    abstract protected function detailRoute(): string;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedScheme(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedReportStatus(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedFaculty(): void
    {
        if (active_role() === 'dekan' || auth()->user()->activeHasRole('dekan')) {
            $this->selectedFaculty = (string) (auth()->user()->identity->faculty_id ?? 'all');
        }
        $this->resetPage();
    }

    public function updatedSelectedSemester(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->selectedScheme = 'all';
        $this->selectedSemester = 'all';
        $this->selectedReportStatus = 'all';

        if (active_role() === 'dekan' || auth()->user()->activeHasRole('dekan')) {
            $this->selectedFaculty = (string) (auth()->user()->identity->faculty_id ?? 'all');
        } else {
            $this->selectedFaculty = 'all';
        }

        $this->period = (string) date('Y');
        $this->resetPage();
    }

    public function mount()
    {
        $this->period = request()->query('period', (string) date('Y'));
        $this->selectedReportStatus = request()->query('report_status', 'all');

        if (active_role() === 'dekan' || auth()->user()->activeHasRole('dekan')) {
            $this->selectedFaculty = (string) (auth()->user()->identity->faculty_id ?? 'all');
        }

        // Load metadata from existing report if available
        $report = $this->getInstitutionalReport($this->reportType(), (int) $this->period);
        if ($report && $report->metadata) {
            $this->search = $report->metadata['search'] ?? '';
            $this->selectedScheme = $report->metadata['scheme'] ?? 'all';
            $this->selectedSemester = $report->metadata['semester'] ?? 'all';
            $this->selectedReportStatus = $report->metadata['report_status'] ?? 'all';

            // Only override faculty if not dekan
            if (active_role() !== 'dekan' && ! auth()->user()->activeHasRole('dekan')) {
                $this->selectedFaculty = $report->metadata['faculty'] ?? 'all';
            }
        } else {
            // Check query params if no report metadata
            $this->search = request()->query('search', '');
            $this->selectedScheme = request()->query('scheme', 'all');
            $this->selectedSemester = request()->query('semester', 'all');
            $this->selectedReportStatus = request()->query('report_status', 'all');

            // Only override faculty if not dekan
            if (active_role() !== 'dekan' && ! auth()->user()->activeHasRole('dekan')) {
                $this->selectedFaculty = request()->query('faculty', 'all');
            }
        }
    }

    /**
     * Update the selected reporting period.
     */
    #[On('set-period')]
    public function setPeriod(string $period): void
    {
        $this->period = $period;
    }

    #[On('export-pdf')]
    public function exportPdf(): void
    {
        $params = [
            'period' => $this->period,
            'semester' => $this->selectedSemester,
            'search' => $this->search,
            'scheme' => $this->selectedScheme,
            'faculty' => $this->selectedFaculty,
            'report_status' => $this->selectedReportStatus,
        ];

        $this->dispatch('download-file', url: route($this->pdfRoute(), $params));
    }

    #[On('preview-pdf')]
    public function previewPdf(): void
    {
        $params = [
            'period' => $this->period,
            'semester' => $this->selectedSemester,
            'search' => $this->search,
            'scheme' => $this->selectedScheme,
            'faculty' => $this->selectedFaculty,
            'report_status' => $this->selectedReportStatus,
            'preview' => true,
        ];

        $this->dispatch('preview-pdf', url: route($this->pdfRoute(), $params));
    }

    #[On('export-excel')]
    public function exportExcel(): void
    {
        $params = [
            'period' => $this->period,
            'semester' => $this->selectedSemester,
            'search' => $this->search,
            'scheme' => $this->selectedScheme,
            'faculty' => $this->selectedFaculty,
            'report_status' => $this->selectedReportStatus,
        ];

        $this->dispatch('download-file', url: route($this->excelRoute(), $params));
    }

    /**
     * Render the component view.
     */
    public function render(): View
    {
        return view($this->viewName(), array_merge(['config' => $this->viewConfig()], [
            'periods' => $this->availablePeriods(),
            'summary' => $this->summaryMetrics(),
            'schemes' => $this->byScheme(),
            'focusAreas' => $this->byFocusArea(),
            'faculties' => $this->byFaculty(),
            'outputStats' => $this->outputAnalytics(),
            'proposals' => $this->proposals(),
            'allSchemes' => ResearchScheme::orderBy('name')->get(),
            'allFaculties' => Faculty::orderBy('name')->get(),
            'institutionalReport' => $this->getInstitutionalReport($this->reportType(), (int) $this->period),
        ]));
    }

    /**
     * Base query for all proposal aggregations with eager loading.
     */
    protected function getBaseQuery()
    {
        return Proposal::query()
            ->where('detailable_type', $this->detailableType())
            ->where('start_year', $this->period)
            ->where(function ($q) {
                $q->where('status', ProposalStatus::COMPLETED)
                    ->orWhereHas('progressReports');
            })
            ->when($this->selectedSemester !== 'all', fn ($q) => $q->where('semester', $this->selectedSemester))
            ->when($this->selectedScheme !== 'all', fn ($q) => $q->where($this->schemeColumn(), $this->selectedScheme))
            ->when($this->selectedFaculty !== 'all', function ($q) {
                $q->whereHas('submitter.identity', fn ($iq) => $iq->where('faculty_id', $this->selectedFaculty));
            })
            ->when($this->selectedReportStatus !== 'all', function ($q) {
                if ($this->selectedReportStatus === 'belum_laporan') {
                    $q->whereDoesntHave('progressReports', fn ($rq) => $rq->where('reporting_period', 'final'));
                } else {
                    $q->whereHas('progressReports', fn ($rq) => $rq->where('reporting_period', 'final')->where('status', $this->selectedReportStatus));
                }
            })
            ->when($this->search, function ($q) {
                $q->where(function ($sq) {
                    $sq->where('title', 'like', "%{$this->search}%")
                        ->orWhereHas('submitter', fn ($uq) => $uq->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->with([
                'submitter.identity.faculty',
                'submitter.identity.studyProgram',
                $this->schemeRelation(),
                'budgetItems',
                'latestFinalReport',
                'progressReports' => fn ($q) => $q->where('reporting_period', 'final')->latest(),
            ])
            ->latest();
    }

    /**
     * Get all proposals for the current period with pagination.
     */
    protected function proposals()
    {
        return $this->getBaseQuery()->paginate(15);
    }

    /**
     * Get available years from proposals.
     */
    protected function availablePeriods(): array
    {
        return Proposal::query()
            ->where('detailable_type', $this->detailableType())
            ->where(function ($q) {
                $q->where('status', ProposalStatus::COMPLETED)
                    ->orWhereHas('progressReports');
            })
            ->distinct()
            ->whereNotNull('start_year')
            ->orderBy('start_year', 'desc')
            ->pluck('start_year')
            ->map(fn ($year) => (string) $year)
            ->toArray() ?: [(string) date('Y')];
    }

    /**
     * Aggregate report metrics for the dashboard cards.
     */
    protected function summaryMetrics(): array
    {
        $query = $this->getBaseQuery();

        $totalFunded = (clone $query)->count();

        $totalBudget = (clone $query)->get()
            ->sum(fn ($p) => ($p->sbk_value && $p->sbk_value > 0) ? (float) $p->sbk_value : $p->budgetItems->sum('total_price'));

        $reportsApprovedCount = (clone $query)
            ->whereHas('progressReports', fn ($rq) => $rq->where('reporting_period', 'final')->where('status', ReportStatus::APPROVED->value))
            ->count();

        $reportsSubmittedCount = (clone $query)
            ->whereHas('progressReports', fn ($rq) => $rq->where('reporting_period', 'final'))
            ->count();

        return [
            [
                'label' => $this->reportType() === 'pkm' ? __('PKM Didanai') : __('Penelitian Didanai'),
                'value' => $totalFunded,
                'icon' => 'check',
                'variant' => 'bg-green-lt text-green',
            ],
            [
                'label' => __('Total Anggaran'),
                'value' => 'Rp '.number_format($totalBudget, 0, ',', '.'),
                'icon' => 'currency-dollar',
                'variant' => 'bg-blue-lt text-blue',
            ],
            [
                'label' => __('Laporan Masuk (Selesai)'),
                'value' => "{$reportsSubmittedCount} ({$reportsApprovedCount} Selesai)",
                'icon' => 'file-text',
                'variant' => 'bg-yellow-lt text-yellow',
            ],
        ];
    }

    /**
     * Aggregate output statistics for the current period.
     */
    protected function outputAnalytics(): Collection
    {
        $proposalIds = $this->getBaseQuery()->pluck('id');

        $mandatory = MandatoryOutput::query()
            ->whereHas('progressReport', fn ($q) => $q->whereIn('proposal_id', $proposalIds))
            ->with('proposalOutput')
            ->get();

        $additional = AdditionalOutput::query()
            ->whereHas('progressReport', fn ($q) => $q->whereIn('proposal_id', $proposalIds))
            ->with('proposalOutput')
            ->get();

        return $mandatory->concat($additional)
            ->groupBy(fn ($output) => $output->proposalOutput->category ?? 'Lainnya')
            ->map(fn ($group, $key) => [
                'category' => $this->translateCategory($key),
                'count' => $group->count(),
                'published' => $group->filter(fn ($o) => in_array($o->status_type ?? $o->status, [
                    'published',
                    'terbit',
                    'granted',
                ]))->count(),
            ])
            ->sortByDesc('count');
    }

    /**
     * Simple category translation.
     */
    protected function translateCategory(string $key): string
    {
        $categories = [
            'journal' => __('Jurnal'),
            'book' => __('Buku'),
            'hki' => __('HKI'),
            'product' => __('Produk'),
            'media' => __('Media Massa'),
            'video' => __('Video'),
        ];

        return $categories[strtolower($key)] ?? ucfirst($key);
    }

    /**
     * Group proposals by scheme for the current period.
     */
    protected function byScheme(): Collection
    {
        return Proposal::query()
            ->where('detailable_type', $this->detailableType())
            ->where('start_year', $this->period)
            ->where(function ($q) {
                $q->where('status', ProposalStatus::COMPLETED)
                    ->orWhereHas('progressReports');
            })
            ->when($this->selectedSemester !== 'all', fn ($q) => $q->where('semester', $this->selectedSemester))
            ->when($this->selectedFaculty !== 'all', function ($q) {
                $q->whereHas('submitter.identity', fn ($iq) => $iq->where('faculty_id', $this->selectedFaculty));
            })
            ->when($this->search, function ($q) {
                $q->where(function ($sq) {
                    $sq->where('title', 'like', "%{$this->search}%")
                        ->orWhereHas('submitter', fn ($uq) => $uq->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->with([$this->schemeRelation(), 'budgetItems'])
            ->get()
            ->groupBy($this->schemeColumn())
            ->map(function ($proposals) {
                $first = $proposals->first();

                return [
                    'name' => $first->{$this->schemeRelation()}->name ?? __('Tanpa Skema'),
                    'count' => $proposals->count(),
                    'budget' => $proposals->sum(fn ($p) => ($p->sbk_value && $p->sbk_value > 0) ? (float) $p->sbk_value :
                        $p->budgetItems->sum('total_price')),
                ];
            })
            ->sortByDesc('count');
    }

    /**
     * Group proposals by focus area for the current period.
     */
    protected function byFocusArea(): Collection
    {
        return $this->getBaseQuery()
            ->with('focusArea')
            ->get()
            ->groupBy('focus_area_id')
            ->map(function ($proposals) {
                $first = $proposals->first();

                return [
                    'name' => $first->focusArea->name ?? __('Lainnya'),
                    'count' => $proposals->count(),
                ];
            })
            ->sortByDesc('count');
    }

    /**
     * Group proposals by faculty for the current period.
     */
    protected function byFaculty(): Collection
    {
        return Proposal::query()
            ->where('detailable_type', $this->detailableType())
            ->where('start_year', $this->period)
            ->where(function ($q) {
                $q->where('status', ProposalStatus::COMPLETED)
                    ->orWhereHas('progressReports');
            })
            ->when($this->selectedSemester !== 'all', fn ($q) => $q->where('semester', $this->selectedSemester))
            ->when($this->selectedScheme !== 'all', fn ($q) => $q->where($this->schemeColumn(), $this->selectedScheme))
            ->when($this->search, function ($q) {
                $q->where(function ($sq) {
                    $sq->where('title', 'like', "%{$this->search}%")
                        ->orWhereHas('submitter', fn ($uq) => $uq->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->with(['submitter.identity.faculty'])
            ->get()
            ->groupBy(fn ($p) => $p->submitter->identity->faculty_id)
            ->map(function ($proposals) {
                $first = $proposals->first();

                return [
                    'name' => $first->submitter->identity->faculty->name ?? __('Pusat/Lainnya'),
                    'count' => $proposals->count(),
                ];
            })
            ->sortByDesc('count');
    }

    /**
     * Per-type configuration passed to the shared blade partial.
     */
    protected function viewConfig(): array
    {
        $isPkm = $this->reportType() === 'pkm';

        return [
            'reportType' => $this->reportType(),
            'validasiTitle' => 'Validasi Dokumen Institusi ('.$this->displayName().')',
            'rekapitulasiText' => $isPkm ? 'Rekapitulasi PKM' : 'Rekapitulasi penelitian',
            'skemaTitle' => $isPkm ? __('Distribusi Skema PKM') : __('Distribusi Skema'),
            'fokusTitle' => $isPkm ? __('Bidang Fokus PKM') : __('Bidang Fokus Utama'),
            'produktivitasTitle' => $isPkm ? __('Produktivitas Fakultas (PKM)') : __('Produktivitas Fakultas'),
            'analitikTitle' => $isPkm ? __('Analitik Luaran PKM') : __('Analitik Luaran Penelitian'),
            'daftarTitle' => $isPkm ? __('Daftar Seluruh Laporan PKM') : __('Daftar Seluruh Laporan Penelitian'),
            'unitLabel' => $this->displayName(),
            'emptyListText' => $isPkm ? __('Belum ada data laporan PKM untuk periode ini.') : __('Belum ada data laporan penelitian untuk periode ini.'),
            'pdfRoute' => $this->pdfRoute(),
            'excelRoute' => $this->excelRoute(),
            'detailRoute' => $this->detailRoute(),
            'finalReportRoute' => $isPkm ? 'community-service.final-report.show' : 'research.final-report.show',
            'schemeRelation' => $this->schemeRelation(),
        ];
    }
}
