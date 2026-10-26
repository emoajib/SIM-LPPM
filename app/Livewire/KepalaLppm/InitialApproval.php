<?php

namespace App\Livewire\KepalaLppm;

use App\Enums\ProposalStatus;
use App\Models\CommunityService;
use App\Models\CommunityServiceScheme;
use App\Models\Faculty;
use App\Models\Proposal;
use App\Models\Research;
use App\Models\ResearchScheme;
use App\Support\AcademicPeriod;
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

    #[Url(except: 'all')]
    public string $facultyFilter = 'all';

    #[Url(except: 'all')]
    public string $schemeFilter = 'all';

    // Vetted by AI - Manual Review Required by Senior Engineer/Manager
    public function updated(string $property): void
    {
        if ($property === 'typeFilter') {
            $this->schemeFilter = 'all';
        }

        if (str_ends_with($property, 'Filter') || $property === 'search') {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->typeFilter = 'all';
        $this->yearFilter = '';
        $this->facultyFilter = 'all';
        $this->schemeFilter = 'all';
        $this->resetPage();
    }

    public function render(): View
    {
        return view('livewire.kepala-lppm.initial-approval');
    }

    #[Computed]
    public function faculties()
    {
        return Faculty::orderBy('name')->get();
    }

    #[Computed]
    public function schemes()
    {
        if ($this->typeFilter === 'research') {
            return ResearchScheme::orderBy('name')->get();
        } elseif ($this->typeFilter === 'community_service') {
            return CommunityServiceScheme::orderBy('name')->get();
        }

        return collect();
    }

    #[Computed]
    public function proposals()
    {
        $query = Proposal::query()
            ->where('status', ProposalStatus::APPROVED);

        $paginator = $query
            ->with(['submitter.identity.faculty', 'submitter.identity.studyProgram', 'detailable', 'focusArea', 'researchScheme', 'communityServiceScheme'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('title', 'like', "%{$this->search}%")
                        ->orWhere('summary', 'like', "%{$this->search}%")
                        ->orWhereHas('submitter', fn ($sq) => $sq->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->typeFilter !== 'all', function ($query) {
                $detailableType = $this->typeFilter === 'research'
                    ? Research::class
                    : CommunityService::class;
                $query->where('detailable_type', $detailableType);
            })
            ->when($this->facultyFilter !== 'all', function ($query) {
                $query->whereHas('submitter.identity', fn ($iq) => $iq->where('faculty_id', $this->facultyFilter));
            })
            ->when($this->schemeFilter !== 'all' && $this->typeFilter !== 'all', function ($query) {
                $column = $this->typeFilter === 'research' ? 'research_scheme_id' : 'community_service_scheme_id';
                $query->where($column, $this->schemeFilter);
            })
            ->when($this->yearFilter, function ($query) {
                $query->forAcademicYear($this->yearFilter);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Self-healing pagination clamp
        if ($paginator->currentPage() > $paginator->lastPage() && $paginator->total() > 0) {
            $this->setPage(1);

            return $query->paginate(15);
        }

        return $paginator;
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
        $periods = Proposal::where('status', ProposalStatus::APPROVED)
            ->whereNotNull('start_year')
            ->distinct()
            ->get(['start_year', 'semester']);

        return AcademicPeriod::optionsFromPeriods($periods);
    }
}
