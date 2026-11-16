<?php

namespace App\Livewire\Iku;

use App\Models\Proposal;
use App\Support\AcademicPeriod;
use App\Traits\HasIkuCalculations;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'IKU'])]
class IkuDashboard extends Component
{
    use HasIkuCalculations;

    public string $period;

    public ?string $selectedIku = null;

    public function exportPdf(): void
    {
        $this->dispatch('download-file', url: route('admin.iku.export-pdf', ['period' => $this->period]));
    }

    public function previewPdf(): void
    {
        $this->dispatch('preview-pdf', url: route('admin.iku.export-pdf', ['period' => $this->period, 'preview' => true]));
    }

    public function exportExcel(): void
    {
        $this->dispatch('download-file', url: route('admin.iku.export-excel', ['period' => $this->period]));
    }

    public function mount()
    {
        $this->period = (string) request()->query('period', AcademicPeriod::current());
    }

    /**
     * Get available periods for filtering.
     */
    #[Computed]
    public function periods(): array
    {
        $periods = Proposal::query()
            ->distinct()
            ->whereNotNull('start_year')
            ->get(['start_year', 'semester']);

        $options = AcademicPeriod::optionsFromPeriods($periods);

        return $options !== [] ? $options : [AcademicPeriod::current()];
    }

    /**
     * Calculate IKU Metrics.
     */
    #[Computed]
    public function ikuMetrics(): array
    {
        return $this->getIkuMetrics($this->period);
    }

    /**
     * Get details for the selected IKU.
     */
    #[Computed]
    public function selectedMetricDetails(): array
    {
        if (! $this->selectedIku) {
            return [];
        }

        return $this->getIkuDetails($this->selectedIku, $this->period);
    }

    public function selectIku(?string $ikuCode)
    {
        $this->selectedIku = $ikuCode;
    }

    public function setPeriod(string $period)
    {
        $this->period = $period;
    }

    public function render()
    {
        return view('livewire.iku.iku-dashboard', [
            'pageTitle' => 'IKU',
            'pageSubtitle' => 'Monitoring Indikator Kinerja Utama sesuai Keputusan Menteri Nomor 358/M/KEP/2025.',
        ]);
    }
}
