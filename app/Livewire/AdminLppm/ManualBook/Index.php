<?php

namespace App\Livewire\AdminLppm\ManualBook;

use App\Models\ManualBook;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $search = '';

    protected $queryString = ['search'];

    public function render()
    {
        return view('livewire.admin-lppm.manual-book.index', [
            'manualBooks' => ManualBook::query()
                ->with('media')
                ->when($this->search, fn ($q) => $q->where('title', 'like', '%'.$this->search.'%'))
                ->latest()
                ->paginate(20),
        ]);
    }
}
