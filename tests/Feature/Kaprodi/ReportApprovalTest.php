<?php

// Vetted by AI - Manual Review Required by Senior Engineer/Manager

use App\Enums\ProposalStatus;
use App\Enums\ReportStatus;
use App\Livewire\Kaprodi\ReportApproval;
use App\Models\Faculty;
use App\Models\Identity;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\Research;
use App\Models\StudyProgram;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->faculty = Faculty::factory()->create(['name' => 'Fakultas Teknik']);
    $this->prodiA = StudyProgram::factory()->create([
        'name' => 'Teknik Informatika',
        'faculty_id' => $this->faculty->id,
    ]);
    $this->prodiB = StudyProgram::factory()->create([
        'name' => 'Sistem Informasi',
        'faculty_id' => $this->faculty->id,
    ]);

    // Kaprodi Prodi A
    $this->kaprodi = User::factory()->create();
    $this->kaprodi->assignRole('kaprodi');
    $this->prodiA->update(['kaprodi_user_id' => $this->kaprodi->id]);
    Identity::factory()->create([
        'user_id' => $this->kaprodi->id,
        'type' => 'dosen',
        'faculty_id' => $this->faculty->id,
        'study_program_id' => $this->prodiA->id,
    ]);

    // Dosen Prodi A
    $this->dosenA = User::factory()->create();
    $this->dosenA->assignRole('dosen');
    Identity::factory()->create([
        'user_id' => $this->dosenA->id,
        'type' => 'dosen',
        'faculty_id' => $this->faculty->id,
        'study_program_id' => $this->prodiA->id,
    ]);

    // Dosen Prodi B
    $this->dosenB = User::factory()->create();
    $this->dosenB->assignRole('dosen');
    Identity::factory()->create([
        'user_id' => $this->dosenB->id,
        'type' => 'dosen',
        'faculty_id' => $this->faculty->id,
        'study_program_id' => $this->prodiB->id,
    ]);

    // Proposal Dosen A
    $researchA = Research::factory()->create();
    $this->proposalA = Proposal::factory()->create([
        'submitter_id' => $this->dosenA->id,
        'detailable_type' => 'App\Models\Research',
        'detailable_id' => $researchA->id,
        'status' => ProposalStatus::COMPLETED,
        'title' => 'Penelitian Prodi A TI',
    ]);

    $this->reportA = ProgressReport::create([
        'proposal_id' => $this->proposalA->id,
        'reporting_period' => 'final',
        'reporting_year' => 2026,
        'status' => ReportStatus::SUBMITTED,
        'summary_update' => 'Ringkasan laporan akhir Prodi A.',
    ]);

    // Proposal Dosen B
    $researchB = Research::factory()->create();
    $this->proposalB = Proposal::factory()->create([
        'submitter_id' => $this->dosenB->id,
        'detailable_type' => 'App\Models\Research',
        'detailable_id' => $researchB->id,
        'status' => ProposalStatus::COMPLETED,
        'title' => 'Penelitian Prodi B SI',
    ]);

    $this->reportB = ProgressReport::create([
        'proposal_id' => $this->proposalB->id,
        'reporting_period' => 'final',
        'reporting_year' => 2026,
        'status' => ReportStatus::SUBMITTED,
        'summary_update' => 'Ringkasan laporan akhir Prodi B.',
    ]);
});

test('kaprodi can view report approval scoped to their study program', function () {
    $this->actingAs($this->kaprodi);
    session(['active_role' => 'kaprodi']);

    Livewire::test(ReportApproval::class)
        ->assertOk()
        ->assertSee('Penelitian Prodi A TI')
        ->assertDontSee('Penelitian Prodi B SI');
});

test('kaprodi can filter reports by status', function () {
    $this->actingAs($this->kaprodi);
    session(['active_role' => 'kaprodi']);

    // Initially status is submitted -> waiting_dekan
    Livewire::test(ReportApproval::class)
        ->set('statusFilter', 'waiting_dekan')
        ->assertSee('Penelitian Prodi A TI');

    Livewire::test(ReportApproval::class)
        ->set('statusFilter', 'ready')
        ->assertDontSee('Penelitian Prodi A TI');

    // Update status to approved_by_dekan
    $this->reportA->update(['status' => ReportStatus::APPROVED_BY_DEKAN]);

    Livewire::test(ReportApproval::class)
        ->set('statusFilter', 'ready')
        ->assertSee('Penelitian Prodi A TI');
});

test('kaprodi can filter belum_laporan proposals from their prodi', function () {
    $this->actingAs($this->kaprodi);
    session(['active_role' => 'kaprodi']);

    // Proposal baru di Prodi A tanpa laporan akhir
    $researchBelum = Research::factory()->create();
    $proposalBelum = Proposal::factory()->create([
        'submitter_id' => $this->dosenA->id,
        'detailable_type' => 'App\Models\Research',
        'detailable_id' => $researchBelum->id,
        'status' => ProposalStatus::APPROVED,
        'title' => 'Proposal Belum Laporan Prodi A',
    ]);

    // Proposal baru di Prodi B tanpa laporan akhir
    $researchBelumB = Research::factory()->create();
    Proposal::factory()->create([
        'submitter_id' => $this->dosenB->id,
        'detailable_type' => 'App\Models\Research',
        'detailable_id' => $researchBelumB->id,
        'status' => ProposalStatus::APPROVED,
        'title' => 'Proposal Belum Laporan Prodi B',
    ]);

    Livewire::test(ReportApproval::class)
        ->set('statusFilter', 'belum_laporan')
        ->assertSee('Proposal Belum Laporan Prodi A')
        ->assertDontSee('Proposal Belum Laporan Prodi B');
});

test('kaprodi report approval route is accessible via http', function () {
    $this->actingAs($this->kaprodi);
    session(['active_role' => 'kaprodi']);

    $response = $this->get(route('kaprodi.report-approval'));
    $response->assertOk();
});
