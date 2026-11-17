<?php

namespace App\Livewire\Settings;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;

class SettingsIndex extends Component
{
    /**
     * Active settings tab. Synced to the query string (?tab=...) so the Modern
     * theme Settings Hub can deep-link straight to a Classic tab instead of
     * dropping the user on the default "Profil Saya" tab.
     */
    // Vetted by SOSIOMEN - Manual Review Required by Senior Engineer/Manager
    #[Url(as: 'tab', history: true)]
    public string $activeTab = 'profile';

    /**
     * Tabs restricted to admin LPPM / superadmin.
     */
    private const ADMIN_ONLY_TABS = [
        'appearance',
        'audit',
        'sync',
        'feature-flags',
        'captcha',
        'backup',
        'restore',
        'pdf-export',
        'tkt-manager',
        'notification-config',
    ];

    /**
     * All tabs the component knows how to render.
     */
    private const KNOWN_TABS = [
        'profile',
        'password',
        'security',
        'appearance',
        'pdf-export',
        'tkt-manager',
        'audit',
        'notification-config',
        'feature-flags',
        'sync',
        'backup',
        'restore',
        'captcha',
    ];

    /**
     * Guard the tab hydrated from the URL before the page renders.
     * Without this, ?tab=backup would bypass the setActiveTab() check.
     */
    public function mount(): void
    {
        $this->activeTab = $this->normalizeTab($this->activeTab);

        $this->assertTabAllowed($this->activeTab);
    }

    /**
     * Set the active tab.
     */
    public function setActiveTab(string $tab): void
    {
        $tab = $this->normalizeTab($tab);

        $this->assertTabAllowed($tab);

        $this->activeTab = $tab;
    }

    /**
     * Unknown tabs fall back to the default tab instead of rendering an empty page.
     */
    private function normalizeTab(string $tab): string
    {
        return in_array($tab, self::KNOWN_TABS, true) ? $tab : 'profile';
    }

    /**
     * Abort when the user may not open an admin-only tab.
     */
    private function assertTabAllowed(string $tab): void
    {
        if (in_array($tab, self::ADMIN_ONLY_TABS, true)
            && ! (Auth::user()?->activeHasRole('admin lppm') || Auth::user()?->activeHasRole('superadmin'))) {
            abort(403, 'Maaf Anda tidak memiliki akses ke tab ini.');
        }
    }

    public function render()
    {
        return view('livewire.settings.index');
    }
}
