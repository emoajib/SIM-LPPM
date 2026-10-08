<?php

// Regresi amandemen RAB: titik kebenaran angka.

use App\Enums\BudgetAmendmentStatus;
use App\Enums\ProposalStatus;
use App\Livewire\Research\DailyNote\Show;
use App\Models\BudgetAmendment;
use App\Models\BudgetAmendmentItem;
use App\Models\BudgetCap;
use App\Models\BudgetGroup;
use App\Models\BudgetItem;
use App\Models\DailyNote;
use App\Models\Proposal;
use App\Models\Research;
use App\Models\User;
use App\Notifications\BudgetAmendmentSubmitted;
use App\Services\BudgetAmendmentService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

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

test('pengajuan amandemen membuat versi pending dan notifikasi', function () {
    $dosen = User::factory()->create();
    $kepala = User::factory()->create();
    $kepala->assignRole('kepala lppm');
    $this->actingAs($dosen);
    Notification::fake();
    BudgetCap::create([
        'year' => (int) date('Y'),
        'semester' => 'ganjil',
        'research_budget_cap' => 1000000000,
        'community_service_budget_cap' => 1000000000,
        'enforce_percentage' => false,
    ]);
    $research = Research::factory()->create();
    $proposal = Proposal::factory()->create([
        'submitter_id' => $dosen->id,
        'detailable_type' => Research::class,
        'detailable_id' => $research->id,
        'status' => ProposalStatus::COMPLETED,
        'start_year' => (int) date('Y'),
        'semester' => 'ganjil',
    ]);
    $group = BudgetGroup::factory()->create();
    BudgetItem::factory()->create([
        'proposal_id' => $proposal->id,
        'budget_group_id' => $group->id,
        'total_price' => 1000000,
        'is_active' => true,
        'version' => 1,
    ]);

    $svc = app(BudgetAmendmentService::class);
    $amendment = $svc->request($proposal, $dosen, [
        [
            'budget_group_id' => $group->id,
            'item_description' => 'Item revisi',
            'volume' => 2,
            'unit_price' => 600000,
        ],
    ], 'Harga bahan naik di lapangan');

    expect($amendment->status)->toBe(BudgetAmendmentStatus::PENDING)
        ->and($amendment->version)->toBe(2)
        ->and($amendment->items)->toHaveCount(1)
        ->and($proposal->fresh()->hasPendingBudgetAmendment())->toBeTrue();

    Notification::assertSentTo(
        User::role('kepala lppm')->get(),
        BudgetAmendmentSubmitted::class
    );
});

test('pengajuan kedua diblokir selama ada pending', function () {
    $dosen = User::factory()->create();
    $this->actingAs($dosen);
    $research = Research::factory()->create();
    $proposal = Proposal::factory()->create([
        'submitter_id' => $dosen->id,
        'detailable_type' => Research::class,
        'detailable_id' => $research->id,
        'status' => ProposalStatus::COMPLETED,
        'sbk_value' => 0,
    ]);
    $group = BudgetGroup::factory()->create();
    BudgetAmendment::factory()->create([
        'proposal_id' => $proposal->id,
        'version' => 2,
        'status' => BudgetAmendmentStatus::PENDING,
    ]);

    $svc = app(BudgetAmendmentService::class);

    expect(fn () => $svc->request($proposal, $dosen, [
        ['budget_group_id' => $group->id, 'item_description' => 'X', 'volume' => 1, 'unit_price' => 1000],
    ], 'Alasan yang cukup panjang'))
        ->toThrow(ValidationException::class);
});

test('persetujuan menerapkan versi baru secara atomik', function () {
    $dosen = User::factory()->create();
    $kepala = User::factory()->create();
    $kepala->assignRole('kepala lppm');
    $this->actingAs($kepala);
    $research = Research::factory()->create();
    $proposal = Proposal::factory()->create([
        'submitter_id' => $dosen->id,
        'detailable_type' => Research::class,
        'detailable_id' => $research->id,
        'status' => ProposalStatus::COMPLETED,
        'sbk_value' => 0,
    ]);
    $group = BudgetGroup::factory()->create();
    BudgetItem::factory()->create([
        'proposal_id' => $proposal->id,
        'budget_group_id' => $group->id,
        'total_price' => 1000000,
        'is_active' => true,
        'version' => 1,
    ]);
    $amendment = BudgetAmendment::factory()->create([
        'proposal_id' => $proposal->id,
        'version' => 2,
        'status' => BudgetAmendmentStatus::PENDING,
    ]);
    BudgetAmendmentItem::factory()->create([
        'budget_amendment_id' => $amendment->id,
        'budget_group_id' => $group->id,
        'volume' => 3,
        'unit_price' => 500000,
        'total_price' => 1500000,
    ]);

    app(BudgetAmendmentService::class)->approve($amendment, $kepala, 'Setuju');

    expect($amendment->fresh()->status)->toBe(BudgetAmendmentStatus::APPROVED)
        ->and($proposal->fresh()->approved_budget_total)->toEqual(1500000.0)
        ->and($proposal->budgetItems()->where('is_active', true)->count())->toBe(1)
        ->and($proposal->budgetItems()->where('is_active', false)->count())->toBe(1);
});

