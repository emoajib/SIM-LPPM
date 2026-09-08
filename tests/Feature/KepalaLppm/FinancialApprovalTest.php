<?php

use App\Enums\ProposalStatus;
use App\Livewire\KepalaLppm\FinancialApproval;
use App\Livewire\Research\FinalReport\Show as ResearchFinalReportShow;
use App\Models\CommunityService;
use App\Models\Identity;
use App\Models\Proposal;
use App\Models\Research;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

// Vetted by AI - Manual Review Required by Senior Engineer/Manager

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->kepala = User::factory()->create();
    $this->kepala->assignRole('kepala lppm');

    $this->lecturer = User::factory()->create();
    $this->lecturer->assignRole('dosen');
    Identity::factory()->create(['user_id' => $this->lecturer->id, 'type' => 'dosen']);

    $research = Research::factory()->create();
    $this->proposal = Proposal::factory()->create([
        'submitter_id' => $this->lecturer->id,
        'detailable_type' => 'App\Models\Research',
        'detailable_id' => $research->id,
        'status' => ProposalStatus::COMPLETED,
        'title' => 'Penelitian Pengujian Persetujuan LPJ 2026',
    ]);
});

test('guest cannot access financial approval component', function () {
    Livewire::test(FinancialApproval::class)
        ->assertStatus(403);
});

test('regular lecturer cannot access financial approval component', function () {
    $this->actingAs($this->lecturer);
    session(['active_role' => 'dosen']);

    Livewire::test(FinancialApproval::class)
        ->assertStatus(403);
});

test('kepala lppm can mount financial approval component and view proposals', function () {
    $this->actingAs($this->kepala);
    session(['active_role' => 'kepala lppm']);

    Livewire::test(FinancialApproval::class)
        ->assertOk()
        ->assertSee('Total Usulan Didanai', false)
        ->assertSee('Penelitian Pengujian Persetujuan LPJ 2026', false);
});

test('kepala lppm can approve lpj and unapprove lpj', function () {
    $this->actingAs($this->kepala);
    session(['active_role' => 'kepala lppm']);

    $this->assertNull($this->proposal->logbook_approved_at);

    $component = Livewire::test(FinancialApproval::class)
        ->call('approveLpj', $this->proposal->id)
        ->assertHasNoErrors();

    $this->proposal->refresh();
    $this->assertNotNull($this->proposal->logbook_approved_at);

    // Test unapprove
    $component->call('unapproveLpj', $this->proposal->id)
        ->assertHasNoErrors();

    $this->proposal->refresh();
    $this->assertNull($this->proposal->logbook_approved_at);
});

test('financial approval filtering works correctly', function () {
    $this->actingAs($this->kepala);
    session(['active_role' => 'kepala lppm']);

    $cs = CommunityService::factory()->create();
    $csProposal = Proposal::factory()->create([
        'submitter_id' => $this->lecturer->id,
        'detailable_type' => 'App\Models\CommunityService',
        'detailable_id' => $cs->id,
        'status' => ProposalStatus::COMPLETED,
        'title' => 'PKM Pengabdian Masyarakat 2026',
    ]);

    // Test search filter
    Livewire::test(FinancialApproval::class)
        ->set('search', 'Pengabdian Masyarakat')
        ->assertSee('PKM Pengabdian Masyarakat 2026', false)
        ->assertDontSee('Penelitian Pengujian Persetujuan LPJ 2026', false);

    // Test type filter
    Livewire::test(FinancialApproval::class)
        ->set('typeFilter', 'research')
        ->assertSee('Penelitian Pengujian Persetujuan LPJ 2026', false)
        ->assertDontSee('PKM Pengabdian Masyarakat 2026', false);
});

test('lecturer can check completeness of final report without logbook requirement', function () {
    $this->actingAs($this->lecturer);
    session(['active_role' => 'dosen']);

    $this->proposal->teamMembers()->attach($this->lecturer->id, [
        'role' => 'ketua',
        'tasks' => 'Ketua Peneliti',
    ]);

    // Proposal has 0 daily notes (completely decoupled from LPJ)
    expect($this->proposal->dailyNotes()->count())->toBe(0);

    $component = Livewire::test(ResearchFinalReportShow::class, ['proposal' => $this->proposal])
        ->call('doCheckCompleteness');

    $missing = $component->get('completenessMissing');
    // Logbook Harian should NOT be in missing requirements
    expect($missing)->not->toContain('Logbook Harian');
});

test('admin lppm can mount financial approval component and approve and unapprove lpj', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin lppm');

    $this->actingAs($admin);
    session(['active_role' => 'admin lppm']);

    Livewire::test(FinancialApproval::class)
        ->assertOk()
        ->assertSee('Tinjau PDF', false)
        ->assertSee(route('financial-reports.export-pdf', $this->proposal), false)
        ->call('approveLpj', $this->proposal->id)
        ->assertHasNoErrors();

    $this->proposal->refresh();
    expect($this->proposal->logbook_approved_at)->not->toBeNull();

    Livewire::test(FinancialApproval::class)
        ->call('unapproveLpj', $this->proposal->id)
        ->assertHasNoErrors();

    $this->proposal->refresh();
    expect($this->proposal->logbook_approved_at)->toBeNull();
});
