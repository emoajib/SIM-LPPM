<?php

use App\Models\Letter;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->user = User::factory()->create();
    $this->user->assignRole('dosen');
});

it('hides letter entry buttons on modern dashboard when module is disabled', function () {
    Setting::set('module_persuratan_active', false, 'boolean');

    $this->actingAs($this->user)
        ->withSession(['sim_lppm_app_theme' => 'modern'])
        ->get('/dashboard')
        ->assertStatus(200)
        ->assertDontSee('Buat Surat Tugas / Izin', false)
        ->assertDontSee('Dashboard Surat', false);
});

it('shows letter entry buttons on modern dashboard when module is enabled', function () {
    Setting::set('module_persuratan_active', true, 'boolean');

    $this->actingAs($this->user)
        ->withSession(['sim_lppm_app_theme' => 'modern'])
        ->get('/dashboard')
        ->assertStatus(200)
        ->assertSee('Buat Surat Tugas / Izin', false);
});

it('keeps letter verification accessible when module is disabled', function () {
    Setting::set('module_persuratan_active', false, 'boolean');

    $letter = Letter::factory()->create();

    $this->get(URL::signedRoute('letters.verify', ['letter' => $letter->id]))
        ->assertOk();
});

it('blocks letter creation route when module is disabled', function () {
    Setting::set('module_persuratan_active', false, 'boolean');

    $this->actingAs($this->user)
        ->get('/surat/buat')
        ->assertStatus(302);
});
