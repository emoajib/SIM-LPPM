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

    // Vetted by AI - Manual Review Required by Senior Engineer/Manager
    public int $reviewerCountRequired = 1;

    public function mount(): void
    {
        $this->featureRoadmapActive = Setting::get('feature_roadmap_active', false);
        $this->featureKaprodiValidation = Setting::get('feature_kaprodi_validation', false);
        $this->featureCommunityPartnerRequired = Setting::get('feature_community_partner_required', true);
        // Vetted by AI - Manual Review Required by Senior Engineer/Manager
        $this->reviewerCountRequired = (int) Setting::get('reviewer_count_required', 1);
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

        // Vetted by AI - Manual Review Required by Senior Engineer/Manager
        if ($property === 'reviewerCountRequired') {
            Setting::set('reviewer_count_required', (int) $value, 'integer');
        }

        // We dispatch a browser event or notify the user
        $this->dispatch('settings-updated', message: 'Feature Flags berhasil diperbarui.');
    }

    public function render(): View
    {
        return view('livewire.settings.feature-flags');
    }
}
