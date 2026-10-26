<?php

namespace App\Livewire\Review;

// Vetted by AI - Manual Review Required by Senior Engineer/Manager

use App\Models\Proposal;
use App\Models\ReviewLog;
use App\Support\AcademicPeriod;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ReviewHistory extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $roundFilter = '';

    #[Url]
    public string $recommendationFilter = '';

    #[Url]
    public string $yearFilter = '';

    public function updated(string $property): void
    {
        if (str_ends_with($property, 'Filter') || $property === 'search' || $property === 'yearFilter') {
            $this->resetPage();
        }
    }

    public function mount(): void
    {
        if (! Auth::user()->can('module_review')) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }
    }

    #[Computed]
    public function history()
    {
        $query = ReviewLog::where('user_id', Auth::id())
            ->whereNotNull('completed_at')
            ->with(['proposal.submitter', 'proposal.detailable', 'proposal.focusArea']);

        if (! empty($this->search)) {
            $query->whereHas('proposal', fn ($q) => $q->where('title', 'like', "%{$this->search}%")
                ->orWhereHas('submitter', fn ($sq) => $sq->where('name', 'like', "%{$this->search}%")));
        }

        if (! empty($this->roundFilter)) {
            $query->where('round', $this->roundFilter);
        }

        if (! empty($this->recommendationFilter)) {
            $query->where('recommendation', $this->recommendationFilter);
        }

        if (! empty($this->yearFilter)) {
            $query->whereHas('proposal', function ($q) {
                Proposal::applyAcademicYearFilter($q, $this->yearFilter);
            });
        }

        $paginator = $query->latest('completed_at')->paginate(15);
        if ($paginator->currentPage() > $paginator->lastPage() && $paginator->total() > 0) {
            $this->setPage(1);

            return $query->latest('completed_at')->paginate(15);
        }

        return $paginator;
    }

    #[Computed]
    public function historyByProposal()
    {
        $query = ReviewLog::where('user_id', Auth::id())
            ->whereNotNull('completed_at')
            ->with(['proposal.submitter', 'proposal.detailable', 'proposal.focusArea']);

        if (! empty($this->search)) {
            $query->whereHas('proposal', fn ($q) => $q->where('title', 'like', "%{$this->search}%")
                ->orWhereHas('submitter', fn ($sq) => $sq->where('name', 'like', "%{$this->search}%")));
        }

        if (! empty($this->roundFilter)) {
            $query->where('round', $this->roundFilter);
        }

        if (! empty($this->recommendationFilter)) {
            $query->where('recommendation', $this->recommendationFilter);
        }

        if (! empty($this->yearFilter)) {
            $query->whereHas('proposal', function ($q) {
                Proposal::applyAcademicYearFilter($q, $this->yearFilter);
            });
        }

        return $query->latest('completed_at')
            ->get()
            ->groupBy('proposal_id');
    }

    #[Computed]
    public function availableRounds(): array
    {
        return ReviewLog::where('user_id', Auth::id())
            ->whereNotNull('completed_at')
            ->distinct()
            ->pluck('round')
            ->sort()
            ->values()
            ->toArray();
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function availableYears(): array
    {
        $periods = Proposal::whereHas('reviewLogs', fn ($q) => $q->where('user_id', Auth::id())->whereNotNull('completed_at'))
            ->whereNotNull('start_year')
            ->select('start_year', 'semester')
            ->distinct()
            ->get();

        return AcademicPeriod::optionsFromPeriods($periods);
    }

    #[Computed]
    public function stats(): array
    {
        $baseQuery = ReviewLog::where('user_id', Auth::id())
            ->whereNotNull('completed_at');

        return [
            'total' => (clone $baseQuery)->count(),
            'approved' => (clone $baseQuery)->where('recommendation', 'approved')->count(),
            'revision_needed' => (clone $baseQuery)->where('recommendation', 'revision_needed')->count(),
            'rejected' => (clone $baseQuery)->where('recommendation', 'rejected')->count(),
        ];
    }

    public function resetFilters(): void
    {
        $this->reset(['roundFilter', 'recommendationFilter', 'search', 'yearFilter']);
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.review.review-history');
    }
}
