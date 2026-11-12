<?php

use App\Livewire\Settings\Tabs\ScienceClusterManager;
use App\Livewire\Settings\Tabs\TktManager;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin lppm');

    $this->actingAs($this->admin);
    session(['active_role' => 'admin lppm']);
});

/**
 * Smoke test markup: sebelumnya file Blade tab TKT & Cluster Ilmu rusak
 * (tag <button> hilang) sehingga atribut tampil sebagai teks di halaman.
 */
test('tkt manager renders with valid action buttons', function () {
    $component = Livewire::test(TktManager::class)->assertOk();

    $html = $component->html();

    expect($html)->toContain('Manajemen TKT')
        ->and(substr_count($html, '<button'))->toBe(substr_count($html, '</button>'))
        ->and($html)->not->toContain('class=&quot;btn')   // atribut bocor sebagai teks
        ->and($html)->not->toContain('wire:click=&quot;');
});

test('science cluster manager renders with valid action buttons', function () {
    $component = Livewire::test(ScienceClusterManager::class)->assertOk();

    $html = $component->html();

    expect($html)->toContain('Manajemen Cluster Ilmu')
        ->and(substr_count($html, '<button'))->toBe(substr_count($html, '</button>'))
        ->and($html)->not->toContain('class=&quot;btn')
        ->and($html)->not->toContain('wire:click=&quot;');
});
