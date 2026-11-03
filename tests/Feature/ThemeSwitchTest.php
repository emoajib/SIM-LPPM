<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(\Database\Seeders\RoleSeeder::class));

it('theme switch endpoint toggles and redirects', function () {
    $user = User::factory()->create();
    $user->assignRole('dosen');
    $this->actingAs($user)->withSession(['active_role' => 'dosen'])
        ->get(route('theme.switch', 'classic'))
        ->assertRedirect();
});

it('research index returns 200 in classic theme', function () {
    $user = User::factory()->create();
    $user->assignRole('dosen');
    $this->actingAs($user)->withSession(['active_role' => 'dosen', 'sim_lppm_app_theme' => 'classic'])
        ->get(route('research.proposal.index'))
        ->assertStatus(200);
});

it('research index returns 200 in modern theme', function () {
    $user = User::factory()->create();
    $user->assignRole('dosen');
    $this->actingAs($user)->withSession(['active_role' => 'dosen', 'sim_lppm_app_theme' => 'modern'])
        ->get(route('research.proposal.index'))
        ->assertStatus(200);
});

it('navbar menu not empty for dosen on research page', function () {
    $user = User::factory()->create();
    $user->assignRole('dosen');
    $response = $this->actingAs($user)->withSession(['active_role' => 'dosen', 'sim_lppm_app_theme' => 'modern'])
        ->get(route('research.proposal.index'));
    $response->assertStatus(200);
    $response->assertSee('Dashboard');
});
