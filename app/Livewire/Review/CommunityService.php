<?php

namespace App\Livewire\Review;

use App\Enums\ReviewStatus;
use App\Models\Proposal;
use App\Support\AcademicPeriod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

class CommunityService extends Component
{
    #[Url]
    public string $search = '';

    #[Url]
    public string $selectedYear = '';

    #[Url]
    public string $statusFilter = 'all'; // all, pending, completed

    public function mount(): void
    {
        if (! Auth::user()->can('module_review')) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }
    }

    #[On('resetFilters')]
    public function resetFilters(): void
    {
        $this->reset(['search', 'selectedYear', 'statusFilter']);
    }

    #[Computed]
    public function proposals(): Collection
    {
        $query = Proposal::where('detailable_type', 'App\Models\CommunityService')
            ->whereHas('reviewers', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->with([
                'submitter',
                'detailable',
                'reviewers' => function ($query) {
                    $query->where('user_id', Auth::id());
                },
            ]);

        if (! empty($this->search)) {
            $searchTerm = '%'.$this->search.'%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'LIKE', $searchTerm)
                    ->orWhereHas('submitter', function ($sq) use ($searchTerm) {
                        $sq->where('name', 'LIKE', $searchTerm);
                    });
            });
        }

        if (! empty($this->selectedYear)) {
            $query->forAcademicYear($this->selectedYear);
        }

        if ($this->statusFilter !== 'all') {
            $query->whereHas('reviewers', function ($q) {
                $q->where('user_id', Auth::id())
                    ->where('status', $this->statusFilter);
            });
        }

        return $query->latest()->get();
    }

    #[Computed]
    public function availableYears()
    {
        $periods = Proposal::where('detailable_type', 'App\Models\CommunityService')
            ->whereHas('reviewers', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->select('start_year', 'semester')
            ->distinct()
            ->get()
            ->filter(fn ($period) => $period->start_year !== null);

        return AcademicPeriod::optionsFromPeriods($periods);
    }

    #[Computed]
    public function statusStats(): array
    {
        $baseQuery = Proposal::where('detailable_type', 'App\Models\CommunityService')
            ->whereHas('reviewers', function ($query) {
                $query->where('user_id', Auth::id());
            });

        return [
            'all' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->whereHas('reviewers', function ($q) {
                $q->where('user_id', Auth::id())->where('status', ReviewStatus::PENDING);
            })->count(),
            'completed' => (clone $baseQuery)->whereHas('reviewers', function ($q) {
                $q->where('user_id', Auth::id())->where('status', ReviewStatus::COMPLETED);
            })->count(),
            're_review' => (clone $baseQuery)->whereHas('reviewers', function ($q) {
                $q->where('user_id', Auth::id())->where('status', ReviewStatus::RE_REVIEW_REQUESTED);
            })->count(),
        ];
    }

    public function render()
    {
        return view('livewire.review.community-service');
    }
}
