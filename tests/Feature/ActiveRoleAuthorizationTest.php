<?php

// Regression: BC-02

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;

uses(RefreshDatabase::class);

it('menu only shows items for active role', function () {
    // Run the database seeder to create roles
    $this->artisan('db:seed', ['--class' => 'Database\Seeders\RoleSeeder'])->run();

    // Create user with both admin lppm and dosen roles
    $user = User::factory()->create();
    $user->assignRole('admin lppm');
    $user->assignRole('dosen');

    // Test with admin lppm as active role
    Session::put('active_role', 'admin lppm');

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertStatus(200);

    // User with admin lppm active should see admin menu
    expect($user->activeHasRole('admin lppm'))->toBeTrue();
    expect($user->activeHasAnyRole(['admin lppm', 'dosen']))->toBeTrue();

    // Test with dosen as active role
    Session::put('active_role', 'dosen');

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertStatus(200);

    // User with dosen active should NOT see admin menu
    expect($user->activeHasRole('dosen'))->toBeTrue();
    expect($user->activeHasRole('admin lppm'))->toBeFalse();
    expect($user->activeHasAnyRole(['dosen']))->toBeTrue();
});

it('switches active role and authorization updates', function () {
    // Run the database seeder to create roles
    $this->artisan('db:seed', ['--class' => 'Database\Seeders\RoleSeeder'])->run();

    // Create user with both roles
    $user = User::factory()->create();
    $user->assignRole('admin lppm');
    $user->assignRole('dosen');

    // Set initial role
    Session::put('active_role', 'admin lppm');
    expect($user->activeRole())->toBe('admin lppm');

    // Switch role
    Session::put('active_role', 'dosen');
    expect($user->activeRole())->toBe('dosen');
    expect($user->activeHasRole('dosen'))->toBeTrue();
    expect($user->activeHasRole('admin lppm'))->toBeFalse();
});

it('activeHasRole methods work correctly', function () {
    // Run the database seeder to create roles
    $this->artisan('db:seed', ['--class' => 'Database\Seeders\RoleSeeder'])->run();

    // Create user with multiple roles
    $user = User::factory()->create();
    $user->assignRole('admin lppm');
    $user->assignRole('dosen');

    // Test with admin lppm active
    Session::put('active_role', 'admin lppm');

    expect($user->activeRole())->toBe('admin lppm');
    expect($user->activeHasRole('admin lppm'))->toBeTrue();
    expect($user->activeHasRole('dosen'))->toBeFalse();
    expect($user->activeHasAnyRole(['admin lppm', 'reviewer']))->toBeTrue();
    expect($user->activeHasAnyRole(['dosen', 'reviewer']))->toBeFalse();
    expect($user->activeHasAllRoles(['admin lppm']))->toBeTrue();
});
