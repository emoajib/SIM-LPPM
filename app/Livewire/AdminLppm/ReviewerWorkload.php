<?php

namespace App\Livewire\AdminLppm;

use App\Enums\ReviewStatus;
use App\Models\Proposal;
use App\Models\User;
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

        $this->yearFilter = (string) date('Y');
    }

    public function resetFilters(): void
    {
        $this->yearFilter = (string) date('Y');
        $this->semesterFilter = 'all';
    }

    #[Computed]
    public function reviewers()
    {
        $year = (int) $this->yearFilter;

        return User::role('reviewer')
            ->withCount([
                'reviews as total_assigned' => function ($query) use ($year) {
                    $query->whereHas('proposal', function ($pq) use ($year) {
                        $pq->when($year, fn ($sub) => $sub->whereYear('created_at', $year))
                            ->when($this->semesterFilter !== 'all', fn ($sub) => $sub->where('semester', $this->semesterFilter));
                    });
                },
                'reviews as pending_count' => function ($query) use ($year) {
                    $query->where('status', ReviewStatus::PENDING->value)
                        ->whereHas('proposal', function ($pq) use ($year) {
                            $pq->when($year, fn ($sub) => $sub->whereYear('created_at', $year))
                                ->when($this->semesterFilter !== 'all', fn ($sub) => $sub->where('semester', $this->semesterFilter));
                        });
                },
                'reviews as completed_count' => function ($query) use ($year) {
                    $query->where('status', ReviewStatus::COMPLETED->value)
                        ->whereHas('proposal', function ($pq) use ($year) {
                            $pq->when($year, fn ($sub) => $sub->whereYear('created_at', $year))
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
        $years = Proposal::query()
            ->selectRaw(sql_year().' as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->map(fn ($y) => (string) $y)
            ->toArray();

        return array_filter($years) ?: [(string) date('Y')];
    }

    public function render()
    {
        return view('livewire.admin-lppm.reviewer-workload');
    }
}
