<?php

// Vetted by SOSIOMEN - Manual Review Required by Senior Engineer/Manager

use App\Livewire\Settings\SettingsIndex;
use App\Models\Setting;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    Setting::clearCache();
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\RoleSeeder'])->run();
});

function deepLinkUser(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    test()->actingAs($user);
    session(['active_role' => $role]);

    return $user;
}

test('admin can deep-link straight to an admin settings tab via query string', function () {
    deepLinkUser('admin lppm');

    Livewire::withQueryParams(['tab' => 'pdf-export'])
        ->test(SettingsIndex::class)
        ->assertSet('activeTab', 'pdf-export');
});

test('admin can deep-link to the backup tab', function () {
    deepLinkUser('superadmin');

    Livewire::withQueryParams(['tab' => 'backup'])
        ->test(SettingsIndex::class)
        ->assertSet('activeTab', 'backup');
});

test('non admin cannot deep-link to an admin-only tab', function () {
    $user = User::factory()->create();
    $user->assignRole('dosen');

    $this->actingAs($user)
        ->get('/settings?tab=backup')
        ->assertForbidden();
});

test('non admin can still deep-link to their own tabs', function () {
    $user = User::factory()->create();
    $user->assignRole('dosen');

    $this->actingAs($user)
        ->get('/settings?tab=password')
        ->assertOk();
});

test('unknown tab falls back to the profile tab', function () {
    deepLinkUser('admin lppm');

    Livewire::withQueryParams(['tab' => 'tidak-ada-tab-ini'])
        ->test(SettingsIndex::class)
        ->assertSet('activeTab', 'profile');
});
