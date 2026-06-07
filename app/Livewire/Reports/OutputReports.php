<?php

namespace App\Livewire\Reports;

use App\Enums\ProposalStatus;
use App\Livewire\Concerns\HasToast;
use App\Livewire\Traits\WithInstitutionalApproval;
use App\Models\AdditionalOutput;
use App\Models\CommunityServiceScheme;
use App\Models\Faculty;
use App\Models\MandatoryOutput;
use App\Models\Proposal;
use App\Models\ResearchScheme;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Laporan Luaran', 'pageTitle' => 'Laporan Luaran'])]
class OutputReports extends Component
{
    use HasToast, WithInstitutionalApproval, WithPagination;

    /**
     * Active tab (research or community-service)
     */
    public string $activeTab = 'research';

    public string $period;

    public string $search = '';

    public string $selectedScheme = 'all';

    public string $selectedFaculty = 'all';

    public function mount(): void
    {
        $this->period = request()->query('period', (string) date('Y'));

        if (active_role() === 'dekan' || auth()->user()->activeHasRole('dekan')) {
            $this->selectedFaculty = (string) (auth()->user()->identity->faculty_id ?? 'all');
        }

        // Load metadata from existing report if available
        $report = $this->getInstitutionalReport('output', (int) $this->period);
        if ($report && $report->metadata) {
            $this->search = $report->metadata['search'] ?? '';
            $this->selectedScheme = $report->metadata['scheme'] ?? 'all';
            $this->activeTab = $report->metadata['activeTab'] ?? $this->activeTab;
            $this->outputType = $report->metadata['outputType'] ?? 'all';

            // Only override faculty if not dekan
            if (active_role() !== 'dekan' && ! auth()->user()->activeHasRole('dekan')) {
                $this->selectedFaculty = $report->metadata['faculty'] ?? 'all';
            }
        } else {
            // Check query params if no report metadata
            $this->search = request()->query('search', '');
            $this->selectedScheme = request()->query('scheme', 'all');

            // Only override faculty if not dekan
            if (active_role() !== 'dekan' && ! auth()->user()->activeHasRole('dekan')) {
                $this->selectedFaculty = request()->query('faculty', 'all');
            }
        }
    }

    public function updatedPeriod(): void
    {
        $this->resetPage();
    }

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

    public function resetFilters(): void
    {
        $this->search = '';
        $this->selectedScheme = 'all';

        if (active_role() === 'dekan' || auth()->user()->activeHasRole('dekan')) {
            $this->selectedFaculty = (string) (auth()->user()->identity->faculty_id ?? 'all');
        } else {
            $this->selectedFaculty = 'all';
        }

        $this->period = (string) date('Y');
        $this->resetPage();
    }

    /**
     * Output type filter (all, mandatory, additional)
     */
    public string $outputType = 'all';

    /**
     * Reset pagination when output type filter changes
     */
    public function updatingOutputType(): void
    {
        $this->resetPage();
    }

    /**
     * Set the active tab
     */
    public function setTab(string $tab): void
    {
        if (! in_array($tab, ['research', 'community-service'])) {
            return;
        }

        $this->activeTab = $tab;
        $this->resetPage();
    }

    #[On('export-pdf')]
    public function exportPdf(): void
    {
        $this->dispatch('download-file', url: route('reports.output.pdf', [
            'activeTab' => $this->activeTab,
            'period' => $this->period,
            'search' => $this->search,
            'scheme' => $this->selectedScheme,
            'faculty' => $this->selectedFaculty,
            'outputType' => $this->outputType,
        ]));
    }

    #[On('preview-pdf')]
    public function previewPdf(): void
    {
        $this->dispatch('preview-pdf', url: route('reports.output.pdf', [
            'activeTab' => $this->activeTab,
            'period' => $this->period,
            'search' => $this->search,
            'scheme' => $this->selectedScheme,
            'faculty' => $this->selectedFaculty,
            'outputType' => $this->outputType,
            'preview' => true,
        ]));
    }

    #[On('export-excel')]
    public function exportExcel(): void
    {
        $this->dispatch('download-file', url: route('reports.output.excel', [
            'activeTab' => $this->activeTab,
            'period' => $this->period,
            'search' => $this->search,
            'scheme' => $this->selectedScheme,
            'faculty' => $this->selectedFaculty,
            'outputType' => $this->outputType,
        ]));
    }

