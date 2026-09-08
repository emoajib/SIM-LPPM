<?php

use App\Enums\ProposalStatus;
use App\Enums\ReportStatus;
use App\Livewire\KepalaLppm\ReportApproval;
use App\Models\Identity;
use App\Models\ProgressReport;
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
        'title' => 'Penelitian Pengujian Laporan Akhir 2026',
    ]);

    $this->finalReport = ProgressReport::create([
        'proposal_id' => $this->proposal->id,
        'reporting_period' => 'final',
        'reporting_year' => 2026,
        'status' => ReportStatus::SUBMITTED,
        'summary' => 'Ringkasan laporan akhir pengujian.',
    ]);
});

test('kepala lppm can view report approval page with stats', function () {
    $this->actingAs($this->kepala);
    session(['active_role' => 'kepala lppm']);

    Livewire::test(ReportApproval::class)
        ->assertOk()
        ->assertSee('Total Berkas Masuk', false)
        ->assertSee('Penelitian Pengujian Laporan Akhir 2026', false)
        ->assertSee('Menunggu Dekan', false);
});

test('report approval filters by status correctly', function () {
    $this->actingAs($this->kepala);
    session(['active_role' => 'kepala lppm']);

    // When statusFilter is 'waiting_dekan', it should see this report
    Livewire::test(ReportApproval::class)
        ->set('statusFilter', 'waiting_dekan')
        ->assertSee('Penelitian Pengujian Laporan Akhir 2026', false);

    // When statusFilter is 'ready', it should not see this report (since it's not approved by dekan yet)
    Livewire::test(ReportApproval::class)
        ->set('statusFilter', 'ready')
        ->assertDontSee('Penelitian Pengujian Laporan Akhir 2026', false);

    // Update status to approved_by_dekan
    $this->finalReport->update(['status' => ReportStatus::APPROVED_BY_DEKAN]);

    // Now 'ready' should see it
    Livewire::test(ReportApproval::class)
        ->set('statusFilter', 'ready')
        ->assertSee('Penelitian Pengujian Laporan Akhir 2026', false)
        ->assertSee('Siap Disahkan LPPM', false);
});
