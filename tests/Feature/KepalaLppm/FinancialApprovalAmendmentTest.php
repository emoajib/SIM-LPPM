<?php

// Regression: BC-08 — workflow dead end pada keputusan amandemen RAB.
//
// Sebelumnya catatan penolakan hanya satu textarea di atas tabel; menekan
// "Tolak Amandemen" tanpa catatan hanya memunculkan toast, sehingga dari sudut
// pandang Kepala LPPM tombolnya "tidak berfungsi" dan LPJ tetap terblokir.
// Tombol "Setujui" juga bisa diklik walau alokasi baru < realisasi, lalu gagal
// setelah diklik.

use App\Enums\BudgetAmendmentStatus;
use App\Enums\ProposalStatus;
use App\Livewire\KepalaLppm\FinancialApproval;
use App\Models\BudgetAmendment;
use App\Models\BudgetAmendmentItem;
use App\Models\BudgetGroup;
use App\Models\BudgetItem;
use App\Models\DailyNote;
use App\Models\Proposal;
use App\Models\Research;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Notification::fake();
});

/**
 * Proposal COMPLETED dengan realisasi Rp 500.000 tetapi alokasi amandemen
 * baru hanya Rp 288.000 pada kelompok yang sama.
 *
 * @return array{0: Proposal, 1: BudgetAmendment, 2: BudgetGroup}
 */
function makeBlockedAmendmentFixture(): array
{
    $kepala = User::factory()->create();
    $kepala->assignRole('kepala lppm');

    $dosen = User::factory()->create();
    $dosen->assignRole('dosen');

    test()->actingAs($kepala);
    session(['active_role' => 'kepala lppm']);

    $research = Research::factory()->create();
    $proposal = Proposal::factory()->create([
        'submitter_id' => $dosen->id,
        'detailable_type' => Research::class,
        'detailable_id' => $research->id,
        'status' => ProposalStatus::COMPLETED,
        'sbk_value' => 0,
        'logbook_approved_at' => null,
    ]);

    $group = BudgetGroup::factory()->create(['name' => 'Biaya Perjalanan']);

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
        'amount' => 500000,
    ]);

    $amendment = BudgetAmendment::factory()->create([
        'proposal_id' => $proposal->id,
        'version' => 2,
        'status' => BudgetAmendmentStatus::PENDING,
        'requested_by' => $dosen->id,
    ]);

    BudgetAmendmentItem::factory()->create([
        'budget_amendment_id' => $amendment->id,
        'budget_group_id' => $group->id,
        'volume' => 1,
        'unit_price' => 288000,
        'total_price' => 288000,
    ]);

    return [$proposal, $amendment, $group];
}

test('violations are exposed with realisasi and alokasi baru', function () {
    [$proposal, $amendment] = makeBlockedAmendmentFixture();

    $component = Livewire::test(FinancialApproval::class);
    $violations = $component->instance()->amendmentViolations;

    expect($violations)->toHaveKey($proposal->id);

    $violation = $violations[$proposal->id][0];

    expect($violation['group'])->toBe('Biaya Perjalanan')
        ->and($violation['used'])->toEqual(500000.0)
        ->and($violation['allocation'])->toEqual(288000.0);
});

test('reject modal prefills notes with the violation list', function () {
    [, $amendment] = makeBlockedAmendmentFixture();

    Livewire::test(FinancialApproval::class)
        ->call('openRejectModal', $amendment->id)
        ->assertSet('rejectingAmendmentId', $amendment->id)
        ->assertSee('Biaya Perjalanan')
        ->assertSee('288.000')
        ->assertSee('500.000');
});

test('reject without notes keeps the amendment pending and shows inline error', function () {
    [, $amendment] = makeBlockedAmendmentFixture();

    $component = Livewire::test(FinancialApproval::class)
        ->call('openRejectModal', $amendment->id)
        ->set('rejectionNotes', '')
        ->call('rejectAmendment')
        ->assertHasErrors('rejectionNotes');

    expect($amendment->fresh()->status)->toBe(BudgetAmendmentStatus::PENDING);

    $component
        ->set('rejectionNotes', 'Alokasi perjalanan lebih kecil dari realisasi. Mohon sesuaikan.')
        ->call('rejectAmendment')
        ->assertHasNoErrors();

    expect($amendment->fresh()->status)->toBe(BudgetAmendmentStatus::REJECTED);
});

test('approve is rejected while realization exceeds the new allocation', function () {
    [$proposal, $amendment] = makeBlockedAmendmentFixture();

    Livewire::test(FinancialApproval::class)
        ->call('approveAmendment', $amendment->id);

    expect($amendment->fresh()->status)->toBe(BudgetAmendmentStatus::PENDING)
        ->and($proposal->fresh()->approved_budget_total)->toEqual(1000000.0);
});

test('lpj is blocked while an amendment is pending and legalizable after rejection', function () {
    [$proposal, $amendment] = makeBlockedAmendmentFixture();

    $component = Livewire::test(FinancialApproval::class);

    $component->call('approveLpj', $proposal->id);
    expect($proposal->fresh()->logbook_approved_at)->toBeNull();

    $component
        ->call('openRejectModal', $amendment->id)
        ->call('rejectAmendment')
        ->assertHasNoErrors();

    $component->call('approveLpj', $proposal->id);

    expect($proposal->fresh()->logbook_approved_at)->not->toBeNull();
});
