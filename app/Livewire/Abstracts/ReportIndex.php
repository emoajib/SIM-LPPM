<?php

declare(strict_types=1);

namespace App\Livewire\Abstracts;

use App\Livewire\Traits\ReportAuthorization;
use App\Livewire\Traits\ReportData;
use App\Livewire\Traits\ReportFilters;
use App\Models\CommunityServiceScheme;
use App\Models\ResearchScheme;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

// Vetted by AI - Manual Review Required by Senior Engineer/Manager
abstract class ReportIndex extends Component
{
    use ReportAuthorization;
    use ReportData;
    use ReportFilters;

    abstract protected function getDetailableType(): string;

    abstract protected function getStatusFilter(): array;

    abstract protected function getViewName(): string;

    #[Computed]
    public function proposals(): Collection
    {
        $query = $this->buildProposalQuery(
            $this->getDetailableType(),
            $this->getStatusFilter()
        );

        $query = $this->eagerLoadRelations($query, $this->getRelations());

        $query = $this->applySearchFilter($query, $this->search);

        $query = $this->applyYearFilter($query, $this->selectedYear);

        $query = $this->applySchemeFilter($query, $this->getDetailableType(), $this->schemeFilter);

        $query = $this->applyStatusFilter($query, $this->statusFilter);

        return $query->latest()->get();
    }

    #[Computed]
    public function statistics(): array
    {
        return $this->getReportStatistics(
            $this->getDetailableType(),
            $this->getStatusFilter()
        );
    }

    #[Computed]
    public function availableSchemes(): Collection
    {
        if (str_contains($this->getDetailableType(), 'CommunityService')) {
            return CommunityServiceScheme::orderBy('name')->get();
        }

        return ResearchScheme::orderBy('name')->get();
    }

    public function filterByStatus(string $status): void
    {
        $this->statusFilter = $status;
    }

    #[Computed]
    public function availableYears()
    {
        return $this->getAvailableYears(
            $this->getDetailableType(),
            $this->getStatusFilter()
        );
    }

    protected function getRelations(): array
    {
        $schemeRelation = str_contains($this->getDetailableType(), 'CommunityService')
            ? 'communityServiceScheme'
            : 'researchScheme';

        $baseRelations = [
            'submitter.identity.faculty',
            'submitter.identity.studyProgram',
            'focusArea',
            $schemeRelation,
            'latestFinalReport',
            'progressReports' => function ($q) {
                if ($this->isFinalReport()) {
                    $q->finalReports()->latest();
                } else {
                    $q->latest();
                }
            },
        ];

        return $baseRelations;
    }

    protected function isFinalReport(): bool
    {
        return str_contains($this->getViewName(), 'final');
    }

    public function render()
    {
        return view($this->getViewName());
    }
}
