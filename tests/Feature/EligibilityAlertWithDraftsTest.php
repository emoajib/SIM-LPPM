<?php

use App\Enums\ProposalStatus;
use App\Models\Identity;
use App\Models\Proposal;
use App\Models\Research;
use App\Models\ResearchScheme;
use App\Models\Setting;
use App\Models\User;
use App\Services\EligibilityService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('shows eligibility in info banner when user is ineligible (SINTA) regardless of draft proposals', function () {
    $user = User::factory()->create();
    $user->assignRole('dosen');

    Identity::factory()->create([
        'user_id' => $user->id,
        'sinta_score_v3_overall' => 0.5,
    ]);

    ResearchScheme::factory()->create([
        'eligibility_rules' => [
            'min_sinta_score' => 3.0,
        ],
    ]);

    Setting::updateOrCreate(
        ['key' => 'research_proposal_start_date'],
        ['value' => now()->subDay()->toDateString()]
    );
    Setting::updateOrCreate(
        ['key' => 'research_proposal_end_date'],
        ['value' => now()->addDay()->toDateString()]
    );

    // User has a draft proposal
    $research = Research::factory()->create();
    Proposal::factory()->create([
        'submitter_id' => $user->id,
        'detailable_id' => $research->id,
        'detailable_type' => Research::class,
        'research_scheme_id' => ResearchScheme::first()->id,
        'status' => ProposalStatus::DRAFT,
    ]);

    $response = $this->actingAs($user)
        ->get(route('research.proposal.index'));

    $response->assertStatus(200);
    $response->assertSee('Status Kelayakan: Tidak Memenuhi Syarat');
});

it('shows eligibility in info banner when user has no submittable proposals', function () {
    $user = User::factory()->create();
    $user->assignRole('dosen');

    Identity::factory()->create([
        'user_id' => $user->id,
        'sinta_score_v3_overall' => 0.5,
    ]);

    ResearchScheme::factory()->create([
        'eligibility_rules' => ['min_sinta_score' => 3.0],
    ]);

    Setting::updateOrCreate(
        ['key' => 'research_proposal_start_date'],
        ['value' => now()->subDay()->toDateString()]
    );
    Setting::updateOrCreate(
        ['key' => 'research_proposal_end_date'],
        ['value' => now()->addDay()->toDateString()]
    );

    expect(Proposal::where('submitter_id', $user->id)->exists())->toBeFalse();

    $response = $this->actingAs($user)
        ->get(route('research.proposal.index'));

    $response->assertStatus(200);
    $response->assertSee('Status Kelayakan: Tidak Memenuhi Syarat');
});

it('keeps scheme eligibility strict for new proposal creation', function () {
    $user = User::factory()->create();
    $user->assignRole('dosen');

    $identity = Identity::factory()->create([
        'user_id' => $user->id,
        'sinta_score_v3_overall' => 0.5,
    ]);

    $researchScheme = ResearchScheme::factory()->create([
        'eligibility_rules' => [
            'min_sinta_score' => 3.0,
        ],
    ]);

    Setting::updateOrCreate(
        ['key' => 'research_proposal_start_date'],
        ['value' => now()->subDay()->toDateString()]
    );
    Setting::updateOrCreate(
        ['key' => 'research_proposal_end_date'],
        ['value' => now()->addDay()->toDateString()]
    );

    $eligibilityService = app(EligibilityService::class);
    $canSubmit = $eligibilityService->canSubmitResearchProposal($identity, $researchScheme);

    expect($canSubmit)->toBeFalse();

    $eligibleSchemes = $eligibilityService->getEligibleResearchSchemes($identity);
    expect($eligibleSchemes->contains($researchScheme))->toBeFalse();
});

it('shows eligibility in info banner for various proposal statuses', function () {
    $user = User::factory()->create();
    $user->assignRole('dosen');

    Identity::factory()->create([
        'user_id' => $user->id,
        'sinta_score_v3_overall' => 0.5,
    ]);

    $researchScheme = ResearchScheme::factory()->create(['eligibility_rules' => ['min_sinta_score' => 3.0]]);

    Setting::updateOrCreate(['key' => 'research_proposal_start_date'], ['value' => now()->subDay()->toDateString()]);
    Setting::updateOrCreate(['key' => 'research_proposal_end_date'], ['value' => now()->addDay()->toDateString()]);

    $research = Research::factory()->create();
    Proposal::factory()->create([
        'submitter_id' => $user->id,
        'detailable_id' => $research->id,
        'detailable_type' => Research::class,
        'research_scheme_id' => $researchScheme->id,
        'status' => ProposalStatus::NEED_ASSIGNMENT,
    ]);

    $response = $this->actingAs($user)->get(route('research.proposal.index'));
    $response->assertStatus(200);
    $response->assertSee('Status Kelayakan: Tidak Memenuhi Syarat');
});
