<?php

namespace App\Livewire\KepalaLppm;

use App\Enums\ProposalStatus;
use App\Models\CommunityService;
use App\Models\Proposal;
use App\Models\Research;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class InitialApproval extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $typeFilter = 'all';

    #[Url]
    public string $yearFilter = '';

    public function resetFilters(): void
    {
        $this->search = '';
        $this->typeFilter = 'all';
        $this->yearFilter = '';
        $this->resetPage();
    }

    public function render(): View
    {
        return view('livewire.kepala-lppm.initial-approval');
    }

    #[Computed]
    public function proposals()
    {
        $query = Proposal::query()
            ->where('status', ProposalStatus::APPROVED);

        return $query
            ->with(['submitter.identity', 'detailable', 'focusArea', 'researchScheme', 'communityServiceScheme'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('title', 'like', "%{$this->search}%")
                        ->orWhere('summary', 'like', "%{$this->search}%");
                });
            })
            ->when($this->typeFilter !== 'all', function ($query) {
                $detailableType = $this->typeFilter === 'research'
                    ? Research::class
                    : CommunityService::class;
                $query->where('detailable_type', $detailableType);
            })
            ->when($this->yearFilter, function ($query) {
                $query->whereYear('created_at', $this->yearFilter);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);
    }

    #[Computed]
    public function statusStats(): array
    {
        return [
            'all' => Proposal::where('status', ProposalStatus::APPROVED)->count(),
            'research' => Proposal::where('status', ProposalStatus::APPROVED)
                ->where('detailable_type', Research::class)
                ->count(),
            'community_service' => Proposal::where('status', ProposalStatus::APPROVED)
                ->where('detailable_type', CommunityService::class)
                ->count(),
        ];
    }

    #[Computed]
    public function availableYears(): array
    {
        $years = Proposal::where('status', ProposalStatus::APPROVED)
            ->selectRaw(sql_year().' as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        return $years;
    }
}
