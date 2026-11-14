<?php

use App\Models\Proposal;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RoleSeeder::class));

/**
 * Regression: BC-01 — setiap halaman modern wajib punya fallback klasik,
 * dan sebaliknya. Mencegah halaman blank / dead-end saat ganti tema.
 */
it('renders classic blade (no modern-root) for index pages in classic theme', function (string $routeName) {
    $user = User::factory()->create();
    $user->assignRole('dosen');

    $response = $this->actingAs($user)
        ->withSession(['active_role' => 'dosen', 'sim_lppm_app_theme' => 'classic'])
        ->get(route($routeName));

    $response->assertStatus(200);
    $response->assertDontSee('id="modern-root"', false);
})->with([
    'research.proposal.index',
    'research.proposal-revision.index',
    'research.final-report.index',
    'research.daily-note.index',
    'community-service.proposal.index',
    'community-service.proposal-revision.index',
    'community-service.final-report.index',
    'community-service.daily-note.index',
]);

it('renders modern root for index pages in modern theme', function (string $routeName) {
    $user = User::factory()->create();
    $user->assignRole('dosen');

    $response = $this->actingAs($user)
        ->withSession(['active_role' => 'dosen', 'sim_lppm_app_theme' => 'modern'])
        ->get(route($routeName));

    $response->assertStatus(200);
    $response->assertSee('id="modern-root"', false);
})->with([
    'research.proposal.index',
    'research.proposal-revision.index',
    'research.final-report.index',
    'research.daily-note.index',
    'community-service.proposal.index',
    'community-service.proposal-revision.index',
    'community-service.final-report.index',
    'community-service.daily-note.index',
]);

it('redirects modern-only daily-note route to classic in classic theme', function () {
    $user = User::factory()->create();
    $user->assignRole('dosen');
    $proposal = Proposal::factory()->create(['submitter_id' => $user->id]);

    $response = $this->actingAs($user)
        ->withSession(['active_role' => 'dosen', 'sim_lppm_app_theme' => 'classic'])
        ->get(route('research.daily-note.modern', $proposal));

    $response->assertRedirect(route('research.daily-note.show', $proposal));
});

it('redirects modern-only proposal-show route to classic in classic theme', function () {
    $user = User::factory()->create();
    $user->assignRole('dosen');
    $proposal = Proposal::factory()->create(['submitter_id' => $user->id]);

    $response = $this->actingAs($user)
        ->withSession(['active_role' => 'dosen', 'sim_lppm_app_theme' => 'classic'])
        ->get(route('research.proposal.show.modern', $proposal));

    $response->assertRedirect(route('research.proposal.show', $proposal));
});

it('redirects verifikasi-luaran route to accreditation.verification in classic theme', function () {
    $user = User::factory()->create();
    $user->assignRole('dosen');

    $response = $this->actingAs($user)
        ->withSession(['active_role' => 'dosen', 'sim_lppm_app_theme' => 'classic'])
        ->get(route('research.verifikasi-luaran.page'));

    $response->assertRedirect(route('accreditation.verification'));
});
