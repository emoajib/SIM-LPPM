<?php

namespace App\Livewire\Settings;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SettingsIndex extends Component
{
    public string $activeTab = 'profile';

    /**
     * Set the active tab.
     */
    public function setActiveTab(string $tab): void
    {
        // Vetted by AI - Manual Review Required by Senior Engineer/Manager
        $adminOnlyTabs = ['appearance', 'audit', 'sync', 'feature-flags', 'backup', 'restore', 'pdf-export', 'tkt-manager'];

        if (in_array($tab, $adminOnlyTabs) && ! (Auth::user()?->hasRole('admin lppm') || Auth::user()?->hasRole('superadmin'))) {
            abort(403, 'Maaf Anda tidak memiliki akses ke tab ini.');
        }

        $this->activeTab = $tab;
    }

    public function render()
    {
        return view('livewire.settings.index');
    }
}
