<?php

use App\Actions\Kaprodi\KaprodiApprovalAction;
use App\Enums\KaprodiStatus;
use App\Enums\ProposalStatus;
use App\Livewire\Actions\SubmitProposalAction;
use App\Models\Faculty;
use App\Models\Identity;
use App\Models\Institution;
use App\Models\Proposal;
use App\Models\Research;
use App\Models\ResearchScheme;
use App\Models\Setting;
use App\Models\StudyProgram;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function createKaprodiScenario(): array
{
    $institution = Institution::factory()->create();
    $faculty = Faculty::factory()->create(['institution_id' => $institution->id]);
    $studyProgram = StudyProgram::factory()->create([
        'institution_id' => $institution->id,
        'faculty_id' => $faculty->id,
    ]);

    $kaprodi = User::factory()->create(['name' => 'Kaprodi Test']);
    $kaprodi->assignRole('kaprodi');
    $kaprodi->assignRole('dosen');
    Identity::factory()->create([
        'user_id' => $kaprodi->id,
        'study_program_id' => $studyProgram->id,
        'faculty_id' => $faculty->id,
        'institution_id' => $institution->id,
    ]);

    $studyProgram->update(['kaprodi_user_id' => $kaprodi->id]);

    $dosen = User::factory()->create(['name' => 'Dosen Test']);
    $dosen->assignRole('dosen');
    Identity::factory()->create([
        'user_id' => $dosen->id,
        'study_program_id' => $studyProgram->id,
        'faculty_id' => $faculty->id,
        'institution_id' => $institution->id,
    ]);

    return [
        'kaprodi' => $kaprodi,
        'dosen' => $dosen,
        'studyProgram' => $studyProgram,
        'faculty' => $faculty,
        'institution' => $institution,
    ];
}

it('allows kaprodi to approve a proposal', function () {
    $scenario = createKaprodiScenario();
    $kaprodi = $scenario['kaprodi'];
    $dosen = $scenario['dosen'];

    $researchScheme = ResearchScheme::factory()->create();
    $research = Research::factory()->create();
    $proposal = Proposal::factory()->create([
        'submitter_id' => $dosen->id,
        'detailable_id' => $research->id,
        'detailable_type' => Research::class,
        'research_scheme_id' => $researchScheme->id,
        'status' => ProposalStatus::DRAFT,
    ]);

    $proposal->teamMembers()->attach($dosen->id, [
        'role' => 'ketua',
        'status' => 'accepted',
        'tasks' => 'Principal Investigator',
    ]);

    $action = app(KaprodiApprovalAction::class);

    $requestResult = $action->requestApproval($proposal, $kaprodi);
    expect($requestResult['success'])->toBeTrue();

    $approveResult = $action->approve($proposal, $kaprodi, 'Proposal sesuai dengan roadmap prodi.');
    expect($approveResult['success'])->toBeTrue();
    expect($approveResult['approval']->status)->toBe(KaprodiStatus::APPROVED);
    expect($approveResult['approval']->approved_at)->not()->toBeNull();

    expect($proposal->fresh()->hasApprovedKaprodi())->toBeTrue();
});

it('allows kaprodi to reject a proposal', function () {
    $scenario = createKaprodiScenario();
    $kaprodi = $scenario['kaprodi'];
    $dosen = $scenario['dosen'];

    $researchScheme = ResearchScheme::factory()->create();
    $research = Research::factory()->create();
    $proposal = Proposal::factory()->create([
        'submitter_id' => $dosen->id,
        'detailable_id' => $research->id,
        'detailable_type' => Research::class,
        'research_scheme_id' => $researchScheme->id,
        'status' => ProposalStatus::DRAFT,
    ]);

    $proposal->teamMembers()->attach($dosen->id, [
        'role' => 'ketua',
        'status' => 'accepted',
        'tasks' => 'Principal Investigator',
    ]);

    $action = app(KaprodiApprovalAction::class);

    $action->requestApproval($proposal, $kaprodi);

    $rejectResult = $action->reject($proposal, $kaprodi, 'Topik tidak sesuai dengan fokus prodi.');
    expect($rejectResult['success'])->toBeTrue();
    expect($rejectResult['approval']->status)->toBe(KaprodiStatus::REJECTED);
    expect($rejectResult['approval']->rejected_at)->not()->toBeNull();

    $kaprodiStatus = $proposal->fresh()->kaprodiApprovalStatus();
    expect($kaprodiStatus)->toBe(KaprodiStatus::REJECTED);
});

