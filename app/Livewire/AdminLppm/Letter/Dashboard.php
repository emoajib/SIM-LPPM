<?php

namespace App\Livewire\AdminLppm\Letter;

use App\Models\Letter;
use App\Services\LetterService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Dashboard Persuratan', 'pageTitle' => 'Dashboard Persuratan', 'pageSubtitle' => 'Statistik dan overview surat'])]
class Dashboard extends Component
{
    use WithPagination;

    public function render()
    {
        $service = new LetterService;
        $stats = $service->getLetterStats();

        $recentLetters = Letter::with(['letterType', 'user'])
            ->latest()
            ->paginate(10);

        return view('livewire.admin-lppm.letter.dashboard', [
            'stats' => $stats,
            'recentLetters' => $recentLetters,
        ]);
    }
}
