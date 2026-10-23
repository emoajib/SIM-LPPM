<?php

namespace App\Livewire\Dekan;

use App\Enums\ProposalStatus;
use App\Models\CommunityService;
use App\Models\Proposal;
use App\Models\Research;
use App\Support\AcademicPeriod;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * @property-read ?int $dekanFacultyId
 * @property-read LengthAwarePaginator $proposals
 * @property-read array $statusStats
 * @property-read array $availableYears
 * @property-read ?string $facultyName
 */
class ProposalIndex extends Component
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
        return view('livewire.dekan.proposal-index');
    }

    /**
     * Get Dekan's faculty ID for scoping proposals.
     */
    #[Computed]
    public function dekanFacultyId(): ?int
    {
        return Auth::user()?->identity?->faculty_id;
    }

    /**
     * Apply faculty scope to a query (only proposals from Dekan's faculty).
     */
    protected function applyFacultyScope($query)
    {
        $facultyId = $this->dekanFacultyId;

        if (! $facultyId) {
            $query->whereRaw('1 = 0');
        } else {
            $query->whereHas('submitter.identity', function ($q) use ($facultyId) {
                $q->where('faculty_id', $facultyId);
            });
        }

        return $query;
    }

    #[Computed]
    public function proposals()
    {
        $query = Proposal::query()
            ->where('status', ProposalStatus::SUBMITTED);

        // Apply faculty scoping: Dekan only sees proposals from their faculty
        $this->applyFacultyScope($query);

        return $query
            ->with(['submitter.identity.studyProgram', 'detailable', 'focusArea', 'researchScheme'])
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
                $query->forAcademicYear($this->yearFilter);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);
    }

    #[Computed]
    public function statusStats(): array
    {
        $facultyId = $this->dekanFacultyId;

        $baseQuery = Proposal::where('status', ProposalStatus::SUBMITTED);

        // Apply faculty scoping to stats
        if ($facultyId) {
            $baseQuery->whereHas('submitter.identity', function ($q) use ($facultyId) {
                $q->where('faculty_id', $facultyId);
            });
        }

        return [
            'all' => (clone $baseQuery)->count(),
            'research' => (clone $baseQuery)
                ->where('detailable_type', Research::class)
                ->count(),
            'community_service' => (clone $baseQuery)
                ->where('detailable_type', CommunityService::class)
                ->count(),
        ];
    }

    #[Computed]
    public function availableYears(): array
    {
        $facultyId = $this->dekanFacultyId;

        $query = Proposal::where('status', ProposalStatus::SUBMITTED);

        // Apply faculty scoping
        if ($facultyId) {
            $query->whereHas('submitter.identity', function ($q) use ($facultyId) {
                $q->where('faculty_id', $facultyId);
            });
        }

        return AcademicPeriod::optionsFromPeriods(
            $query->select('start_year', 'semester')
                ->distinct()
                ->get()
                ->filter(fn ($period) => $period->start_year !== null)
        );
    }

    /**
     * Get the faculty name for display.
     */
    #[Computed]
    public function facultyName(): ?string
    {
        return Auth::user()?->identity?->faculty?->name;
    }
}
