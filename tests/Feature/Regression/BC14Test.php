<?php

use App\Livewire\Settings\Appearance;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * Regression: BC-14 — maskot banner tidak berubah setelah upload karena berkas
 * hanya tersimpan saat tombol "Simpan Maskot" diklik.
 *
 * Penyebab: `wire:model="mascot"` bersifat deferred. Memilih berkas lalu menekan
 * tombol Livewire lain (mis. "Simpan Ukuran") membuang temporary upload tanpa
 * pernah menulis media, sehingga dashboard tetap merender SVG bawaan.
 *
 * Perbaikan: hook `updatedMascot()` menyimpan otomatis saat berkas dipilih, dan
 * `saveMascotSize()` menyimpan berkas yang masih pending sebelum menyimpan ukuran.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Storage::fake('public');
    Setting::clearCache();

    $admin = User::factory()->create();
    $admin->assignRole('admin lppm');
    $this->actingAs($admin);
    session(['active_role' => 'admin lppm']);
});

it('BC-14: memilih berkas maskot langsung menyimpannya tanpa klik tombol', function () {
    Livewire::test(Appearance::class)
        ->set('mascot', UploadedFile::fake()->image('maskot-baru.png', 200, 200))
        ->assertHasNoErrors();

    $setting = Setting::where('key', 'dashboard_mascot')->first();

    expect($setting)->not->toBeNull();
    expect($setting->getFirstMedia('mascot'))->not->toBeNull();
    expect($setting->getFirstMediaUrl('mascot'))->toContain('maskot-baru.png');
});

it('BC-14: menekan Simpan Ukuran tidak membuang berkas maskot yang sudah dipilih', function () {
    Livewire::test(Appearance::class)
        ->set('mascotSize', 'large')
        ->set('mascot', UploadedFile::fake()->image('maskot-pending.png', 200, 200))
        ->call('saveMascotSize')
        ->assertHasNoErrors();

    Setting::clearCache();

    expect(Setting::mediaUrl('dashboard_mascot', 'mascot'))->not->toBeNull();
    expect(Setting::get('dashboard_mascot_size'))->toBe('large');
});

it('BC-14: dashboard modern menampilkan gambar maskot yang baru diunggah', function () {
    Livewire::test(Appearance::class)
        ->set('mascot', UploadedFile::fake()->image('maskot-dashboard.png', 200, 200))
        ->assertHasNoErrors();

    Setting::clearCache();
    $url = Setting::mediaUrl('dashboard_mascot', 'mascot');

    expect($url)->not->toBeNull();

    session([
        'active_role' => 'admin lppm',
        'sim_lppm_app_theme' => 'modern',
        'sim_lppm_dashboard_theme' => 'modern',
    ]);

    $this->get('/dashboard')
        ->assertOk()
        ->assertSee($url, false)
        ->assertDontSee('M10,20 L12,23', false);
});