it('prevents non-kaprodi from approving proposals', function () {
    $scenario = createKaprodiScenario();
    $kaprodi = $scenario['kaprodi'];
    $dosen = $scenario['dosen'];

    $researchScheme = ResearchScheme::factory()->create();
    $research = Research::factory()->create();
    $proposal = Proposal::factory()->create([
        'submitter_id' => $dosen->id,
        'detailable_id' => $research->id,
        'detailable_type' => Research::class,
        'research_scheme_id' => $researchScheme->id,
        'status' => ProposalStatus::DRAFT,
    ]);

    $proposal->teamMembers()->attach($dosen->id, [
        'role' => 'ketua',
        'status' => 'accepted',
        'tasks' => 'Principal Investigator',
    ]);

    $otherUser = User::factory()->create();
    $otherUser->assignRole('dosen');

    $action = app(KaprodiApprovalAction::class);

    $action->requestApproval($proposal, $kaprodi);

    $approveResult = $action->approve($proposal, $otherUser);
    expect($approveResult['success'])->toBeFalse();
});

it('prevents kaprodi from approving proposals from other study programs', function () {
    $scenario1 = createKaprodiScenario();
    $kaprodi = $scenario1['kaprodi'];

    $institution2 = Institution::factory()->create();
    $faculty2 = Faculty::factory()->create(['institution_id' => $institution2->id]);
    $studyProgram2 = StudyProgram::factory()->create([
        'institution_id' => $institution2->id,
        'faculty_id' => $faculty2->id,
    ]);

    $dosen2 = User::factory()->create(['name' => 'Dosen Other Prodi']);
    $dosen2->assignRole('dosen');
    Identity::factory()->create([
        'user_id' => $dosen2->id,
        'study_program_id' => $studyProgram2->id,
        'faculty_id' => $faculty2->id,
        'institution_id' => $institution2->id,
    ]);

    $researchScheme = ResearchScheme::factory()->create();
    $research = Research::factory()->create();
    $proposal = Proposal::factory()->create([
        'submitter_id' => $dosen2->id,
        'detailable_id' => $research->id,
        'detailable_type' => Research::class,
        'research_scheme_id' => $researchScheme->id,
        'status' => ProposalStatus::DRAFT,
    ]);

    $proposal->teamMembers()->attach($dosen2->id, [
        'role' => 'ketua',
        'status' => 'accepted',
        'tasks' => 'Principal Investigator',
    ]);

    $action = app(KaprodiApprovalAction::class);

    $approveResult = $action->approve($proposal, $kaprodi);
    expect($approveResult['success'])->toBeFalse();
});

it('blocks proposal submission without kaprodi approval when feature is active', function () {
    Setting::set('feature_kaprodi_validation', true, 'boolean');

    $scenario = createKaprodiScenario();
    $dosen = $scenario['dosen'];

    $researchScheme = ResearchScheme::factory()->create();
    $research = Research::factory()->create();
    $proposal = Proposal::factory()->create([
        'submitter_id' => $dosen->id,
        'detailable_id' => $research->id,
        'detailable_type' => Research::class,
        'research_scheme_id' => $researchScheme->id,
        'status' => ProposalStatus::DRAFT,
    ]);

    $proposal->teamMembers()->attach($dosen->id, [
        'role' => 'ketua',
        'status' => 'accepted',
        'tasks' => 'Principal Investigator',
    ]);

    $action = app(KaprodiApprovalAction::class);
    $canSubmit = $action->canSubmit($proposal);

    expect($canSubmit['can_submit'])->toBeFalse();
    expect($canSubmit['reason'])->toContain('harus disetujui Kaprodi');
});

