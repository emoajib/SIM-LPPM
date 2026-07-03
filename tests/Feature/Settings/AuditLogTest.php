<?php

use App\Livewire\Settings\AuditLog;
use App\Livewire\Settings\SettingsIndex;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('denies access to audit log component for non-admin users', function () {
    // ensure roles exist before trying to assign any
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\RoleSeeder'])->run();
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(AuditLog::class)
        ->assertForbidden();
});

it('allows admin to view audit logs and filter by user', function () {
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\RoleSeeder'])->run();
    $admin = User::factory()->create();
    $admin->assignRole('admin lppm');

    $userA = User::factory()->create(['name' => 'Alice']);
    $userB = User::factory()->create(['name' => 'Bob']);

    ActivityLog::create([
        'user_id' => $userA->id,
        'activity' => 'login',
        'description' => 'User logged in',
    ]);

    ActivityLog::create([
        'user_id' => $userB->id,
        'activity' => 'logout',
        'description' => 'User logged out',
    ]);

    $this->actingAs($admin);
    session(['active_role' => 'admin lppm']);

    // can open settings index and switch to audit tab
    $component = Livewire::test(SettingsIndex::class);
    $component->call('setActiveTab', 'audit')
        ->assertSee('Audit Log');

    // inspect the audit log itself
    Livewire::test(AuditLog::class)
        ->assertSee('Alice')
        ->assertSee('Bob')
        ->set('searchUser', 'Alice')
        ->assertSee('Alice')
        ->assertDontSee('Bob');
});

it('admin route is protected and accessible', function () {
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\RoleSeeder'])->run();
    $admin = User::factory()->create();
    $admin->assignRole('admin lppm');

    $this->actingAs($admin);
    session(['active_role' => 'admin lppm']);
    $this->get(route('admin-lppm.audit-log'))
        ->assertOk();

    $user = User::factory()->create();
    $this->actingAs($user);
    session()->forget('active_role');
    $this->get(route('admin-lppm.audit-log'))
        ->assertForbidden();
});

it('can filter by date range and ip address', function () {
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\RoleSeeder'])->run();
    $admin = User::factory()->create();
    $admin->assignRole('admin lppm');

    $user = User::factory()->create(['name' => 'Charlie']);
    // create logs across different dates and ip addresses
    $old = ActivityLog::create([
        'user_id' => $user->id,
        'activity' => 'login',
        'description' => 'old entry',
        'ip_address' => '192.168.1.1',
    ]);
    $old->created_at = now()->subDays(10);
    $old->save();

    $recent = ActivityLog::create([
        'user_id' => $user->id,
        'activity' => 'login',
        'description' => 'recent entry',
        'ip_address' => '10.0.0.5',
    ]);
    $recent->created_at = now()->subDays(1);
    $recent->save();

    session(['active_role' => 'admin lppm']);
    Livewire::actingAs($admin)
        ->test(AuditLog::class)
        ->set('dateFrom', now()->subDays(2)->format('Y-m-d'))
        ->set('dateTo', now()->format('Y-m-d'))
        ->assertSee('recent entry')
        ->assertDontSee('old entry')
        ->set('ipAddress', '10.0.0')
        ->assertSee('recent entry')
        ->assertDontSee('old entry');
});
