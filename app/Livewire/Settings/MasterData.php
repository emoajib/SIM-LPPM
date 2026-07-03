<?php

namespace App\Livewire\Settings;

use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class MasterData extends Component
{
    use WithPagination;

    #[Url(as: 'group')]
    public string $group = 'academic-content';

    #[Url(as: 'tab')]
    public string $activeTab = '';

    public function mount(): void
    {
        $user = Auth::user();

        // Base authorization: hanya admin lppm, superadmin, dekan, kaprodi
        abort_unless(
            $user?->activeHasRole('admin lppm') || $user?->activeHasRole('superadmin') || $user?->activeHasRole('dekan') || $user?->activeHasRole('kaprodi'),
            403,
            'Maaf Anda tidak memiliki akses ini'
        );

        $roadmapActive = Setting::get('feature_roadmap_active', false);

        // Jika fitur roadmap nonaktif, Dekan & Kaprodi dilarang akses (kecuali mereka juga admin)
        if (! $roadmapActive && ($user->activeHasRole('dekan') || $user->activeHasRole('kaprodi'))) {
            if (! $user->activeHasRole('admin lppm') && ! $user->activeHasRole('superadmin')) {
                abort(403, 'Maaf Anda tidak memiliki akses ini. Fitur roadmap tidak aktif.');
            }
        }

        if (empty($this->activeTab)) {
            $this->activeTab = match ($this->group) {
                'academic-structure' => 'study-programs',
                'budget' => 'budget-groups',
                'partnership' => 'partners',
                'academic-content' => 'focus-areas',
                default => 'focus-areas',
            };
        }
    }

    public function setActiveTab(string $tab): void
    {
        $this->resetPage();
        $this->activeTab = $tab;
    }

    public function render()
    {
        $title = match ($this->group) {
            'academic-structure' => 'Struktur Akademik',
            'budget' => 'Anggaran & RAB',
            'partnership' => 'Kemitraan & Prioritas',
            default => 'Master Data',
        };

        return view('livewire.settings.master-data', [
            'pageTitle' => $title,
        ]);
    }
}
