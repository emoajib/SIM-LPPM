<?php

namespace Tests\Feature\Dekan;

// Vetted by AI - Manual Review Required by Senior Engineer/Manager

use App\Enums\ProposalStatus;
use App\Enums\ReportStatus;
use App\Livewire\Dekan\ApprovalHistory;
use App\Livewire\Dekan\ProposalIndex;
use App\Livewire\Dekan\ReportIndex;
use App\Models\Faculty;
use App\Models\Identity;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\ProposalStatusLog;
use App\Models\Research;
use App\Models\StudyProgram;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DekanFilterSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $dekanFaculty1;

    protected Faculty $faculty1;

    protected Faculty $faculty2;

    protected StudyProgram $prodi1;

    protected StudyProgram $prodi2;

    protected User $dosen1;

    protected User $dosen2;

    protected Proposal $proposalProdi1;

    protected Proposal $proposalProdi2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->faculty1 = Faculty::factory()->create(['name' => 'Fakultas Teknik']);
        $this->faculty2 = Faculty::factory()->create(['name' => 'Fakultas Ekonomi']);

        $this->prodi1 = StudyProgram::factory()->create([
            'faculty_id' => $this->faculty1->id,
            'name' => 'Teknik Informatika',
        ]);
        $this->prodi2 = StudyProgram::factory()->create([
            'faculty_id' => $this->faculty2->id,
            'name' => 'Manajemen Bisnis',
        ]);

        // Dekan of Faculty 1
        $this->dekanFaculty1 = User::factory()->create(['name' => 'Dekan Fakultas 1']);
        $this->dekanFaculty1->assignRole('dekan');
        Identity::factory()->create([
            'user_id' => $this->dekanFaculty1->id,
            'faculty_id' => $this->faculty1->id,
        ]);

        // Dosen 1 (Prodi 1, Faculty 1)
        $this->dosen1 = User::factory()->create(['name' => 'Dosen Teknik']);
        $this->dosen1->assignRole('dosen');
        Identity::factory()->create([
            'user_id' => $this->dosen1->id,
            'faculty_id' => $this->faculty1->id,
            'study_program_id' => $this->prodi1->id,
        ]);

        // Dosen 2 (Prodi 2, Faculty 2)
        $this->dosen2 = User::factory()->create(['name' => 'Dosen Ekonomi']);
        $this->dosen2->assignRole('dosen');
        Identity::factory()->create([
            'user_id' => $this->dosen2->id,
            'faculty_id' => $this->faculty2->id,
            'study_program_id' => $this->prodi2->id,
        ]);

        // Proposal 1: Fakultas 1
        $research1 = Research::factory()->create();
        $this->proposalProdi1 = Proposal::factory()->create([
            'title' => 'Rancang Bangun Sistem AI Terpadu',
            'submitter_id' => $this->dosen1->id,
            'detailable_type' => Research::class,
            'detailable_id' => $research1->id,
            'status' => ProposalStatus::SUBMITTED,
            'start_year' => (int) date('Y'),
        ]);

        // Proposal 2: Fakultas 2
        $research2 = Research::factory()->create();
        $this->proposalProdi2 = Proposal::factory()->create([
            'title' => 'Analisis Rasio Keuangan UMKM',
            'submitter_id' => $this->dosen2->id,
            'detailable_type' => Research::class,
            'detailable_id' => $research2->id,
            'status' => ProposalStatus::SUBMITTED,
            'start_year' => (int) date('Y'),
        ]);
    }

    public function test_dekan_can_filter_proposals_by_study_program_in_own_faculty(): void
    {
        $this->actingAs($this->dekanFaculty1)
            ->withSession(['active_role' => 'dekan']);

        Livewire::test(ProposalIndex::class)
            ->set('studyProgramFilter', (string) $this->prodi1->id)
            ->assertSee($this->proposalProdi1->title)
            ->assertDontSee($this->proposalProdi2->title);
    }

    public function test_dekan_cannot_leak_data_by_filtering_study_program_from_other_faculty(): void
    {
        $this->actingAs($this->dekanFaculty1)
            ->withSession(['active_role' => 'dekan']);

        // IDOR Attack Simulation: Dekan menyuntikkan ID prodi fakultas 2
        Livewire::test(ProposalIndex::class)
            ->set('studyProgramFilter', (string) $this->prodi2->id)
            ->assertDontSee($this->proposalProdi2->title)
            ->assertDontSee($this->proposalProdi1->title);
    }

    public function test_dekan_report_index_filters_and_idor_prevention(): void
    {
        $this->actingAs($this->dekanFaculty1)
            ->withSession(['active_role' => 'dekan']);

        // Final Report Fakultas 1
        ProgressReport::create([
            'proposal_id' => $this->proposalProdi1->id,
            'reporting_year' => (int) date('Y'),
            'reporting_period' => 'final',
            'status' => ReportStatus::SUBMITTED->value,
            'submitted_by' => $this->dosen1->id,
            'submitted_at' => now(),
        ]);

        // Final Report Fakultas 2
        ProgressReport::create([
            'proposal_id' => $this->proposalProdi2->id,
            'reporting_year' => (int) date('Y'),
            'reporting_period' => 'final',
            'status' => ReportStatus::SUBMITTED->value,
            'submitted_by' => $this->dosen2->id,
            'submitted_at' => now(),
        ]);

        // Filter valid prodi1
        Livewire::test(ReportIndex::class)
            ->set('studyProgramFilter', (string) $this->prodi1->id)
            ->assertSee($this->proposalProdi1->title)
            ->assertDontSee($this->proposalProdi2->title);

        // IDOR attempt prodi2 -> harus kosong
        Livewire::test(ReportIndex::class)
            ->set('studyProgramFilter', (string) $this->prodi2->id)
            ->assertDontSee($this->proposalProdi2->title)
            ->assertDontSee($this->proposalProdi1->title);
    }

    public function test_dekan_approval_history_with_search_and_filters(): void
    {
        $this->actingAs($this->dekanFaculty1)
            ->withSession(['active_role' => 'dekan']);

        ProposalStatusLog::create([
            'proposal_id' => $this->proposalProdi1->id,
            'user_id' => $this->dekanFaculty1->id,
            'status_before' => 'submitted',
            'status_after' => 'approved',
            'notes' => 'Catatan uji dekan',
            'at' => now(),
        ]);

        Livewire::test(ApprovalHistory::class)
            ->set('search', 'AI Terpadu')
            ->assertSee('Rancang Bangun Sistem AI Terpadu')
            ->set('decisionFilter', 'approved')
            ->assertSee('Catatan uji dekan')
            ->assertSee('Disetujui</span>', false)
            ->set('search', 'Non Existent Title')
            ->assertDontSee('Rancang Bangun Sistem AI Terpadu');
    }

    public function test_filter_change_resets_paginator(): void
    {
        $this->actingAs($this->dekanFaculty1)
            ->withSession(['active_role' => 'dekan']);

        Livewire::test(ProposalIndex::class)
            ->call('setPage', 2)
            ->set('studyProgramFilter', (string) $this->prodi1->id)
            ->assertSet('paginators.page', 1);
    }
}
