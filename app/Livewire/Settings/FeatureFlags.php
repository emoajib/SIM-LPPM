<?php

namespace App\Livewire\Settings;

use App\Models\Setting;
use Illuminate\View\View;
use Livewire\Component;

class FeatureFlags extends Component
{
    public bool $featureRoadmapActive = false;

    public bool $featureKaprodiValidation = false;

    public bool $featureCommunityPartnerRequired = true;

    public function mount(): void
    {
        $this->featureRoadmapActive = Setting::get('feature_roadmap_active', false);
        $this->featureKaprodiValidation = Setting::get('feature_kaprodi_validation', false);
        $this->featureCommunityPartnerRequired = Setting::get('feature_community_partner_required', true);
    }

    public function updated(string $property, mixed $value): void
    {
        if ($property === 'featureRoadmapActive') {
            Setting::set('feature_roadmap_active', $value, 'boolean');
        }

        if ($property === 'featureKaprodiValidation') {
            Setting::set('feature_kaprodi_validation', $value, 'boolean');
        }

        if ($property === 'featureCommunityPartnerRequired') {
            Setting::set('feature_community_partner_required', $value, 'boolean');
        }

        // We dispatch a browser event or notify the user
        $this->dispatch('settings-updated', message: 'Feature Flags berhasil diperbarui.');
    }

    public function render(): View
    {
        return view('livewire.settings.feature-flags');
    }
}