it('allows proposal submission after kaprodi approval', function () {
    Setting::set('feature_kaprodi_validation', true, 'boolean');

    $scenario = createKaprodiScenario();
    $kaprodi = $scenario['kaprodi'];
    $dosen = $scenario['dosen'];

    $researchScheme = ResearchScheme::factory()->create();
    $research = Research::factory()->create();
    $proposal = Proposal::factory()->create([
        'submitter_id' => $dosen->id,
        'detailable_id' => $research->id,
        'detailable_type' => Research::class,
        'research_scheme_id' => $researchScheme->id,
        'status' => ProposalStatus::DRAFT,
    ]);

    $proposal->teamMembers()->attach($dosen->id, [
        'role' => 'ketua',
        'status' => 'accepted',
        'tasks' => 'Principal Investigator',
    ]);

    $action = app(KaprodiApprovalAction::class);

    $action->requestApproval($proposal, $kaprodi);
    $action->approve($proposal, $kaprodi);

    $canSubmit = $action->canSubmit($proposal);
    expect($canSubmit['can_submit'])->toBeTrue();
    expect($canSubmit['reason'])->toBeNull();
});

it('blocks submission when kaprodi approval is pending', function () {
    $scenario = createKaprodiScenario();
    $kaprodi = $scenario['kaprodi'];
    $dosen = $scenario['dosen'];

    $researchScheme = ResearchScheme::factory()->create();
    $research = Research::factory()->create();
    $proposal = Proposal::factory()->create([
        'submitter_id' => $dosen->id,
        'detailable_id' => $research->id,
        'detailable_type' => Research::class,
        'research_scheme_id' => $researchScheme->id,
        'status' => ProposalStatus::DRAFT,
    ]);

    $proposal->teamMembers()->attach($dosen->id, [
        'role' => 'ketua',
        'status' => 'accepted',
        'tasks' => 'Principal Investigator',
    ]);

    $action = app(KaprodiApprovalAction::class);
    $action->requestApproval($proposal, $kaprodi);

    $canSubmit = $action->canSubmit($proposal);
    expect($canSubmit['can_submit'])->toBeFalse();
    expect($canSubmit['reason'])->toContain('menunggu persetujuan');
});

it('blocks submission when kaprodi rejected the proposal', function () {
    $scenario = createKaprodiScenario();
    $kaprodi = $scenario['kaprodi'];
    $dosen = $scenario['dosen'];

    $researchScheme = ResearchScheme::factory()->create();
    $research = Research::factory()->create();
    $proposal = Proposal::factory()->create([
        'submitter_id' => $dosen->id,
        'detailable_id' => $research->id,
        'detailable_type' => Research::class,
        'research_scheme_id' => $researchScheme->id,
        'status' => ProposalStatus::DRAFT,
    ]);

    $proposal->teamMembers()->attach($dosen->id, [
        'role' => 'ketua',
        'status' => 'accepted',
        'tasks' => 'Principal Investigator',
    ]);

    $action = app(KaprodiApprovalAction::class);

    $action->requestApproval($proposal, $kaprodi);
    $action->reject($proposal, $kaprodi, 'Topik tidak sesuai.');

    $canSubmit = $action->canSubmit($proposal);
    expect($canSubmit['can_submit'])->toBeFalse();
    expect($canSubmit['reason'])->toContain('ditolak');
});

