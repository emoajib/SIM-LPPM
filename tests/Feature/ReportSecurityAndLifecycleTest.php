<?php

namespace Tests\Feature;

use App\Enums\ProposalStatus;
use App\Enums\ReportStatus;
use App\Livewire\Research\FinalReport\Show as ResearchFinalReportShow;
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
use Tests\TestCase;

// Vetted by AI - Manual Review Required by Senior Engineer/Manager
class ReportSecurityAndLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected User $dosen1;

    protected User $dekanFaculty1;

    protected User $dekanFaculty2;

    protected User $adminLppm;

    protected Faculty $faculty1;

    protected Faculty $faculty2;

    protected StudyProgram $prodi1;

    protected Proposal $proposal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->faculty1 = Faculty::factory()->create(['name' => 'Fakultas Saintek']);
        $this->faculty2 = Faculty::factory()->create(['name' => 'Fakultas Dekabita']);

        $this->prodi1 = StudyProgram::factory()->create([
            'faculty_id' => $this->faculty1->id,
            'name' => 'Teknik Informatika',
        ]);

        // Dosen 1 (Submitter)
        $this->dosen1 = User::factory()->create(['name' => 'Dosen Peneliti 1']);
        $this->dosen1->assignRole('dosen');
        Identity::factory()->create([
            'user_id' => $this->dosen1->id,
            'faculty_id' => $this->faculty1->id,
            'study_program_id' => $this->prodi1->id,
        ]);

        // Dekan 1 (Same faculty)
        $this->dekanFaculty1 = User::factory()->create(['name' => 'Dekan Saintek']);
        $this->dekanFaculty1->assignRole('dekan');
        Identity::factory()->create([
            'user_id' => $this->dekanFaculty1->id,
            'faculty_id' => $this->faculty1->id,
        ]);

        // Dekan 2 (Different faculty)
        $this->dekanFaculty2 = User::factory()->create(['name' => 'Dekan Dekabita']);
        $this->dekanFaculty2->assignRole('dekan');
        Identity::factory()->create([
            'user_id' => $this->dekanFaculty2->id,
            'faculty_id' => $this->faculty2->id,
        ]);

        // Admin LPPM
        $this->adminLppm = User::factory()->create(['name' => 'Admin LPPM']);
        $this->adminLppm->assignRole('admin lppm');

        // Proposal COMPLETED
        $research = Research::factory()->create();
        $this->proposal = Proposal::factory()->create([
            'submitter_id' => $this->dosen1->id,
            'detailable_type' => Research::class,
            'detailable_id' => $research->id,
            'status' => ProposalStatus::COMPLETED,
            'title' => 'Penelitian Implementasi AI dan Keamanan Sistem',
        ]);
    }

    public function test_author_can_edit_when_report_is_draft_or_new(): void
    {
        session(['active_role' => 'dosen']);

        // Case 1: No report yet
        Livewire::actingAs($this->dosen1)
            ->test(ResearchFinalReportShow::class, ['proposal' => $this->proposal])
            ->assertSet('canEdit', true)
            ->assertSee('Simpan Draft');

        // Case 2: Draft status
        ProgressReport::factory()->create([
            'proposal_id' => $this->proposal->id,
            'reporting_period' => 'final',
            'reporting_year' => (int) date('Y'),
            'status' => ReportStatus::DRAFT,
        ]);

        Livewire::actingAs($this->dosen1)
            ->test(ResearchFinalReportShow::class, ['proposal' => $this->proposal])
            ->assertSet('canEdit', true)
            ->assertSet('isFinalReportDraft', true)
            ->assertSee('Simpan Draft');
    }

    public function test_author_cannot_edit_when_report_is_submitted(): void
    {
        session(['active_role' => 'dosen']);

        ProgressReport::factory()->create([
            'proposal_id' => $this->proposal->id,
            'reporting_period' => 'final',
            'reporting_year' => (int) date('Y'),
            'status' => ReportStatus::SUBMITTED,
        ]);

        Livewire::actingAs($this->dosen1)
            ->test(ResearchFinalReportShow::class, ['proposal' => $this->proposal])
            ->assertSet('canEdit', false)
            ->assertSet('isFinalReportDraft', false)
            ->assertDontSee('Simpan Draft')
            ->assertDontSee('Ajukan Laporan Akhir')
            ->assertSee('Laporan Akhir Telah Diajukan');
    }

    public function test_author_cannot_edit_when_report_is_approved(): void
    {
        session(['active_role' => 'dosen']);

        ProgressReport::factory()->create([
            'proposal_id' => $this->proposal->id,
            'reporting_period' => 'final',
            'reporting_year' => (int) date('Y'),
            'status' => ReportStatus::APPROVED,
        ]);

        Livewire::actingAs($this->dosen1)
            ->test(ResearchFinalReportShow::class, ['proposal' => $this->proposal])
            ->assertSet('canEdit', false)
            ->assertSet('isFinalReportDraft', false)
            ->assertDontSee('Simpan Draft')
            ->assertDontSee('Ajukan Laporan Akhir')
            ->assertSee('Laporan Akhir Telah Selesai Disahkan');
    }

    public function test_author_can_edit_when_report_is_rejected(): void
    {
        session(['active_role' => 'dosen']);

        ProgressReport::factory()->create([
            'proposal_id' => $this->proposal->id,
            'reporting_period' => 'final',
            'reporting_year' => (int) date('Y'),
            'status' => ReportStatus::REJECTED,
            'rejection_notes' => 'Tolong perbaiki daftar pustaka dan luaran wajib.',
        ]);

        Livewire::actingAs($this->dosen1)
            ->test(ResearchFinalReportShow::class, ['proposal' => $this->proposal])
            ->assertSet('canEdit', true)
            ->assertSee('Simpan Draft')
            ->assertSee('Laporan Akhir Ditolak');
    }

    public function test_admin_lppm_cannot_edit_lecturer_report(): void
    {
        session(['active_role' => 'admin lppm']);

        // Even when report is in draft or new, Admin LPPM should not have canEdit = true
        Livewire::actingAs($this->adminLppm)
            ->test(ResearchFinalReportShow::class, ['proposal' => $this->proposal])
            ->assertSet('canEdit', false)
            ->assertDontSee('Simpan Draft')
            ->assertDontSee('Ajukan Laporan Akhir');
    }

    public function test_dekan_from_other_faculty_cannot_export_proposal_or_report_pdf(): void
    {
        // Dekan from other faculty should get 403 Forbidden (fixing IDOR)
        $response = $this->actingAs($this->dekanFaculty2)
            ->withSession(['active_role' => 'dekan'])
            ->get(route('proposals.export-pdf', $this->proposal));

        $response->assertStatus(403);

        $responseReport = $this->actingAs($this->dekanFaculty2)
            ->withSession(['active_role' => 'dekan'])
            ->get(route('reports.export-pdf', $this->proposal));

        $responseReport->assertStatus(403);
    }

    public function test_dekan_from_same_faculty_can_access_proposal_export(): void
    {
        // Dekan from same faculty is authorized
        $response = $this->actingAs($this->dekanFaculty1)
            ->withSession(['active_role' => 'dekan'])
            ->get(route('proposals.export-pdf', ['proposal' => $this->proposal, 'preview' => 1]));

        $response->assertStatus(200);
    }
}
