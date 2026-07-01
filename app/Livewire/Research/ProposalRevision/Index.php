<?php

declare(strict_types=1);

namespace App\Livewire\Research\ProposalRevision;

use App\Enums\ProposalStatus;
use App\Models\Proposal;
use App\Models\Research;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

class Index extends Component
{
    #[Url]
    public string $search = '';

    #[Url]
    public string $selectedYear = '';

    #[On('resetFilters')]
    public function resetFilters(): void
    {
        $this->reset(['search', 'selectedYear']);
    }

    #[Computed]
    public function proposals(): Collection
    {
        $user = Auth::user();

        $query = Proposal::where('detailable_type', Research::class);

        // Eager load relationships
        $query->with([
            'submitter.identity',
            'detailable',
            'focusArea',
            'reviewers' => function ($q) {
                $q->whereIn('status', ['completed', 'pending'])
                    ->with('user');
            },
        ]);

        // where submitter is current user
        if ($user->hasRole('dosen')) {
            // Tampilkan semua proposal milik dosen yang sudah selesai direview:
            // - REVISION_NEEDED: dikembalikan Kepala LPPM untuk perbaikan
            // - REVISION_SUBMITTED: sudah diajukan ulang, menunggu keputusan akhir
            $query->where('submitter_id', $user->id)
                ->whereIn('status', [
                    ProposalStatus::REVISION_NEEDED,
                    ProposalStatus::REVISION_SUBMITTED,
                ]);
        } elseif ($user->hasRole('reviewer')) {
            $query->whereHas('reviewers', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->whereIn('status', ['completed', 'pending']);
            });
        } elseif ($user->hasAnyRole(['kepala lppm', 'admin lppm', 'rektor'])) {
            $query->whereHas('reviewers', function ($q) {
                $q->whereIn('status', ['completed', 'pending']);
            });
        }

        $query->whereHas('reviewers', function ($q) {
            $q->whereIn('status', ['completed', 'pending']);
        });

        // Search filter
        if (! empty($this->search)) {
            $searchTerm = '%'.$this->search.'%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'LIKE', $searchTerm)
                    ->orWhereHas('submitter', function ($sq) use ($searchTerm) {
                        $sq->where('name', 'LIKE', $searchTerm);
                    });
            });
        }

        // Year filter
        if (! empty($this->selectedYear)) {
            $query->whereYear('created_at', $this->selectedYear);
        }

        return $query->latest()->get();
    }

    #[Computed]
    public function availableYears()
    {
        $user = Auth::user();

        $query = Proposal::where('detailable_type', Research::class);

        if ($user->hasRole('dosen')) {
            $query->where('submitter_id', $user->id)
                ->whereIn('status', [
                    ProposalStatus::REVISION_NEEDED,
                    ProposalStatus::REVISION_SUBMITTED,
                ]);
        } elseif ($user->hasRole('reviewer')) {
            $query->whereHas('reviewers', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->where('status', 'completed');
            });
        } elseif ($user->hasAnyRole(['kepala lppm', 'admin lppm', 'rektor'])) {
            $query->whereHas('reviewers', function ($q) {
                $q->where('status', 'completed');
            });
        }

        return $query->selectRaw('DISTINCT '.sql_year().' as year')
            ->orderByDesc('year')
            ->pluck('year');
    }

    public function render()
    {
        return view('livewire.research.proposal-revision.index');
    }
}
