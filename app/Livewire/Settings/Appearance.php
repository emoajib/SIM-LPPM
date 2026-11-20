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

    public $mascot;

    public function mount()
    {
        if (! auth()->user()->activeHasAnyRole(['admin lppm', 'superadmin'])) {
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

    public function saveMascot()
    {
        $this->validate([
            'mascot' => 'image|mimes:png,jpg,jpeg,webp|max:2048',
        ]);

        $setting = Setting::firstOrCreate(
            ['key' => 'dashboard_mascot'],
            ['value' => 'mascot', 'type' => 'image']
        );

        if ($this->mascot) {
            $setting->clearMediaCollection('mascot');
            $setting->addMedia($this->mascot->getRealPath())
                ->usingName($this->mascot->getClientOriginalName())
                ->setFileName($this->mascot->getClientOriginalName())
                ->toMediaCollection('mascot');
        }

        $this->mascot = null;
        Setting::clearCache();
        session()->flash('success_mascot', 'Maskot banner berhasil diperbarui.');
    }

    public function removeMascot()
    {
        $setting = Setting::where('key', 'dashboard_mascot')->first();
        if ($setting) {
            $setting->clearMediaCollection('mascot');
            $setting->delete();
        }
        $this->mascot = null;
        Setting::clearCache();
        session()->flash('success_mascot', 'Maskot dikembalikan ke bawaan.');
    }

    public function render()
    {
        $currentLogo = Setting::where('key', 'app_logo')->first()?->getFirstMediaUrl('logo') ?: '/logo.png';
        $currentMascot = Setting::mediaUrl('dashboard_mascot', 'mascot');

        return view('livewire.settings.appearance', [
            'currentLogo' => $currentLogo,
            'currentMascot' => $currentMascot,
        ]);
    }
}
