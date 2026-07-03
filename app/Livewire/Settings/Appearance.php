<?php

namespace App\Livewire\Settings;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithFileUploads;

class Appearance extends Component
{
    use WithFileUploads;

    public string $dashboardName = 'Dashboard';

    public string $loginTitle = 'Login to your account';

    public $logo;

    public function mount()
    {
        if (! auth()->user()->activeHasRole('admin lppm')) {
            abort(403);
        }

        $this->dashboardName = Setting::where('key', 'dashboard_name')->value('value') ?? 'Dashboard';
        $this->loginTitle = Setting::where('key', 'login_title')->value('value') ?? 'Login to your account';
    }

    public function saveSettings()
    {
        Setting::updateOrCreate(
            ['key' => 'dashboard_name'],
            ['value' => $this->dashboardName, 'type' => 'string']
        );

        Setting::updateOrCreate(
            ['key' => 'login_title'],
            ['value' => $this->loginTitle, 'type' => 'string']
        );

        Cache::forget('settings.login_title');

        session()->flash('success_settings', 'Pengaturan teks berhasil disimpan.');
    }

    public function saveLogo()
    {
        $this->validate([
            'logo' => 'image|max:2048', // 2MB Max
        ]);

        $setting = Setting::firstOrCreate(
            ['key' => 'app_logo'],
            ['value' => 'logo', 'type' => 'image']
        );

        if ($this->logo) {
            $setting->clearMediaCollection('logo');
            $setting->addMedia($this->logo->getRealPath())
                ->usingName($this->logo->getClientOriginalName())
                ->setFileName($this->logo->getClientOriginalName())
                ->toMediaCollection('logo');
        }

        session()->flash('success_logo', 'Logo berhasil diperbarui.');
    }

    public function render()
    {
        $currentLogo = Setting::where('key', 'app_logo')->first()?->getFirstMediaUrl('logo') ?: '/logo.png';

        return view('livewire.settings.appearance', ['currentLogo' => $currentLogo]);
    }
}
