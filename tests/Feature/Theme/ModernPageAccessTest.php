<?php

/**
 * Regression: BC-01 — otorisasi halaman modern (ModernPageController).
 *
 * Sebelum refactor, aturan dekan/kaprodi ditulis sebagai closure duplikat di
 * routes/web.php. Test ini menjaga agar isolasi fakultas/prodi tetap berlaku
 * untuk halaman modern detail proposal dan realisasi anggaran.
 */

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

    $this->institution = Institution::first();
    $this->facultyA = Faculty::factory()->create(['institution_id' => $this->institution->id]);
    $this->facultyB = Faculty::factory()->create(['institution_id' => $this->institution->id]);
    $this->prodiA = StudyProgram::factory()->create(['faculty_id' => $this->facultyA->id]);
    $this->prodiB = StudyProgram::factory()->create(['faculty_id' => $this->facultyB->id]);

    $this->submitter = makeUser('dosen', $this->facultyA, $this->prodiA);
    $this->proposal = makeProposal($this->submitter);
});

function makeUser(string $role, Faculty $faculty, StudyProgram $prodi): User
{
    $user = User::factory()->create();
    $user->assignRole($role);
    Identity::factory()->create([
        'user_id' => $user->id,
        'faculty_id' => $faculty->id,
        'study_program_id' => $prodi->id,
    ]);

    return $user;
}

function makeProposal(User $submitter): Proposal
{
    $research = Research::factory()->create();

    return Proposal::factory()->create([
        'submitter_id' => $submitter->id,
        'detailable_id' => $research->id,
        'detailable_type' => Research::class,
    ]);
}

it('allows dekan from the same faculty', function () {
    $dekan = makeUser('dekan', $this->facultyA, $this->prodiA);

    $this->actingAs($dekan)
        ->withSession(['active_role' => 'dekan', 'sim_lppm_app_theme' => 'modern'])
        ->get(route('research.proposal.show.modern', $this->proposal))
        ->assertStatus(200);
});

it('blocks dekan from another faculty', function () {
    $dekan = makeUser('dekan', $this->facultyB, $this->prodiB);

    $this->actingAs($dekan)
        ->withSession(['active_role' => 'dekan', 'sim_lppm_app_theme' => 'modern'])
        ->get(route('research.proposal.show.modern', $this->proposal))
        ->assertStatus(403);
});

it('allows kaprodi of the same study program', function () {
    $kaprodi = makeUser('kaprodi', $this->facultyA, $this->prodiA);
    $this->prodiA->update(['kaprodi_user_id' => $kaprodi->id]);

    $this->actingAs($kaprodi)
        ->withSession(['active_role' => 'kaprodi', 'sim_lppm_app_theme' => 'modern'])
        ->get(route('research.daily-note.modern', $this->proposal))
        ->assertStatus(200);
});

it('blocks kaprodi from another study program', function () {
    $kaprodi = makeUser('kaprodi', $this->facultyB, $this->prodiB);
    $this->prodiB->update(['kaprodi_user_id' => $kaprodi->id]);

    $this->actingAs($kaprodi)
        ->withSession(['active_role' => 'kaprodi', 'sim_lppm_app_theme' => 'modern'])
        ->get(route('research.daily-note.modern', $this->proposal))
        ->assertStatus(403);
});

it('allows team member to open modern daily note', function () {
    $member = makeUser('dosen', $this->facultyA, $this->prodiA);
    $this->proposal->teamMembers()->attach($member->id, ['role' => 'anggota', 'status' => 'accepted']);

    $response = $this->actingAs($member)
        ->withSession(['active_role' => 'dosen', 'sim_lppm_app_theme' => 'modern'])
        ->get(route('research.daily-note.modern', $this->proposal));

    $response->assertStatus(200);
});

it('allows reviewer to read modern proposal detail', function () {
    $reviewer = User::factory()->create();
    $reviewer->assignRole('reviewer');

    $this->actingAs($reviewer)
        ->withSession(['active_role' => 'reviewer', 'sim_lppm_app_theme' => 'modern'])
        ->get(route('research.proposal.show.modern', $this->proposal))
        ->assertStatus(200);
});

it('redirects guest to login', function () {
    $this->get(route('research.proposal.show.modern', $this->proposal))
        ->assertRedirect(route('login'));
});
