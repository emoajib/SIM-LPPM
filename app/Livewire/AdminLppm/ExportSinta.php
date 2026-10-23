<?php

namespace App\Livewire\AdminLppm;

use App\Enums\ProposalStatus;
use App\Models\Proposal;
use App\Support\AcademicPeriod;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', [
    'title' => 'Export SINTA',
    'pageTitle' => 'Export Data ke SINTA',
    'pageSubtitle' => 'Ekspor data penelitian & PKM sesuai format upload SINTA Author Verification',
])]
class ExportSinta extends Component
{
    public string $selectedYear = '';

    public array $availableYears = [];

    public array $summary = [];

    public function mount(): void
    {
        $this->selectedYear = AcademicPeriod::current();
        $this->availableYears = $this->getYears();
        $this->loadSummary();
    }

    public function updatedSelectedYear(): void
    {
        $this->loadSummary();
    }

    private function getYears(): array
    {
        $periods = Proposal::query()
            ->withArchived()
            ->distinct()
            ->whereNotNull('start_year')
            ->whereIn('status', [ProposalStatus::APPROVED->value, ProposalStatus::COMPLETED->value])
            ->get(['start_year', 'semester']);

        $options = AcademicPeriod::optionsFromPeriods($periods);

        return $options !== [] ? $options : [AcademicPeriod::current()];
    }

    private function loadSummary(): void
    {
        $researchCount = Proposal::query()
            ->withArchived()
            ->where('detailable_type', 'App\Models\Research')
            ->whereIn('status', [ProposalStatus::APPROVED->value, ProposalStatus::COMPLETED->value])
            ->when($this->selectedYear, fn ($q) => $q->forAcademicYear($this->selectedYear))
            ->count();

        $pkmCount = Proposal::query()
            ->withArchived()
            ->where('detailable_type', 'App\Models\CommunityService')
            ->whereIn('status', [ProposalStatus::APPROVED->value, ProposalStatus::COMPLETED->value])
            ->when($this->selectedYear, fn ($q) => $q->forAcademicYear($this->selectedYear))
            ->count();

        $this->summary = [
            'research' => $researchCount,
            'pkm' => $pkmCount,
        ];
    }

    public function exportResearch()
    {
        // Redirect ke HTTP controller route untuk download yang benar (dengan nama file & ekstensi .xlsx)
        return redirect()->route('export-sinta.research', ['year' => $this->selectedYear]);
    }

    public function exportPkm()
    {
        // Redirect ke HTTP controller route untuk download yang benar (dengan nama file & ekstensi .xlsx)
        return redirect()->route('export-sinta.pkm', ['year' => $this->selectedYear]);
    }

    public function render()
    {
        return view('livewire.admin-lppm.export-sinta');
    }
}