test('persetujuan ditolak bila realisasi melebihi alokasi baru', function () {
    $dosen = User::factory()->create();
    $kepala = User::factory()->create();
    $kepala->assignRole('kepala lppm');
    $this->actingAs($kepala);
    $research = Research::factory()->create();
    $proposal = Proposal::factory()->create([
        'submitter_id' => $dosen->id,
        'detailable_type' => Research::class,
        'detailable_id' => $research->id,
        'status' => ProposalStatus::COMPLETED,
        'sbk_value' => 0,
    ]);
    $group = BudgetGroup::factory()->create();
    BudgetItem::factory()->create([
        'proposal_id' => $proposal->id,
        'budget_group_id' => $group->id,
        'total_price' => 1000000,
        'is_active' => true,
        'version' => 1,
    ]);
    DailyNote::factory()->create([
        'proposal_id' => $proposal->id,
        'budget_group_id' => $group->id,
        'amount' => 900000,
    ]);
    $amendment = BudgetAmendment::factory()->create([
        'proposal_id' => $proposal->id,
        'version' => 2,
        'status' => BudgetAmendmentStatus::PENDING,
    ]);
    BudgetAmendmentItem::factory()->create([
        'budget_amendment_id' => $amendment->id,
        'budget_group_id' => $group->id,
        'total_price' => 500000,
    ]);

    expect(fn () => app(BudgetAmendmentService::class)->approve($amendment, $kepala))
        ->toThrow(ValidationException::class);

    expect($amendment->fresh()->status)->toBe(BudgetAmendmentStatus::PENDING)
        ->and($proposal->fresh()->approved_budget_total)->toEqual(1000000.0);
});

test('snapshot dibekukan saat lpj disahkan dan tak berubah oleh amandemen baru', function () {
    $dosen = User::factory()->create();
    $kepala = User::factory()->create();
    $kepala->assignRole('kepala lppm');
    $research = Research::factory()->create();
    $proposal = Proposal::factory()->create([
        'submitter_id' => $dosen->id,
        'detailable_type' => Research::class,
        'detailable_id' => $research->id,
        'status' => ProposalStatus::COMPLETED,
        'sbk_value' => 0,
    ]);
    $group = BudgetGroup::factory()->create();
    BudgetItem::factory()->create([
        'proposal_id' => $proposal->id,
        'budget_group_id' => $group->id,
        'total_price' => 1000000,
        'is_active' => true,
        'version' => 1,
    ]);

    $svc = app(BudgetAmendmentService::class);
    $snapshot = $svc->snapshotApprovedBudget($proposal->fresh());

    expect($snapshot['version'])->toBe(1)
        ->and($snapshot['total'])->toEqual(1000000.0)
        ->and($proposal->fresh()->approved_budget_snapshot['total'])->toEqual(1000000.0);

    $amendment = BudgetAmendment::factory()->create([
        'proposal_id' => $proposal->id,
        'version' => 2,
        'status' => BudgetAmendmentStatus::PENDING,
    ]);
    BudgetAmendmentItem::factory()->create([
        'budget_amendment_id' => $amendment->id,
        'budget_group_id' => $group->id,
        'volume' => 2,
        'unit_price' => 750000,
        'total_price' => 1500000,
    ]);
    $svc->approve($amendment, $kepala);

    expect($proposal->fresh()->approved_budget_total)->toEqual(1500000.0)
        ->and($proposal->fresh()->approved_budget_snapshot['version'])->toBe(1)
        ->and($proposal->fresh()->approved_budget_snapshot['total'])->toEqual(1000000.0);
});

test('toleransi membolehkan total sedikit di atas pagu', function () {
    $dosen = User::factory()->create();
    BudgetCap::create([
        'year' => (int) date('Y'),
        'semester' => 'ganjil',
        'research_budget_cap' => 1000000,
        'community_service_budget_cap' => 1000000,
        'enforce_percentage' => true,
    ]);

    $makeProposal = function () use ($dosen) {
        $research = Research::factory()->create();

        return Proposal::factory()->create([
            'submitter_id' => $dosen->id,
            'detailable_type' => Research::class,
            'detailable_id' => $research->id,
            'status' => ProposalStatus::COMPLETED,
            'sbk_value' => 0,
            'start_year' => (int) date('Y'),
            'semester' => 'ganjil',
        ]);
    };

    $svc = app(BudgetAmendmentService::class);
    $group = BudgetGroup::factory()->create(['percentage' => null]);

    $proposalOk = $makeProposal();
    BudgetItem::factory()->create([
        'proposal_id' => $proposalOk->id,
        'budget_group_id' => $group->id,
        'total_price' => 900000,
        'is_active' => true,
        'version' => 1,
    ]);

    $amendment = $svc->request($proposalOk, $dosen, [
        ['budget_group_id' => $group->id, 'item_description' => 'Naik sedikit', 'volume' => 1, 'unit_price' => 1050000],
    ], 'Penyesuaian harga di lapangan yang wajar');

    expect($amendment->status)->toBe(BudgetAmendmentStatus::PENDING);

    $proposalOver = $makeProposal();
    BudgetItem::factory()->create([
        'proposal_id' => $proposalOver->id,
        'budget_group_id' => $group->id,
        'total_price' => 900000,
        'is_active' => true,
        'version' => 1,
    ]);

    expect(fn () => $svc->request($proposalOver, $dosen, [
        ['budget_group_id' => $group->id, 'item_description' => 'Naik kebangetan', 'volume' => 1, 'unit_price' => 1200000],
    ], 'Alasan yang cukup panjang untuk lolos validasi'))
        ->toThrow(ValidationException::class);
});
