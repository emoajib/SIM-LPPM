<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RoleSeeder::class));

/**
 * Regression: BC-01 Theme boundary leak — tab bar modern (partial module-tabs)
 * tidak boleh muncul di tema klasik. Sebelumnya 2 halaman (usulan penelitian &
 * pengabdian) meng-include partial tanpa guard `@if (is_modern_theme())`.
 */
it('does not render the modern module-tabs bar in classic theme', function (string $routeName) {
    $user = User::factory()->create();
    $user->assignRole('dosen');

    $response = $this->actingAs($user)
        ->withSession(['active_role' => 'dosen', 'sim_lppm_app_theme' => 'classic'])
        ->get(route($routeName));

    $response->assertStatus(200);
    // marker unik partial module-tabs
    $response->assertDontSee('bg-white border border-slate-200 rounded-xl shadow-sm px-4 pt-3 mb-3', false);
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

it('keeps sidebar navigation to all module destinations in classic theme', function () {
    $user = User::factory()->create();
    $user->assignRole('dosen');

    $response = $this->actingAs($user)
        ->withSession(['active_role' => 'dosen', 'sim_lppm_app_theme' => 'classic'])
        ->get(route('research.proposal.index'));

    $response->assertStatus(200);
    foreach (['Perbaikan Usulan', 'Laporan Akhir', 'Realisasi Anggaran', 'Verifikasi Luaran'] as $label) {
        $response->assertSee($label);
    }
});

it('still renders modern root in modern theme', function (string $routeName) {
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
