<?php

/**
 * Regression: BC-06 — satu aturan otorisasi untuk klasik, modern, dan API.
 *
 * Sebelum perbaikan:
 * - `ModernPageController` hanya cek role global → dosen tanpa relasi 200.
 * - `ProposalPolicy` tidak mengizinkan kaprodi → klasik 403, API 200.
 * - Reviewer yang tidak ditugaskan bisa membuka halaman modern.
 *
 * Test ini mengunci paritas: kaprodi prodi sama 200, prodi lain 403,
 * reviewer hanya bila ditugaskan, dosen tanpa relasi 403.
 */

use App\Enums\ReviewStatus;
use App\Models\Faculty;
use App\Models\Identity;
use App\Models\Institution;
use App\Models\Proposal;
use App\Models\ProposalReviewer;
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
    $this->facultyA = Faculty::factory()->create(['institution_id' => $institution->id]);
    $this->facultyB = Faculty::factory()->create(['institution_id' => $institution->id]);
    $this->prodiA = StudyProgram::factory()->create(['faculty_id' => $this->facultyA->id]);
    $this->prodiB = StudyProgram::factory()->create(['faculty_id' => $this->facultyB->id]);

    $this->submitter = bolaUser('dosen', $this->facultyA, $this->prodiA);
    $this->proposal = bolaProposal($this->submitter);

    $this->teammate = bolaUser('dosen', $this->facultyA, $this->prodiA);
    $this->proposal->teamMembers()->attach($this->teammate->id, [
        'role' => 'anggota',
        'status' => 'accepted',
    ]);

    $this->reviewer = bolaUser('reviewer', $this->facultyA, $this->prodiA);
    ProposalReviewer::create([
        'proposal_id' => $this->proposal->id,
        'user_id' => $this->reviewer->id,
        'status' => ReviewStatus::PENDING,
    ]);

    $this->unassignedReviewer = bolaUser('reviewer', $this->facultyB, $this->prodiB);
    $this->outsider = bolaUser('dosen', $this->facultyB, $this->prodiB);
    $this->dekanA = bolaUser('dekan', $this->facultyA, $this->prodiA);
    $this->dekanB = bolaUser('dekan', $this->facultyB, $this->prodiB);

    $this->kaprodiA = bolaUser('kaprodi', $this->facultyA, $this->prodiA);
    $this->prodiA->update(['kaprodi_user_id' => $this->kaprodiA->id]);

    $this->kaprodiB = bolaUser('kaprodi', $this->facultyB, $this->prodiB);
    $this->prodiB->update(['kaprodi_user_id' => $this->kaprodiB->id]);

    $this->admin = bolaUser('admin lppm', $this->facultyB, $this->prodiB);
});

function bolaUser(string $role, Faculty $faculty, StudyProgram $prodi): User
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

function bolaProposal(User $submitter): Proposal
{
    $research = Research::factory()->create();

    return Proposal::factory()->create([
        'submitter_id' => $submitter->id,
        'detailable_id' => $research->id,
        'detailable_type' => Research::class,
    ]);
}

function bolaModernSession(User $user, string $role)
{
    return test()->actingAs($user)->withSession([
        'active_role' => $role,
        'sim_lppm_app_theme' => 'modern',
    ]);
}

function bolaClassicSession(User $user, string $role)
{
    return test()->actingAs($user)->withSession([
        'active_role' => $role,
        'sim_lppm_app_theme' => 'classic',
    ]);
}

it('allows submitter and team member on modern proposal detail', function () {
    bolaModernSession($this->submitter, 'dosen')
        ->get(route('research.proposal.show.modern', $this->proposal))
        ->assertOk();

    bolaModernSession($this->teammate, 'dosen')
        ->get(route('research.proposal.show.modern', $this->proposal))
        ->assertOk();
});

it('blocks unrelated dosen from modern proposal detail', function () {
    bolaModernSession($this->outsider, 'dosen')
        ->get(route('research.proposal.show.modern', $this->proposal))
        ->assertForbidden();
});

it('allows assigned reviewer but blocks unassigned reviewer on proposal detail', function () {
    bolaModernSession($this->reviewer, 'reviewer')
        ->get(route('research.proposal.show.modern', $this->proposal))
        ->assertOk();

    bolaModernSession($this->unassignedReviewer, 'reviewer')
        ->get(route('research.proposal.show.modern', $this->proposal))
        ->assertForbidden();
});

it('allows kaprodi of the same study program on proposal detail', function () {
    bolaModernSession($this->kaprodiA, 'kaprodi')
        ->get(route('research.proposal.show.modern', $this->proposal))
        ->assertOk();

    bolaModernSession($this->kaprodiB, 'kaprodi')
        ->get(route('research.proposal.show.modern', $this->proposal))
        ->assertForbidden();
});

it('keeps dekan isolation on modern proposal detail', function () {
    bolaModernSession($this->dekanA, 'dekan')
        ->get(route('research.proposal.show.modern', $this->proposal))
        ->assertOk();

    bolaModernSession($this->dekanB, 'dekan')
        ->get(route('research.proposal.show.modern', $this->proposal))
        ->assertForbidden();
});

it('allows global roles on modern proposal detail', function () {
    bolaModernSession($this->admin, 'admin lppm')
        ->get(route('research.proposal.show.modern', $this->proposal))
        ->assertOk();
});

it('keeps classic and modern proposal authorization in parity', function () {
    bolaClassicSession($this->kaprodiA, 'kaprodi')
        ->get(route('research.proposal.show', $this->proposal))
        ->assertOk();

    bolaClassicSession($this->kaprodiB, 'kaprodi')
        ->get(route('research.proposal.show', $this->proposal))
        ->assertForbidden();

    bolaClassicSession($this->outsider, 'dosen')
        ->get(route('research.proposal.show', $this->proposal))
        ->assertForbidden();
});

it('blocks reviewer-only access to modern daily note', function () {
    bolaModernSession($this->reviewer, 'reviewer')
        ->get(route('research.daily-note.modern', $this->proposal))
        ->assertForbidden();

    bolaModernSession($this->kaprodiA, 'kaprodi')
        ->get(route('research.daily-note.modern', $this->proposal))
        ->assertOk();
});

it('enforces the same rules on proposal API', function () {
    $this->actingAs($this->outsider)
        ->getJson(route('research.proposal.show.v1', $this->proposal))
        ->assertForbidden();

    $this->actingAs($this->unassignedReviewer)
        ->getJson(route('research.proposal.show.v1', $this->proposal))
        ->assertForbidden();

    $this->actingAs($this->kaprodiA)
        ->getJson(route('research.proposal.show.v1', $this->proposal))
        ->assertOk();

    $this->actingAs($this->kaprodiB)
        ->getJson(route('research.proposal.show.v1', $this->proposal))
        ->assertForbidden();
});

it('enforces daily note rules on API', function () {
    $this->actingAs($this->reviewer)
        ->getJson(route('research.daily-notes.index', ['proposal_id' => $this->proposal->id]))
        ->assertForbidden();

    $this->actingAs($this->submitter)
        ->getJson(route('research.daily-notes.index', ['proposal_id' => $this->proposal->id]))
        ->assertOk();

    $this->actingAs($this->kaprodiA)
        ->getJson(route('research.daily-notes.index', ['proposal_id' => $this->proposal->id]))
        ->assertOk();
});
