<?php

namespace App\Livewire\Reports;

use App\Livewire\Concerns\HasToast;
use App\Livewire\Traits\WithInstitutionalApproval;
use App\Models\Proposal;
use App\Support\AcademicPeriod;
use App\Traits\HasIkuCalculations;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Laporan Capaian IKU', 'pageTitle' => 'Laporan Capaian IKU'])]
class IkuReport extends Component
{
    use HasIkuCalculations, HasToast, WithInstitutionalApproval;

    public string $period;

    public string $search = '';

    public ?string $selectedIku = null;

    public bool $onlyPending = false;

    public function updatedSearch(): void
    {
        // No need to reset page as IKU report detail is not paginated yet in current impl
    }

    public function mount()
    {
        $this->period = request()->query('period', AcademicPeriod::current());

        // Load metadata from existing report if available
        $report = $this->getInstitutionalReport('iku', $this->institutionalYear());
        if ($report && $report->metadata) {
            $this->search = $report->metadata['search'] ?? '';
            $this->selectedIku = $report->metadata['selectedIku'] ?? null;
        } else {
            // Check query params if no report metadata
            $this->search = request()->query('search', '');
            $this->selectedIku = request()->query('selectedIku', null);
        }

        $this->selectedIku = $this->normalizeIkuCode($this->selectedIku);
    }

    public function institutionalYear(): int
    {
        return AcademicPeriod::startYear($this->period) ?? (int) date('Y');
    }

    public function setPeriod(string $period): void
    {
        $this->period = $period;
        $this->selectedIku = null;
        $this->onlyPending = false;
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->period = AcademicPeriod::current();
        $this->selectedIku = null;
        $this->onlyPending = false;
    }

    public function toggleDetails(?string $ikuCode): void
    {
        $normalized = $this->normalizeIkuCode($ikuCode);

        $this->selectedIku = $this->selectedIku === $normalized ? null : $normalized;
    }

    #[On('preview-pdf')]
    public function previewPdf(): void
    {
        $this->dispatch('preview-pdf', url: route('admin.iku.export-pdf', [
            'period' => $this->period,
            'search' => $this->search,
            'preview' => 1,
        ]));
    }

    public function render(): View
    {
        $ikuMetrics = $this->getIkuMetrics($this->period);
        $ikuDetails = $this->selectedIku ? $this->getIkuDetails($this->selectedIku, $this->period, $this->search) : [];

        $periods = AcademicPeriod::optionsFromPeriods(
            Proposal::query()->distinct()->whereNotNull('start_year')->get(['start_year', 'semester'])
        );
        if (! in_array($this->period, $periods, true)) {
            array_unshift($periods, $this->period);
        }

        return view('livewire.reports.iku-report', [
            'ikuMetrics' => $ikuMetrics,
            'ikuDetails' => $ikuDetails,
            'periods' => $periods,
            'institutionalReport' => $this->getInstitutionalReport('iku', $this->institutionalYear()),
            'period' => $this->period,
        ]);
    }
}
