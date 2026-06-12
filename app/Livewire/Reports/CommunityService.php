<?php

namespace App\Livewire\Reports;

use App\Enums\ProposalStatus;
use App\Livewire\Concerns\HasToast;
use App\Livewire\Traits\WithInstitutionalApproval;
use App\Models\AdditionalOutput;
use App\Models\Faculty;
use App\Models\MandatoryOutput;
use App\Models\Proposal;
use App\Models\ResearchScheme;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Laporan Pengabdian (PKM)', 'pageTitle' => 'Laporan Pengabdian (PKM)'])]
class CommunityService extends Component
{
    use HasToast, WithInstitutionalApproval, WithPagination;

    public string $period;

    public string $search = '';

    public string $selectedScheme = 'all';

    public string $selectedFaculty = 'all';

    public string $selectedSemester = 'all';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedScheme(): void
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

        if (active_role() === 'dekan' || auth()->user()->activeHasRole('dekan')) {
            $this->selectedFaculty = (string) (auth()->user()->identity->faculty_id ?? 'all');
        }

        // Load metadata from existing report if available
        $report = $this->getInstitutionalReport('pkm', (int) $this->period);
        if ($report && $report->metadata) {
            $this->search = $report->metadata['search'] ?? '';
            $this->selectedScheme = $report->metadata['scheme'] ?? 'all';
            $this->selectedSemester = $report->metadata['semester'] ?? 'all';

            // Only override faculty if not dekan
            if (active_role() !== 'dekan' && ! auth()->user()->activeHasRole('dekan')) {
                $this->selectedFaculty = $report->metadata['faculty'] ?? 'all';
            }
        } else {
            // Check query params if no report metadata
            $this->search = request()->query('search', '');
            $this->selectedScheme = request()->query('scheme', 'all');
            $this->selectedSemester = request()->query('semester', 'all');

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
        ];

        $this->dispatch('download-file', url: route('reports.pkm.pdf', $params));
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
            'preview' => true,
        ];

        $this->dispatch('preview-pdf', url: route('reports.pkm.pdf', $params));
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
        ];

        $this->dispatch('download-file', url: route('reports.pkm.excel', $params));
    }

    protected function getBaseQuery()
    {
        return Proposal::query()
            ->where('detailable_type', 'App\Models\CommunityService')
            ->where('start_year', $this->period)
            ->when($this->selectedSemester !== 'all', fn ($q) => $q->where('semester', $this->selectedSemester))
            ->when($this->selectedScheme !== 'all', fn ($q) => $q->where('community_service_scheme_id', $this->selectedScheme))
            ->when($this->selectedFaculty !== 'all', function ($q) {
                $q->whereHas('submitter.identity', fn ($iq) => $iq->where('faculty_id', $this->selectedFaculty));
            })
            ->when($this->search, function ($q) {
                $q->where(function ($sq) {
                    $sq->where('title', 'like', "%{$this->search}%")
                        ->orWhereHas('submitter', fn ($uq) => $uq->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->with(['submitter.identity.faculty', 'submitter.identity.studyProgram', 'communityServiceScheme', 'budgetItems'])
            ->latest();
    }

    /**
     * Render the component view.
     */
    public function render(): View
    {
        return view('livewire.reports.community-service', [
            'periods' => $this->availablePeriods(),
            'summary' => $this->summaryMetrics(),
            'schemes' => $this->pkmByScheme(),
            'focusAreas' => $this->pkmByFocusArea(),
            'faculties' => $this->pkmByFaculty(),
            'outputStats' => $this->outputAnalytics(),
            'proposals' => $this->proposals(),
            'allSchemes' => ResearchScheme::orderBy('name')->get(),
            'allFaculties' => Faculty::orderBy('name')->get(),
            'institutionalReport' => $this->getInstitutionalReport('pkm', (int) $this->period),
        ]);
    }

    /**
     * Get all PKM proposals for the current period with pagination.
     */
    protected function proposals()
    {
        return $this->getBaseQuery()->paginate(15);
    }

    /**
     * Group PKM by focus area for the current period.
     */
    protected function pkmByFocusArea(): Collection
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
     * Get available years from proposals.
     */
    protected function availablePeriods(): array
    {
        return Proposal::query()
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

        $totalApproved = (clone $query)
            ->whereIn('status', [
                ProposalStatus::APPROVED->value,
                ProposalStatus::COMPLETED->value,
                ProposalStatus::WAITING_REVIEWER->value,
                ProposalStatus::UNDER_REVIEW->value,
                ProposalStatus::REVIEWED->value,
            ])
            ->count();

        $totalBudget = (clone $query)
            ->whereIn('status', [
                ProposalStatus::APPROVED->value,
                ProposalStatus::COMPLETED->value,
            ])
            ->get()
            ->sum(fn ($p) => ($p->sbk_value && $p->sbk_value > 0) ? (float) $p->sbk_value : $p->budgetItems->sum('total_price'));

        $reportsCount = (clone $query)
            ->whereHas('progressReports')
            ->count();

        return [
            [
                'label' => __('Proposal'),
                'value' => $totalApproved,
                'icon' => 'check',
                'variant' => 'bg-green-lt text-green',
            ],
            [
                'label' => __('Anggaran'),
                'value' => 'Rp '.number_format($totalBudget, 0, ',', '.'),
                'icon' => 'currency-dollar',
                'variant' => 'bg-blue-lt text-blue',
            ],
            [
                'label' => __('Laporan'),
                'value' => $reportsCount,
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
     * Group PKM by scheme for the current period.
     */
    protected function pkmByScheme(): Collection
    {
        return Proposal::query()
            ->where('detailable_type', 'App\Models\CommunityService')
            ->where('start_year', $this->period)
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
            ->with(['communityServiceScheme', 'budgetItems'])
            ->get()
            ->groupBy('community_service_scheme_id')
            ->map(function ($proposals) {
                $first = $proposals->first();

                return [
                    'name' => $first->communityServiceScheme->name ?? __('Tanpa Skema'),
                    'count' => $proposals->count(),
                    'budget' => $proposals->sum(fn ($p) => ($p->sbk_value && $p->sbk_value > 0) ? (float) $p->sbk_value :
                        $p->budgetItems->sum('total_price')),
                ];
            })
            ->sortByDesc('count');
    }

    /**
     * Group PKM by faculty for the current period.
     */
    protected function pkmByFaculty(): Collection
    {
        return Proposal::query()
            ->where('detailable_type', 'App\Models\CommunityService')
            ->where('start_year', $this->period)
            ->when($this->selectedSemester !== 'all', fn ($q) => $q->where('semester', $this->selectedSemester))
            ->when($this->selectedScheme !== 'all', fn ($q) => $q->where('community_service_scheme_id', $this->selectedScheme))
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
}
