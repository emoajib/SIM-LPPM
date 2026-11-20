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

    /** Ukuran maskot banner dashboard: small|medium|large. */
    public string $mascotSize = 'medium';

    public function mount()
    {
        if (! auth()->user()->activeHasAnyRole(['admin lppm', 'superadmin'])) {
            abort(403);
        }

        $this->dashboardName = Setting::where('key', 'dashboard_name')->value('value') ?? 'Dashboard';
        $this->loginTitle = Setting::where('key', 'login_title')->value('value') ?? 'Login to your account';
        $this->mascotSize = (string) (Setting::get('dashboard_mascot_size', 'medium') ?: 'medium');
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

    /** Simpan otomatis logo begitu berkas dipilih (lihat BC-14). */
    public function updatedLogo(): void
    {
        $this->saveLogo();
    }

    public function saveLogo()
    {
        if (! $this->logo) {
            $this->addError('logo', 'Pilih berkas logo terlebih dahulu.');

            return;
        }

        $this->validate([
            'logo' => 'required|image|max:2048', // 2MB Max
        ]);

        $setting = Setting::firstOrCreate(
            ['key' => 'app_logo'],
            ['value' => 'logo', 'type' => 'image']
        );

        $setting->clearMediaCollection('logo');
        $setting->addMedia($this->logo->getRealPath())
            ->usingName($this->logo->getClientOriginalName())
            ->setFileName($this->logo->getClientOriginalName())
            ->toMediaCollection('logo');

        $this->logo = null;
        session()->flash('success_logo', 'Logo berhasil diperbarui.');
    }

    /**
     * Simpan otomatis begitu admin memilih berkas.
     *
     * Sebelumnya berkas hanya tersimpan bila tombol "Simpan Maskot" diklik;
     * memilih berkas lalu menekan "Simpan Ukuran" membuang berkas sementara
     * sehingga dashboard tetap menampilkan SVG bawaan.
     */
    public function updatedMascot(): void
    {
        $this->saveMascot();
    }

    public function saveMascot()
    {
        if (! $this->mascot) {
            $this->addError('mascot', 'Pilih berkas maskot terlebih dahulu.');

            return;
        }

        $this->validate([
            'mascot' => 'required|image|mimes:png,jpg,jpeg,webp|max:2048',
        ]);

        $setting = Setting::firstOrCreate(
            ['key' => 'dashboard_mascot'],
            ['value' => 'mascot', 'type' => 'image']
        );

        $setting->clearMediaCollection('mascot');
        $setting->addMedia($this->mascot->getRealPath())
            ->usingName($this->mascot->getClientOriginalName())
            ->setFileName($this->mascot->getClientOriginalName())
            ->toMediaCollection('mascot');

        $this->mascot = null;
        Setting::clearCache();
        session()->flash('success_mascot', 'Maskot banner berhasil diperbarui.');
    }

    public function saveMascotSize()
    {
        // Jangan buang berkas yang sudah dipilih admin: simpan dulu maskotnya.
        if ($this->mascot) {
            $this->saveMascot();

            if ($this->getErrorBag()->has('mascot')) {
                return;
            }
        }

        $this->validate([
            'mascotSize' => 'required|in:small,medium,large',
        ], [], ['mascotSize' => 'ukuran maskot']);

        Setting::set('dashboard_mascot_size', $this->mascotSize);
        Setting::clearCache();

        session()->flash('success_mascot_size', 'Ukuran maskot banner berhasil diperbarui.');
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

    /** Simpan otomatis avatar pria begitu berkas dipilih. */
    public function updatedAvatarMale(): void
    {
        $this->saveGenderAvatar('male');
    }

    /** Simpan otomatis avatar wanita begitu berkas dipilih. */
    public function updatedAvatarFemale(): void
    {
        $this->saveGenderAvatar('female');
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
        $currentMascotName = Setting::where('key', 'dashboard_mascot')->first()?->getFirstMedia('mascot')?->file_name;

        return view('livewire.settings.appearance', [
            'currentLogo' => $currentLogo,
            'currentMascot' => $currentMascot,
            'currentMascotName' => $currentMascotName,
            'currentAvatarMale' => Setting::mediaUrl('default_avatar_male', 'avatar'),
            'currentAvatarFemale' => Setting::mediaUrl('default_avatar_female', 'avatar'),
        ]);
    }
}
