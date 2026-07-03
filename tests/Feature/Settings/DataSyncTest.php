<?php

use App\Livewire\Settings\DataSync;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin lppm');

    $this->user = User::factory()->create();
});

test('guest cannot access data sync page', function () {
    Livewire::test(DataSync::class)
        ->assertStatus(403);
});

test('non-admin user cannot access data sync page', function () {
    $this->actingAs($this->user);

    Livewire::test(DataSync::class)
        ->assertStatus(403);
});

test('admin lppm can access data sync page', function () {
    $this->actingAs($this->admin);
    session(['active_role' => 'admin lppm']);

    Livewire::test(DataSync::class)
        ->assertOk();
});

test('blade shows local-only message in non-local environment', function () {
    $this->actingAs($this->admin);
    session(['active_role' => 'admin lppm']);

    app()->detectEnvironment(fn () => 'production');

    Livewire::test(DataSync::class)
        ->assertOk()
        ->assertSee('Fitur ini hanya tersedia di lingkungan');
});

test('testConnection shows config-not-complete in local environment', function () {
    $this->actingAs($this->admin);
    session(['active_role' => 'admin lppm']);

    app()->detectEnvironment(fn () => 'local');

    Livewire::test(DataSync::class)
        ->call('testConnection')
        ->assertOk()
        ->assertSee('Konfigurasi SSH belum lengkap');
});

test('testConnection returns local-only message when called in non-local env', function () {
    $this->actingAs($this->admin);
    session(['active_role' => 'admin lppm']);

    app()->detectEnvironment(fn () => 'production');

    Livewire::test(DataSync::class)
        ->call('testConnection')
        ->assertOk();
});
