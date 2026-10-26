<?php

namespace App\Livewire\Dekan;

use App\Models\CommunityService;
use App\Models\Proposal;
use App\Models\ProposalStatusLog;
use App\Models\Research;
use App\Support\AcademicPeriod;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

// Vetted by AI - Manual Review Required by Senior Engineer/Manager
class ApprovalHistory extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $typeFilter = 'all';

    #[Url]
    public string $decisionFilter = 'all';

    #[Url]
    public string $yearFilter = '';

    public function mount(): void
    {
        // Zero Trust RBAC: wajib peran aktif dekan
        abort_unless(Auth::user()?->activeHasRole('dekan'), 403);
    }

    public function updated(string $property): void
    {
        if (str_ends_with($property, 'Filter') || $property === 'search') {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->typeFilter = 'all';
        $this->decisionFilter = 'all';
        $this->yearFilter = '';
        $this->resetPage();
    }

    #[Computed]
    public function availableYears(): array
    {
        $periods = Proposal::whereHas('statusLogs', fn ($q) => $q->where('user_id', Auth::id()))
            ->whereNotNull('start_year')
            ->select('start_year', 'semester')
            ->distinct()
            ->get();

        return AcademicPeriod::optionsFromPeriods($periods);
    }

    #[Computed]
    public function history()
    {
        $facultyId = Auth::user()?->identity?->faculty_id;

        $query = ProposalStatusLog::where('user_id', Auth::id())
            ->whereHas('proposal', function ($query) use ($facultyId) {
                if (! $facultyId) {
                    $query->whereRaw('1 = 0');
                } else {
                    $query->whereHas('submitter.identity', function ($q) use ($facultyId) {
                        $q->where('faculty_id', $facultyId);
                    });
                }
            })
            ->with(['proposal.submitter.identity', 'proposal.detailable', 'proposal.focusArea']);

        if (! empty($this->search)) {
            $query->whereHas('proposal', function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                    ->orWhereHas('submitter', fn ($sq) => $sq->where('name', 'like', "%{$this->search}%"));
            });
        }

        if ($this->typeFilter !== 'all') {
            $detailableType = $this->typeFilter === 'research'
                ? Research::class
                : CommunityService::class;
            $query->whereHas('proposal', fn ($q) => $q->where('detailable_type', $detailableType));
        }

        if ($this->decisionFilter !== 'all') {
            $query->where('status_after', $this->decisionFilter);
        }

        if (! empty($this->yearFilter)) {
            $query->whereHas('proposal', function ($q) {
                Proposal::applyAcademicYearFilter($q, $this->yearFilter);
            });
        }

        $paginator = $query->latest('at')->paginate(15);

        // Self-healing pagination clamp
        if ($paginator->currentPage() > $paginator->lastPage() && $paginator->total() > 0) {
            $this->setPage(1);

            return $query->latest('at')->paginate(15);
        }

        return $paginator;
    }

    public function render()
    {
        return view('livewire.dekan.approval-history');
    }
}