it('allows submission when no kaprodi is assigned to study program', function () {
    $institution = Institution::factory()->create();
    $faculty = Faculty::factory()->create(['institution_id' => $institution->id]);
    $studyProgram = StudyProgram::factory()->create([
        'institution_id' => $institution->id,
        'faculty_id' => $faculty->id,
        'kaprodi_user_id' => null,
    ]);

    $dosen = User::factory()->create(['name' => 'Dosen Test']);
    $dosen->assignRole('dosen');
    Identity::factory()->create([
        'user_id' => $dosen->id,
        'study_program_id' => $studyProgram->id,
        'faculty_id' => $faculty->id,
        'institution_id' => $institution->id,
    ]);

    $researchScheme = ResearchScheme::factory()->create();
    $research = Research::factory()->create();
    $proposal = Proposal::factory()->create([
        'submitter_id' => $dosen->id,
        'detailable_id' => $research->id,
        'detailable_type' => Research::class,
        'research_scheme_id' => $researchScheme->id,
        'status' => ProposalStatus::DRAFT,
    ]);

    $proposal->teamMembers()->attach($dosen->id, [
        'role' => 'ketua',
        'status' => 'accepted',
        'tasks' => 'Principal Investigator',
    ]);

    $action = app(KaprodiApprovalAction::class);
    $canSubmit = $action->canSubmit($proposal);

    expect($canSubmit['can_submit'])->toBeTrue();
});

it('prevents duplicate kaprodi approval requests', function () {
    $scenario = createKaprodiScenario();
    $kaprodi = $scenario['kaprodi'];
    $dosen = $scenario['dosen'];

    $researchScheme = ResearchScheme::factory()->create();
    $research = Research::factory()->create();
    $proposal = Proposal::factory()->create([
        'submitter_id' => $dosen->id,
        'detailable_id' => $research->id,
        'detailable_type' => Research::class,
        'research_scheme_id' => $researchScheme->id,
        'status' => ProposalStatus::DRAFT,
    ]);

    $proposal->teamMembers()->attach($dosen->id, [
        'role' => 'ketua',
        'status' => 'accepted',
        'tasks' => 'Principal Investigator',
    ]);

    $action = app(KaprodiApprovalAction::class);

    $firstRequest = $action->requestApproval($proposal, $kaprodi);
    expect($firstRequest['success'])->toBeTrue();

    $secondRequest = $action->requestApproval($proposal, $kaprodi);
    expect($secondRequest['success'])->toBeFalse();
    expect($secondRequest['message'])->toContain('menunggu persetujuan');
});

it('integrates kaprodi check with SubmitProposalAction when feature is active', function () {
    Setting::set('feature_kaprodi_validation', true, 'boolean');

    $scenario = createKaprodiScenario();
    $dosen = $scenario['dosen'];

    $researchScheme = ResearchScheme::factory()->create();
    $research = Research::factory()->create();
    $proposal = Proposal::factory()->create([
        'submitter_id' => $dosen->id,
        'detailable_id' => $research->id,
        'detailable_type' => Research::class,
        'research_scheme_id' => $researchScheme->id,
        'status' => ProposalStatus::DRAFT,
    ]);

    $proposal->teamMembers()->attach($dosen->id, [
        'role' => 'ketua',
        'status' => 'accepted',
        'tasks' => 'Principal Investigator',
    ]);

    $submitAction = app(SubmitProposalAction::class);
    $this->actingAs($dosen);

    $result = $submitAction->execute($proposal);
    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('Kaprodi');
});

it('allows submission when kaprodi validation feature is disabled', function () {
    Setting::set('feature_kaprodi_validation', false, 'boolean');

    $scenario = createKaprodiScenario();
    $dosen = $scenario['dosen'];

    $researchScheme = ResearchScheme::factory()->create();
    $research = Research::factory()->create();
    $proposal = Proposal::factory()->create([
        'submitter_id' => $dosen->id,
        'detailable_id' => $research->id,
        'detailable_type' => Research::class,
        'research_scheme_id' => $researchScheme->id,
        'status' => ProposalStatus::DRAFT,
    ]);

    $proposal->teamMembers()->attach($dosen->id, [
        'role' => 'ketua',
        'status' => 'accepted',
        'tasks' => 'Principal Investigator',
    ]);

    $proposal->budgetItems()->create([
        'year' => 1,
        'group' => 'Honorarium',
        'component' => 'Honor Ketua',
        'item_description' => 'Biaya operasional',
        'volume' => 1,
        'unit_price' => 1000000,
        'total_price' => 1000000,
    ]);

    $submitAction = app(SubmitProposalAction::class);
    $this->actingAs($dosen);

    $result = $submitAction->execute($proposal);
    expect($result['success'])->toBeTrue();
});
