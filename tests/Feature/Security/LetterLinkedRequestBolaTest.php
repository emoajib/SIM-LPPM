<?php

use App\Livewire\Dashboard\Dosen\LetterProposalLinkedRequest;
use App\Models\LetterType;
use App\Models\Proposal;
use App\Models\User;
use App\Services\LetterService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'Database\Seeders\RoleSeeder'])->run();
});

/**
 * Regression tests for BOLA (broken object level authorization) in
 * LetterProposalLinkedRequest: a non-submitter, non-team-member user must
 * not be able to open the "Buat Surat dari Proposal" form for someone
 * else's proposal, nor create a letter referencing it.
 */
it('non-owner cannot open letter request form for someone elses proposal', function () {
    $proposal = Proposal::factory()->create();
    $proposal->update(['submitter_id' => User::factory()->create()->id]);

    // Attacker: a different dosen (not submitter, not team member)
    $attacker = User::factory()->create();
    $attacker->assignRole('dosen');

    $this->actingAs($attacker);

    Livewire::test(LetterProposalLinkedRequest::class, ['proposal' => $proposal])
        ->assertForbidden();
});

it('submitter can open the letter request form for their own proposal', function () {
    $owner = User::factory()->create();
    $owner->assignRole('dosen');

    $proposal = Proposal::factory()->create();
    $proposal->update(['submitter_id' => $owner->id]);

    $this->actingAs($owner);

    Livewire::test(LetterProposalLinkedRequest::class, ['proposal' => $proposal])
        ->assertOk();
});

it('non-owner cannot submit letter linked to someone elses proposal at service level', function () {
    $letterType = LetterType::factory()->create(['is_active' => true]);

    $proposal = Proposal::factory()->create();
    $proposal->update(['submitter_id' => User::factory()->create()->id]);

    $attacker = User::factory()->create();
    $attacker->assignRole('dosen');

    $this->actingAs($attacker);

    // Bypass the component (whose mount() now aborts) and hit the service
    // directly with a forged reference to the victim's proposal.
    $service = app(LetterService::class);

    expect(fn () => $service->requestManualLetter($attacker, [
        'letterTypeId' => $letterType->id,
        'reference_type' => get_class($proposal),
        'reference_id' => $proposal->id,
        'activityType' => 'Penelitian',
        'title' => 'Surat Pengantar Penelitian',
        'dateString' => '2026-08-05',
        'timeString' => '09:00',
        'location' => 'Lab A',
    ]))->toThrow(DomainException::class);

    $this->assertDatabaseMissing('letters', [
        'user_id' => $attacker->id,
        'reference_type' => get_class($proposal),
        'reference_id' => $proposal->id,
    ]);
});
