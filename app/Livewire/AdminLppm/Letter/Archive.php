<?php

namespace App\Livewire\AdminLppm\Letter;

use App\Models\Letter;
use App\Models\LetterType;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Arsip Surat', 'pageTitle' => 'Arsip Surat', 'pageSubtitle' => 'Kelola dan lihat semua surat'])]
class Archive extends Component
{
    use WithPagination;

    public $search = '';

    public $statusFilter = '';

    public $typeFilter = '';

    public $dateFrom = '';

    public $dateTo = '';

    public function render()
    {
        $letters = Letter::with(['letterType', 'user'])
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->typeFilter, fn ($q) => $q->where('letter_type_id', $this->typeFilter))
            ->when($this->search, function ($q) {
                $q->where('letter_number', 'like', '%'.$this->search.'%')
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', '%'.$this->search.'%'))
                    ->orWhereHas('letterType', fn ($tq) => $tq->where('name', 'like', '%'.$this->search.'%'));
            })
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->latest()
            ->paginate(20);

        return view('livewire.admin-lppm.letter.archive', [
            'letters' => $letters,
            'letterTypes' => LetterType::where('is_active', true)->orderBy('code')->get(),
        ]);
    }
}
