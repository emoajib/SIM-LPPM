<?php

namespace App\Livewire\AdminLppm;

use App\Enums\ReviewStatus;
use App\Models\Proposal;
use App\Models\User;
use App\Support\AcademicPeriod;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class ReviewerWorkload extends Component
{
    #[Url(history: true)]
    public string $yearFilter = '';

    #[Url(history: true)]
    public string $semesterFilter = 'all';

    public function mount()
    {
        if (! Auth::user()->hasRole('admin lppm')) {
            abort(403);
        }

        $this->yearFilter = AcademicPeriod::current();
    }

    public function resetFilters(): void
    {
        $this->yearFilter = AcademicPeriod::current();
        $this->semesterFilter = 'all';
    }

    #[Computed]
    public function reviewers()
    {
        $year = $this->yearFilter;

        return User::role('reviewer')
            ->withCount([
                'reviews as total_assigned' => function ($query) use ($year) {
                    $query->whereHas('proposal', function ($pq) use ($year) {
                        $pq->when($year, fn ($sub) => $sub->forAcademicYear($year))
                            ->when($this->semesterFilter !== 'all', fn ($sub) => $sub->where('semester', $this->semesterFilter));
                    });
                },
                'reviews as pending_count' => function ($query) use ($year) {
                    $query->where('status', ReviewStatus::PENDING->value)
                        ->whereHas('proposal', function ($pq) use ($year) {
                            $pq->when($year, fn ($sub) => $sub->forAcademicYear($year))
                                ->when($this->semesterFilter !== 'all', fn ($sub) => $sub->where('semester', $this->semesterFilter));
                        });
                },
                'reviews as completed_count' => function ($query) use ($year) {
                    $query->where('status', ReviewStatus::COMPLETED->value)
                        ->whereHas('proposal', function ($pq) use ($year) {
                            $pq->when($year, fn ($sub) => $sub->forAcademicYear($year))
                                ->when($this->semesterFilter !== 'all', fn ($sub) => $sub->where('semester', $this->semesterFilter));
                        });
                },
            ])
            ->with(['identity.faculty'])
            ->get();
    }

    #[Computed]
    public function availableYears(): array
    {
        $periods = Proposal::query()
            ->select('start_year', 'semester')
            ->distinct()
            ->orderByDesc('start_year')
            ->get()
            ->filter(fn ($period) => $period->start_year !== null);

        $options = AcademicPeriod::optionsFromPeriods($periods);

        return $options !== [] ? $options : [AcademicPeriod::current()];
    }

    public function render()
    {
        return view('livewire.admin-lppm.reviewer-workload');
    }
}