    protected function getProposalsQuery()
    {
        $detailableType = $this->activeTab === 'research' ? 'App\\Models\\Research' : 'App\\Models\\CommunityService';

        $query = Proposal::query()
            ->with([
                'submitter.identity.faculty',
                'submitter.identity.studyProgram',
                'progressReports.mandatoryOutputs.proposalOutput',
                'progressReports.additionalOutputs.proposalOutput',
            ])
            ->where('detailable_type', $detailableType)
            ->where('start_year', $this->period)
            ->where(function (Builder $query) {
                $query->whereHas('progressReports.mandatoryOutputs')
                    ->orWhereHas('progressReports.additionalOutputs');
            });

        // Apply search filter
        if ($this->search) {
            $query->where(function (Builder $q) {
                $q->where('title', 'like', "%{$this->search}%")
                    ->orWhereHas('submitter', function (Builder $u) {
                        $u->where('name', 'like', "%{$this->search}%");
                    });
            });
        }

        // Filter by scheme
        if ($this->selectedScheme !== 'all') {
            $schemeColumn = $this->activeTab === 'research' ? 'research_scheme_id' : 'community_service_scheme_id';
            $query->where($schemeColumn, $this->selectedScheme);
        }

        // Filter by faculty
        if ($this->selectedFaculty !== 'all') {
            $query->whereHas('submitter.identity', function ($q) {
                $q->where('faculty_id', $this->selectedFaculty);
            });
        }

        // Filter by output type if needed
        if ($this->outputType === 'mandatory') {
            $query->whereHas('progressReports.mandatoryOutputs');
        } elseif ($this->outputType === 'additional') {
            $query->whereHas('progressReports.additionalOutputs');
        }

        return $query->latest();
    }

    /**
     * Render the component view
     */
    public function render(): View
    {
        return view('livewire.reports.output-reports', [
            'proposals' => $this->getProposalsWithOutputs(),
            'statistics' => $this->getStatistics(),
            'periods' => $this->availablePeriods(),
            'allSchemes' => $this->activeTab === 'research'
                ? ResearchScheme::orderBy('name')->get()
                : CommunityServiceScheme::orderBy('name')->get(),
            'allFaculties' => Faculty::orderBy('name')->get(),
            'institutionalReport' => $this->getInstitutionalReport('output', (int) $this->period),
        ]);
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
     * Get proposals with their outputs based on active tab and filters
     */
    protected function getProposalsWithOutputs()
    {
        return $this->getProposalsQuery()->paginate(10);
    }

    /**
     * Get statistics for the current tab
     */
    protected function getStatistics(): array
    {
        $detailableType = $this->activeTab === 'research' ? 'App\\Models\\Research' : 'App\\Models\\CommunityService';

        $mandatoryCount = MandatoryOutput::query()
            ->whereHas('progressReport.proposal', function (Builder $query) use ($detailableType) {
                $query->where('detailable_type', $detailableType);
            })
            ->count();

        $additionalCount = AdditionalOutput::query()
            ->whereHas('progressReport.proposal', function (Builder $query) use ($detailableType) {
                $query->where('detailable_type', $detailableType);
            })
            ->count();

        $totalProposals = Proposal::query()
            ->where('detailable_type', $detailableType)
            ->where('start_year', $this->period)
            ->whereIn('status', [
                ProposalStatus::APPROVED->value,
                ProposalStatus::COMPLETED->value,
            ])
            ->count();

        return [
            'mandatory' => $mandatoryCount,
            'additional' => $additionalCount,
            'total' => $mandatoryCount + $additionalCount,
            'proposals' => $totalProposals,
        ];
    }

    /**
     * View a mandatory output in modal
     */
    public ?string $viewingMandatoryId = null;

    public function viewMandatoryOutput(string $id): void
    {
        $this->viewingMandatoryId = $id;
        $this->dispatch('open-modal', modalId: 'modalMandatoryOutput');
    }

    public function closeMandatoryModal(): void
    {
        $this->viewingMandatoryId = null;
    }

    public function mandatoryOutput(): ?MandatoryOutput
    {
        if (! $this->viewingMandatoryId) {
            return null;
        }

        return MandatoryOutput::with(['proposalOutput'])->find($this->viewingMandatoryId);
    }

    /**
     * View an additional output in modal
     */
    public ?string $viewingAdditionalId = null;

    public function viewAdditionalOutput(string $id): void
    {
        $this->viewingAdditionalId = $id;
        $this->dispatch('open-modal', modalId: 'modalAdditionalOutput');
    }

    public function closeAdditionalModal(): void
    {
        $this->viewingAdditionalId = null;
    }

    public function additionalOutput(): ?AdditionalOutput
    {
        if (! $this->viewingAdditionalId) {
            return null;
        }

        return AdditionalOutput::with(['proposalOutput'])->find($this->viewingAdditionalId);
    }

    /**
     * Get the display name for an output category
     */
    public function getOutputCategoryName(string $category): string
    {
        $categories = [
            'journal' => 'Jurnal',
            'book' => 'Buku',
            'hki' => 'HKI',
            'product' => 'Produk',
            'media' => 'Media Massa',
            'video' => 'Video',
        ];

        return $categories[$category] ?? ucfirst($category);
    }
}
