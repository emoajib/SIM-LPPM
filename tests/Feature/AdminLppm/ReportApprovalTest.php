<?php

// Vetted by AI - Manual Review Required by Senior Engineer/Manager

use App\Enums\ProposalStatus;
use App\Enums\ReportStatus;
use App\Livewire\AdminLppm\ReportApproval;
use App\Models\Faculty;
use App\Models\Identity;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\Research;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->facultyA = Faculty::factory()->create(['name' => 'Fakultas Teknik']);
    $this->facultyB = Faculty::factory()->create(['name' => 'Fakultas Ekonomi']);

    $this->adminLppm = User::factory()->create();
    $this->adminLppm->assignRole('admin lppm');

    // Dosen Fak A
    $this->dosenA = User::factory()->create();
    $this->dosenA->assignRole('dosen');
    Identity::factory()->create([
        'user_id' => $this->dosenA->id,
        'type' => 'dosen',
        'faculty_id' => $this->facultyA->id,
    ]);

    // Dosen Fak B
    $this->dosenB = User::factory()->create();
    $this->dosenB->assignRole('dosen');
    Identity::factory()->create([
        'user_id' => $this->dosenB->id,
        'type' => 'dosen',
        'faculty_id' => $this->facultyB->id,
    ]);

    // Proposal Dosen Fak A
    $researchA = Research::factory()->create();
    $this->proposalA = Proposal::factory()->create([
        'submitter_id' => $this->dosenA->id,
        'detailable_type' => 'App\Models\Research',
        'detailable_id' => $researchA->id,
        'status' => ProposalStatus::COMPLETED,
        'title' => 'Penelitian Fakultas Teknik',
    ]);

    $this->reportA = ProgressReport::create([
        'proposal_id' => $this->proposalA->id,
        'reporting_period' => 'final',
        'reporting_year' => 2026,
        'status' => ReportStatus::SUBMITTED,
        'summary_update' => 'Ringkasan laporan Teknik.',
    ]);

    // Proposal Dosen Fak B
    $researchB = Research::factory()->create();
    $this->proposalB = Proposal::factory()->create([
        'submitter_id' => $this->dosenB->id,
        'detailable_type' => 'App\Models\Research',
        'detailable_id' => $researchB->id,
        'status' => ProposalStatus::COMPLETED,
        'title' => 'Penelitian Fakultas Ekonomi',
    ]);

    $this->reportB = ProgressReport::create([
        'proposal_id' => $this->proposalB->id,
        'reporting_period' => 'final',
        'reporting_year' => 2026,
        'status' => ReportStatus::SUBMITTED,
        'summary_update' => 'Ringkasan laporan Ekonomi.',
    ]);
});

test('admin lppm can view all reports across faculties', function () {
    $this->actingAs($this->adminLppm);
    session(['active_role' => 'admin lppm']);

    Livewire::test(ReportApproval::class)
        ->assertOk()
        ->assertSee('Penelitian Fakultas Teknik')
        ->assertSee('Penelitian Fakultas Ekonomi');
});

test('admin lppm can filter reports by faculty', function () {
    $this->actingAs($this->adminLppm);
    session(['active_role' => 'admin lppm']);

    Livewire::test(ReportApproval::class)
        ->set('facultyFilter', (string) $this->facultyA->id)
        ->assertSee('Penelitian Fakultas Teknik')
        ->assertDontSee('Penelitian Fakultas Ekonomi');

    Livewire::test(ReportApproval::class)
        ->set('facultyFilter', (string) $this->facultyB->id)
        ->assertDontSee('Penelitian Fakultas Teknik')
        ->assertSee('Penelitian Fakultas Ekonomi');
});

test('admin lppm can filter belum_laporan proposals across all faculties or by faculty', function () {
    $this->actingAs($this->adminLppm);
    session(['active_role' => 'admin lppm']);

    $researchBelumA = Research::factory()->create();
    Proposal::factory()->create([
        'submitter_id' => $this->dosenA->id,
        'detailable_type' => 'App\Models\Research',
        'detailable_id' => $researchBelumA->id,
        'status' => ProposalStatus::APPROVED,
        'title' => 'Proposal Belum Laporan Teknik',
    ]);

    $researchBelumB = Research::factory()->create();
    Proposal::factory()->create([
        'submitter_id' => $this->dosenB->id,
        'detailable_type' => 'App\Models\Research',
        'detailable_id' => $researchBelumB->id,
        'status' => ProposalStatus::APPROVED,
        'title' => 'Proposal Belum Laporan Ekonomi',
    ]);

    // All faculties
    Livewire::test(ReportApproval::class)
        ->set('statusFilter', 'belum_laporan')
        ->assertSee('Proposal Belum Laporan Teknik')
        ->assertSee('Proposal Belum Laporan Ekonomi');

    // Filter by Faculty A
    Livewire::test(ReportApproval::class)
        ->set('statusFilter', 'belum_laporan')
        ->set('facultyFilter', (string) $this->facultyA->id)
        ->assertSee('Proposal Belum Laporan Teknik')
        ->assertDontSee('Proposal Belum Laporan Ekonomi');
});

test('admin lppm report approval route is accessible via http', function () {
    $this->actingAs($this->adminLppm);
    session(['active_role' => 'admin lppm']);

    $response = $this->get(route('admin-lppm.report-approval'));
    $response->assertOk();
});
