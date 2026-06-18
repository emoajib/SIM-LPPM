<?php

use App\Enums\ProposalStatus;
use App\Models\CommunityServiceScheme;
use App\Models\Identity;
use App\Models\Proposal;
use App\Models\QuotaMessage;
use App\Models\Research;
use App\Models\ResearchScheme;
use App\Models\Setting;
use App\Models\User;
use App\Services\EligibilityService;
use App\Services\QuotaMessageService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('counts draft proposals in head quota', function () {
    $user = User::factory()->create();
    $user->assignRole('dosen');

    Identity::factory()->create(['user_id' => $user->id]);

    $researchScheme = ResearchScheme::factory()->create([
        'eligibility_rules' => [
            'max_total_proposals_as_head' => 2,
        ],
    ]);

    // Create 2 draft proposals
    $research1 = Research::factory()->create();
    Proposal::factory()->create([
        'submitter_id' => $user->id,
        'detailable_id' => $research1->id,
        'detailable_type' => Research::class,
        'research_scheme_id' => $researchScheme->id,
        'status' => ProposalStatus::DRAFT,
    ]);

    $research2 = Research::factory()->create();
    Proposal::factory()->create([
        'submitter_id' => $user->id,
        'detailable_id' => $research2->id,
        'detailable_type' => Research::class,
        'research_scheme_id' => $researchScheme->id,
        'status' => ProposalStatus::DRAFT,
    ]);

    $eligibilityService = app(EligibilityService::class);
    $result = $eligibilityService->canCreateProposal($user, 'research');

    expect($result['can_create'])->toBeFalse();
    expect($result['quota_info']['head_current'])->toBe(2);
    expect($result['quota_info']['head_limit'])->toBe(2);
});

it('counts draft proposals in member quota', function () {
    $user = User::factory()->create();
    $user->assignRole('dosen');

    Identity::factory()->create(['user_id' => $user->id]);

    $researchScheme = ResearchScheme::factory()->create([
        'eligibility_rules' => [
            'max_total_proposals_as_member' => 2,
        ],
    ]);

    // Create 2 draft proposals where user is a member
    $research1 = Research::factory()->create();
    $proposal1 = Proposal::factory()->create([
        'detailable_id' => $research1->id,
        'detailable_type' => Research::class,
        'research_scheme_id' => $researchScheme->id,
        'status' => ProposalStatus::DRAFT,
    ]);

    $proposal1->teamMembers()->attach($user->id, [
        'role' => 'anggota',
        'status' => 'accepted',
        'tasks' => 'Research assistant',
    ]);

    $research2 = Research::factory()->create();
    $proposal2 = Proposal::factory()->create([
        'detailable_id' => $research2->id,
        'detailable_type' => Research::class,
        'research_scheme_id' => $researchScheme->id,
        'status' => ProposalStatus::DRAFT,
    ]);

    $proposal2->teamMembers()->attach($user->id, [
        'role' => 'anggota',
        'status' => 'accepted',
        'tasks' => 'Data analyst',
    ]);

    $eligibilityService = app(EligibilityService::class);
    $result = $eligibilityService->canCreateProposal($user, 'research');

    expect($result['can_create'])->toBeFalse();
    expect($result['quota_info']['member_current'])->toBe(2);
    expect($result['quota_info']['member_limit'])->toBe(2);
});

it('blocks proposal creation when head quota is full', function () {
    $user = User::factory()->create();
    $user->assignRole('dosen');

    Identity::factory()->create(['user_id' => $user->id]);

    $researchScheme = ResearchScheme::factory()->create([
        'eligibility_rules' => [
            'max_total_proposals_as_head' => 1,
        ],
    ]);

    // Create 1 draft proposal
    $research = Research::factory()->create();
    Proposal::factory()->create([
        'submitter_id' => $user->id,
        'detailable_id' => $research->id,
        'detailable_type' => Research::class,
        'research_scheme_id' => $researchScheme->id,
        'status' => ProposalStatus::DRAFT,
    ]);

    // Open the schedule
    Setting::updateOrCreate(
        ['key' => 'research_proposal_start_date'],
        ['value' => now()->subDay()->toDateString()]
    );
    Setting::updateOrCreate(
        ['key' => 'research_proposal_end_date'],
        ['value' => now()->addDay()->toDateString()]
    );

    // User tries to access create page
    $response = $this->actingAs($user)->get(route('research.proposal.create'));

    // Should redirect to index with error message
    $response->assertRedirect(route('research.proposal.index'));
    $response->assertSessionHas('error');
});

