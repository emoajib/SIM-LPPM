<?php

namespace App\Livewire\Settings;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class Appearance extends Component
{
    use WithFileUploads;

    public string $dashboardName = 'Dashboard';

    public string $loginTitle = 'Login to your account';

    public $logo;

    public $mascot;

    public $avatarMale;

    public $avatarFemale;

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

    public function saveAvatarMale()
    {
        $this->saveGenderAvatar('male');
    }

    public function saveAvatarFemale()
    {
        $this->saveGenderAvatar('female');
    }

    public function removeAvatarMale()
    {
        $this->removeGenderAvatar('male');
    }

    public function removeAvatarFemale()
    {
        $this->removeGenderAvatar('female');
    }

    /**
     * Simpan avatar default untuk gender tertentu (male|female).
     * File lama otomatis tergantikan karena koleksi bersifat singleFile.
     */
    private function saveGenderAvatar(string $gender): void
    {
        $property = $gender === 'male' ? 'avatarMale' : 'avatarFemale';

        $this->validate([
            $property => 'required|image|mimes:png,jpg,jpeg,webp|max:2048',
        ], [], [
            $property => $gender === 'male' ? 'avatar pria' : 'avatar wanita',
        ]);

        /** @var TemporaryUploadedFile|null $file */
        $file = $this->{$property};
        if (! $file) {
            return;
        }

        $setting = Setting::firstOrCreate(
            ['key' => 'default_avatar_'.$gender],
            ['value' => 'avatar', 'type' => 'image']
        );

        $setting->clearMediaCollection('avatar');
        $setting->addMedia($file->getRealPath())
            ->usingName($file->getClientOriginalName())
            ->setFileName($file->getClientOriginalName())
            ->toMediaCollection('avatar');

        $this->{$property} = null;
        Setting::clearCache();

        session()->flash(
            'success_avatar_'.$gender,
            'Avatar default '.($gender === 'male' ? 'pria' : 'wanita').' berhasil diperbarui.'
        );
    }

    private function removeGenderAvatar(string $gender): void
    {
        $property = $gender === 'male' ? 'avatarMale' : 'avatarFemale';

        $setting = Setting::where('key', 'default_avatar_'.$gender)->first();
        if ($setting) {
            $setting->clearMediaCollection('avatar');
            $setting->delete();
        }

        $this->{$property} = null;
        Setting::clearCache();

        session()->flash(
            'success_avatar_'.$gender,
            'Avatar default '.($gender === 'male' ? 'pria' : 'wanita').' dikembalikan ke inisial.'
        );
    }

    public function render()
    {
        $currentLogo = Setting::where('key', 'app_logo')->first()?->getFirstMediaUrl('logo') ?: '/logo.png';
        $currentMascot = Setting::mediaUrl('dashboard_mascot', 'mascot');

        return view('livewire.settings.appearance', [
            'currentLogo' => $currentLogo,
            'currentMascot' => $currentMascot,
            'currentAvatarMale' => Setting::mediaUrl('default_avatar_male', 'avatar'),
            'currentAvatarFemale' => Setting::mediaUrl('default_avatar_female', 'avatar'),
        ]);
    }
}
