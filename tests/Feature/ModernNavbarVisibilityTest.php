<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RoleSeeder::class));

/**
 * Regresi: utility `.collapse` milik Tailwind (visibility: collapse) bertabrakan
 * dengan komponen `.collapse` Bootstrap/Tabler. Akibatnya menu navbar ter-render
 * di DOM (tinggi ~89px) tetapi TIDAK TERLIHAT di tema modern.
 * Override ini wajib tetap ada di resources/css/modern.css.
 */
it('neutralizes tailwind collapse utility so tabler navbar stays visible', function () {
    $css = file_get_contents(resource_path('css/modern.css'));

    expect($css)->toContain('.collapse { visibility: visible; }');
});

it('renders the navbar menu container with module links on research page', function () {
    $user = User::factory()->create();
    $user->assignRole('dosen');

    $response = $this->actingAs($user)
        ->withSession(['active_role' => 'dosen', 'sim_lppm_app_theme' => 'modern'])
        ->get(route('research.proposal.index'));

    $response->assertStatus(200);
    $response->assertSee('id="navbar-menu"', false);
    $response->assertSee('class="collapse navbar-collapse"', false);
    $response->assertSee('navbar-nav', false);
    $response->assertSee('Penelitian');
});

it('renders the navbar menu container with module links on community-service page', function () {
    $user = User::factory()->create();
    $user->assignRole('dosen');

    $response = $this->actingAs($user)
        ->withSession(['active_role' => 'dosen', 'sim_lppm_app_theme' => 'modern'])
        ->get(route('community-service.proposal.index'));

    $response->assertStatus(200);
    $response->assertSee('id="navbar-menu"', false);
    $response->assertSee('class="collapse navbar-collapse"', false);
    $response->assertSee('navbar-nav', false);
    $response->assertSee('Pengabdian');
});

it('does not force the navbar collapse open with inline styles', function () {
    $header = file_get_contents(resource_path('views/components/layouts/header.blade.php'));

    expect($header)->not->toContain('id="navbar-menu" style=');
});