it('uses customizable quota messages from database', function () {
    // Create custom message in database
    QuotaMessage::create([
        'key' => 'button_tooltip',
        'message' => 'Custom: batas {limit} proposal tercapai!',
    ]);

    $messageService = app(QuotaMessageService::class);
    $message = $messageService->getMessage('button_tooltip', ['limit' => 5]);

    expect($message)->toBe('Custom: batas 5 proposal tercapai!');
});

it('falls back to default message when not in database', function () {
    $messageService = app(QuotaMessageService::class);
    $message = $messageService->getMessage('button_tooltip', ['limit' => 3]);

    expect($message)->toBe('Kuota terbatas: maksimal 3 usulan sebagai ketua aktif (termasuk draft)');
});

it('shows correct quota status with mixed draft and submitted proposals', function () {
    $user = User::factory()->create();
    $user->assignRole('dosen');

    Identity::factory()->create(['user_id' => $user->id]);

    $researchScheme = ResearchScheme::factory()->create([
        'eligibility_rules' => [
            'max_total_proposals_as_head' => 3,
        ],
    ]);

    // Create 1 draft and 1 submitted proposal
    $research1 = Research::factory()->create();
    Proposal::factory()->create([
        'submitter_id' => $user->id,
        'detailable_id' => $research1->id,
        'detailable_type' => Research::class,
        'research_scheme_id' => $researchScheme->id,
        'status' => ProposalStatus::DRAFT,
    ]);

    $research2 = Research::factory()->create();
    Proposal::factory()->create([
        'submitter_id' => $user->id,
        'detailable_id' => $research2->id,
        'detailable_type' => Research::class,
        'research_scheme_id' => $researchScheme->id,
        'status' => ProposalStatus::SUBMITTED,
    ]);

    $eligibilityService = app(EligibilityService::class);
    $result = $eligibilityService->canCreateProposal($user, 'research');

    expect($result['can_create'])->toBeTrue();
    expect($result['quota_info']['head_current'])->toBe(2);
    expect($result['quota_info']['head_limit'])->toBe(3);
});

it('separates research and community service quotas', function () {
    $user = User::factory()->create();
    $user->assignRole('dosen');

    Identity::factory()->create(['user_id' => $user->id]);

    $researchScheme = ResearchScheme::factory()->create([
        'eligibility_rules' => [
            'max_total_proposals_as_head' => 1,
        ],
    ]);

    $pkmScheme = CommunityServiceScheme::create([
        'name' => 'PKM Internal',
        'strata' => 'Internal',
        'eligibility_rules' => [
            'max_total_proposals_as_head' => 1,
        ],
    ]);

    // Create 1 research draft
    $research = Research::factory()->create();
    Proposal::factory()->create([
        'submitter_id' => $user->id,
        'detailable_id' => $research->id,
        'detailable_type' => Research::class,
        'research_scheme_id' => $researchScheme->id,
        'status' => ProposalStatus::DRAFT,
    ]);

    $eligibilityService = app(EligibilityService::class);

    // Research quota should be full
    $researchResult = $eligibilityService->canCreateProposal($user, 'research');
    expect($researchResult['can_create'])->toBeFalse();
    expect($researchResult['quota_info']['head_current'])->toBe(1);

    // Community service quota should still be available
    $pkmResult = $eligibilityService->canCreateProposal($user, 'community-service');
    expect($pkmResult['can_create'])->toBeTrue();
    expect($pkmResult['quota_info']['head_current'])->toBe(0);
});

it('includes all active statuses including DRAFT in quota calculation', function () {
    $user = User::factory()->create();
    $user->assignRole('dosen');

    Identity::factory()->create(['user_id' => $user->id]);

    $researchScheme = ResearchScheme::factory()->create([
        'eligibility_rules' => [
            'max_total_proposals_as_head' => 5,
        ],
    ]);

    $statuses = [
        ProposalStatus::DRAFT,
        ProposalStatus::SUBMITTED,
        ProposalStatus::NEED_ASSIGNMENT,
        ProposalStatus::APPROVED,
        ProposalStatus::REVISION_NEEDED,
    ];

    foreach ($statuses as $status) {
        $research = Research::factory()->create();
        Proposal::factory()->create([
            'submitter_id' => $user->id,
            'detailable_id' => $research->id,
            'detailable_type' => Research::class,
            'research_scheme_id' => $researchScheme->id,
            'status' => $status,
        ]);
    }

    $eligibilityService = app(EligibilityService::class);
    $result = $eligibilityService->canCreateProposal($user, 'research');

    expect($result['can_create'])->toBeFalse();
    expect($result['quota_info']['head_current'])->toBe(5);
    expect($result['quota_info']['head_limit'])->toBe(5);
});
