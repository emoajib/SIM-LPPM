<?php

namespace App\Livewire\Settings;

use App\Models\Setting;
use App\Services\Security\CaptchaAudioService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class CaptchaSettings extends Component
{
    public bool $audioEnabled = true;

    public string $audioVoice = 'damayanti';

    /** @var list<array{key: string, label: string, locale: string, available: bool}> */
    #[Locked]
    public array $voices = [];

    public function mount(): void
    {
        abort_unless(
            Auth::user()?->activeHasRole('admin lppm') || Auth::user()?->activeHasRole('superadmin'),
            403
        );

        $service = app(CaptchaAudioService::class);

        $this->voices = $service->voices();
        $this->audioVoice = $service->voice();
        $this->audioEnabled = (bool) Setting::get('captcha_audio_enabled', true);
    }

    public function updatedAudioEnabled(mixed $value): void
    {
        $this->audioEnabled = (bool) $value;
        Setting::set('captcha_audio_enabled', $this->audioEnabled, 'boolean');

        $this->dispatch('settings-updated', message: 'Pengaturan audio CAPTCHA diperbarui.');
    }

    public function updatedAudioVoice(mixed $value): void
    {
        $voice = collect(app(CaptchaAudioService::class)->voices())->firstWhere('key', $value);

        if (! is_string($value) || $voice === null || ! $voice['available']) {
            $this->audioVoice = app(CaptchaAudioService::class)->voice();
            $this->addError('audioVoice', 'Jenis suara tersebut tidak tersedia.');

            return;
        }

        Setting::set('captcha_audio_voice', $value);
        $this->resetErrorBag('audioVoice');
        $this->dispatch('settings-updated', message: 'Jenis suara audio CAPTCHA diperbarui.');
    }

    public function render(): View
    {
        return view('livewire.settings.captcha-settings');
    }
}
