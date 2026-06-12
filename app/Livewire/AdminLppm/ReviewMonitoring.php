<?php

namespace App\Livewire\AdminLppm;

use App\Models\CommunityService;
use App\Models\Proposal;
use App\Models\Research;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ReviewMonitoring extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $typeFilter = 'all';

    public function mount()
    {
        if (! Auth::user()->hasRole('admin lppm')) {
            abort(403);
        }
    }

    public function resetFilters()
    {
        $this->reset(['search', 'typeFilter']);
        $this->resetPage();
    }

    #[Computed]
    public function proposals()
    {
        return Proposal::query()
            ->whereIn('status', ['under_review', 'reviewed'])
            ->with(['submitter', 'detailable', 'reviewers.user'])
            ->when($this->search, function ($query) {
                $query->where('title', 'like', "%{$this->search}%");
            })
            ->when($this->typeFilter !== 'all', function ($query) {
                $detailableType = $this->typeFilter === 'research'
                    ? Research::class
                    : CommunityService::class;
                $query->where('detailable_type', $detailableType);
            })
            ->latest()
            ->paginate(15);
    }

    public function render()
    {
        return view('livewire.admin-lppm.review-monitoring');
    }
}
