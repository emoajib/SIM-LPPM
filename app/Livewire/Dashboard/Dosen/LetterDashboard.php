<?php

namespace App\Livewire\Dashboard\Dosen;

use App\Models\Letter;
use App\Services\LetterService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Dashboard Persuratan', 'pageTitle' => 'Dashboard Surat Saya', 'pageSubtitle' => 'Ringkasan pengajuan dan status surat'])]
class LetterDashboard extends Component
{
    use WithPagination;

    public function render()
    {
        $service = new LetterService;
        $stats = $service->getLetterStatsForUser(auth()->id());

        $recentLetters = Letter::with(['letterType'])
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(10);

        return view('livewire.dashboard.dosen.letter-dashboard', [
            'stats' => $stats,
            'recentLetters' => $recentLetters,
        ]);
    }
}
