<?php

namespace App\Livewire\AdminLppm\ManualBook;

use App\Livewire\Concerns\HasToast;
use App\Models\ManualBook;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesRequests, HasToast, WithPagination;

    public $search = '';

    public $statusFilter = '';

    protected $queryString = ['search', 'statusFilter'];

    public function toggleStatus(ManualBook $manualBook): void
    {
        $manualBook->update([
            'status' => $manualBook->status === 'active' ? 'inactive' : 'active',
        ]);
        $this->toastSuccess('Status manual book berhasil diubah.');
    }

    public function delete(ManualBook $manualBook): void
    {
        $manualBook->delete();
        $this->toastSuccess('Manual book berhasil dihapus.');
    }

    public function render()
    {
        return view('livewire.admin-lppm.manual-book.index', [
            'manualBooks' => ManualBook::query()
                ->with(['creator', 'media'])
                ->when($this->search, fn ($q) => $q->where('title', 'like', '%'.$this->search.'%'))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->latest()
                ->paginate(10),
        ]);
    }
}
