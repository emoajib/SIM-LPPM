<?php

// Regresi amandemen RAB: titik kebenaran angka.
uses(RefreshDatabase::class);

use App\Enums\ProposalStatus;
use App\Livewire\Research\DailyNote\Show;
use App\Models\BudgetGroup;
use App\Models\BudgetItem;
use App\Models\DailyNote;
use App\Models\Proposal;
use App\Models\Research;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('approved budget total hanya menghitung item aktif', function () {
    $dosen = User::factory()->create();
    $research = Research::factory()->create();
    $proposal = Proposal::factory()->create([
        'submitter_id' => $dosen->id,
        'detailable_type' => Research::class,
        'detailable_id' => $research->id,
        'sbk_value' => 0,
    ]);

    BudgetItem::factory()->create([
        'proposal_id' => $proposal->id,
        'total_price' => 1000000,
        'is_active' => true,
        'version' => 2,
    ]);
    BudgetItem::factory()->create([
        'proposal_id' => $proposal->id,
        'total_price' => 5000000,
        'is_active' => false,
        'version' => 1,
    ]);

    expect($proposal->fresh()->approved_budget_total)->toEqual(1000000.0)
        ->and($proposal->fresh()->hasPendingBudgetAmendment())->toBeFalse();
});

test('salin rab mengisi modal dengan sisa kelompok', function () {
    $dosen = User::factory()->create();
    $this->actingAs($dosen);
    $research = Research::factory()->create();
    $proposal = Proposal::factory()->create([
        'submitter_id' => $dosen->id,
        'detailable_type' => Research::class,
        'detailable_id' => $research->id,
        'status' => ProposalStatus::COMPLETED,
    ]);
    $group = BudgetGroup::factory()->create();
    BudgetItem::factory()->create([
        'proposal_id' => $proposal->id,
        'budget_group_id' => $group->id,
        'total_price' => 1000000,
        'is_active' => true,
    ]);

    Livewire::test(Show::class, ['proposal' => $proposal])
        ->call('prefillFromBudgetGroup', $group->id)
        ->assertSet('budget_group_id', $group->id)
        ->assertSet('amount', 1000000.0)
        ->assertHasNoErrors();
});

test('logbook terkunci setelah lpj disahkan', function () {
    $dosen = User::factory()->create();
    $this->actingAs($dosen);
    $research = Research::factory()->create();
    $proposal = Proposal::factory()->create([
        'submitter_id' => $dosen->id,
        'detailable_type' => Research::class,
        'detailable_id' => $research->id,
        'status' => ProposalStatus::COMPLETED,
        'logbook_approved_at' => now(),
    ]);

    Livewire::test(Show::class, ['proposal' => $proposal])
        ->set('activity_date', date('Y-m-d'))
        ->set('activity_description', 'Kegiatan yang mencoba menyelinap masuk')
        ->set('progress_percentage', 10)
        ->call('save');

    expect(DailyNote::where('proposal_id', $proposal->id)->count())->toBe(0);
});
