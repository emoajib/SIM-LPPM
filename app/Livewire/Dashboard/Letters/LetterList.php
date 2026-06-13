<?php

namespace App\Livewire\Dashboard\Letters;

use App\Models\Letter;
use App\Models\Proposal;
use App\Models\Setting;
use Livewire\Component;

class LetterList extends Component
{
    public Proposal $proposal;

    protected $listeners = ['letterRequested' => '$refresh'];

    public function render()
    {
        $letters = Letter::with(['letterType'])
            ->where('reference_type', get_class($this->proposal))
            ->where('reference_id', $this->proposal->id)
            ->latest()
            ->get();

        $isActive = (bool) Setting::get('module_persuratan_active', false);

        return view('livewire.dashboard.letters.letter-list', [
            'letters' => $letters,
            'isActive' => $isActive,
        ]);
    }
}
