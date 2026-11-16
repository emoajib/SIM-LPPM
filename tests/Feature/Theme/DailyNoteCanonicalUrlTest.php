<?php

/**
 * Regression: BC-01 — URL kanonik daily-note (Opsi A).
 *
 * Kanonik: /{module}/daily-note/{proposal} (Livewire klasik), berlaku di
 * kedua tema. Halaman React tetap di /{module}/daily-note/{proposal}/modern.
 * API `show_url` untuk sub-modul daily-note harus menunjuk URL kanonik.
 */

use App\Enums\ProposalStatus;
use App\Models\CommunityService;
use App\Models\DailyNote;
use App\Models\Faculty;
use App\Models\Identity;
use App\Models\Institution;
use App\Models\Proposal;
use App\Models\Research;
use App\Models\StudyProgram;
use App\Models\User;
use Database\Seeders\InstitutionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(InstitutionSeeder::class);

    $institution = Institution::first();
    $this->faculty = Faculty::factory()->create(['institution_id' => $institution->id]);
    $this->prodi = StudyProgram::factory()->create(['faculty_id' => $this->faculty->id]);

    $this->owner = User::factory()->create();
    $this->owner->assignRole('dosen');
    Identity::factory()->create([
        'user_id' => $this->owner->id,
        'faculty_id' => $this->faculty->id,
        'study_program_id' => $this->prodi->id,
    ]);

    $this->outsider = User::factory()->create();
    $this->outsider->assignRole('dosen');
    Identity::factory()->create([
        'user_id' => $this->outsider->id,
        'faculty_id' => $this->faculty->id,
        'study_program_id' => $this->prodi->id,
    ]);

    $research = Research::factory()->create();
    $this->researchProposal = Proposal::factory()->create([
        'submitter_id' => $this->owner->id,
        'detailable_id' => $research->id,
        'detailable_type' => Research::class,
        'status' => ProposalStatus::COMPLETED->value,
    ]);
    DailyNote::factory()->create(['proposal_id' => $this->researchProposal->id]);

    $community = CommunityService::factory()->create();
    $this->communityProposal = Proposal::factory()->create([
        'submitter_id' => $this->owner->id,
        'detailable_id' => $community->id,
        'detailable_type' => CommunityService::class,
        'status' => ProposalStatus::COMPLETED->value,
    ]);
    DailyNote::factory()->create(['proposal_id' => $this->communityProposal->id]);
});

it('API show_url daily-note memakai URL kanonik tanpa /modern', function (string $module, string $type) {
    $proposal = $module === 'research' ? $this->researchProposal : $this->communityProposal;

    $response = $this->actingAs($this->owner)
        ->get("/{$module}/api/v1/proposals?submodule=daily-note&type={$type}");

    $response->assertOk();

    $row = collect($response->json('data'))->firstWhere('id', $proposal->id);
    expect($row)->not->toBeNull();

    $expected = route("{$module}.daily-note.show", $proposal);
    expect($row['show_url'])->toBe($expected);
    expect($row['show_url'])->not->toContain('/modern');
})->with([
    ['research', 'research'],
    ['community-service', 'community-service'],
]);

it('URL kanonik daily-note dapat dibuka di kedua tema', function (string $module, string $theme) {
    $proposal = $module === 'research' ? $this->researchProposal : $this->communityProposal;

    $this->actingAs($this->owner)
        ->withSession(['active_role' => 'dosen', 'sim_lppm_app_theme' => $theme])
        ->get(route("{$module}.daily-note.show", $proposal))
        ->assertOk();
})->with([
    ['research', 'classic'],
    ['research', 'modern'],
    ['community-service', 'classic'],
    ['community-service', 'modern'],
]);

it('halaman modern daily-note tetap dapat dibuka di tema modern', function (string $module) {
    $proposal = $module === 'research' ? $this->researchProposal : $this->communityProposal;

    $this->actingAs($this->owner)
        ->withSession(['active_role' => 'dosen', 'sim_lppm_app_theme' => 'modern'])
        ->get(route("{$module}.daily-note.modern", $proposal))
        ->assertOk()
        ->assertSee('id="modern-root"', false);
})->with(['research', 'community-service']);

it('dosen lain tidak boleh membuka daily-note milik orang lain', function (string $module) {
    $proposal = $module === 'research' ? $this->researchProposal : $this->communityProposal;

    $this->actingAs($this->outsider)
        ->withSession(['active_role' => 'dosen', 'sim_lppm_app_theme' => 'classic'])
        ->get(route("{$module}.daily-note.show", $proposal))
        ->assertForbidden();
})->with(['research', 'community-service']);
