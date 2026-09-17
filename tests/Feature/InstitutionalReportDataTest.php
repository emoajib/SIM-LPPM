<?php

namespace Tests\Feature;

use App\Enums\ProposalStatus;
use App\Enums\ReportStatus;
use App\Livewire\Reports\Research as InstitutionalResearchReport;
use App\Livewire\Research\FinalReport\Index as ResearchFinalReportIndex;
use App\Models\Faculty;
use App\Models\Identity;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\Research;
use App\Models\ResearchScheme;
use App\Models\StudyProgram;
use App\Models\User;
use Database\Seeders\InstitutionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

// Vetted by AI - Manual Review Required by Senior Engineer/Manager
class InstitutionalReportDataTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $dosen;

    protected Faculty $faculty;

    protected StudyProgram $studyProgram;

    protected ResearchScheme $scheme;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->seed(RoleSeeder::class);
        $this->seed(InstitutionSeeder::class);

        $this->faculty = Faculty::factory()->create(['name' => 'Fakultas Teknik']);
        $this->studyProgram = StudyProgram::factory()->create([
            'name' => 'Teknik Informatika',
            'faculty_id' => $this->faculty->id,
        ]);
        $this->scheme = ResearchScheme::factory()->create(['name' => 'Penelitian Dasar']);

        // Create Admin LPPM
        $this->admin = User::factory()->create(['name' => 'Admin LPPM']);
        $this->admin->assignRole('admin lppm');
        Permission::firstOrCreate(['name' => 'module_laporan']);
        $this->admin->givePermissionTo('module_laporan');

        // Create Dosen
        $this->dosen = User::factory()->create(['name' => 'Dr. Peneliti']);
        $this->dosen->assignRole('dosen');
        Identity::factory()->create([
            'user_id' => $this->dosen->id,
            'faculty_id' => $this->faculty->id,
            'study_program_id' => $this->studyProgram->id,
            'identity_id' => '0612345678',
            'type' => 'dosen',
        ]);
    }

    public function test_institutional_report_excludes_proposal_drafts_and_unapproved_proposals(): void
    {
        $currentYear = (string) date('Y');

        // 1. Usulan proposal yang masih DRAFT (tidak boleh muncul di laporan)
        $draftProposal = Proposal::factory()->create([
            'detailable_type' => Research::class,
            'detailable_id' => Research::factory()->create()->id,
            'title' => 'Usulan Masih Draf Tidak Boleh Muncul',
            'status' => ProposalStatus::DRAFT,
            'start_year' => $currentYear,
            'submitter_id' => $this->dosen->id,
            'research_scheme_id' => $this->scheme->id,
        ]);

        // 2. Usulan proposal yang baru diajukan (tahap seleksi, tidak boleh muncul)
        $submittedProposal = Proposal::factory()->create([
            'detailable_type' => Research::class,
            'detailable_id' => Research::factory()->create()->id,
            'title' => 'Usulan Baru Diajukan Tidak Boleh Muncul',
            'status' => ProposalStatus::SUBMITTED,
            'start_year' => $currentYear,
            'submitter_id' => $this->dosen->id,
            'research_scheme_id' => $this->scheme->id,
        ]);

        // 3. Penelitian yang lolos didanai (COMPLETED) tapi belum ada laporan akhir
        $completedProposal = Proposal::factory()->create([
            'detailable_type' => Research::class,
            'detailable_id' => Research::factory()->create()->id,
            'title' => 'Penelitian Didanai Belum Lapor',
            'status' => ProposalStatus::COMPLETED,
            'start_year' => $currentYear,
            'submitter_id' => $this->dosen->id,
            'research_scheme_id' => $this->scheme->id,
        ]);

        // 4. Penelitian yang sudah ada laporan akhir disetujui LPPM
        $reportedProposal = Proposal::factory()->create([
            'detailable_type' => Research::class,
            'detailable_id' => Research::factory()->create()->id,
            'title' => 'Penelitian dengan Laporan Selesai',
            'status' => ProposalStatus::COMPLETED,
            'start_year' => $currentYear,
            'submitter_id' => $this->dosen->id,
            'research_scheme_id' => $this->scheme->id,
        ]);

        ProgressReport::factory()->create([
            'proposal_id' => $reportedProposal->id,
            'reporting_period' => 'final',
            'reporting_year' => (int) $currentYear,
            'status' => ReportStatus::APPROVED,
            'submitted_by' => $this->dosen->id,
        ]);

        $this->actingAs($this->admin);

        Livewire::test(InstitutionalResearchReport::class)
            ->assertDontSee($draftProposal->title)
            ->assertDontSee($submittedProposal->title)
            ->assertSee($completedProposal->title)
            ->assertSee('Belum Laporan')
            ->assertSee($reportedProposal->title)
            ->assertSee(ReportStatus::APPROVED->label());
    }

    public function test_institutional_report_filter_by_report_status(): void
    {
        $currentYear = (string) date('Y');

        $completedNoReport = Proposal::factory()->create([
            'detailable_type' => Research::class,
            'detailable_id' => Research::factory()->create()->id,
            'title' => 'Judul Belum Bikin Laporan',
            'status' => ProposalStatus::COMPLETED,
            'start_year' => $currentYear,
            'submitter_id' => $this->dosen->id,
            'research_scheme_id' => $this->scheme->id,
        ]);

        $completedApprovedReport = Proposal::factory()->create([
            'detailable_type' => Research::class,
            'detailable_id' => Research::factory()->create()->id,
            'title' => 'Judul Sudah Disahkan LPPM',
            'status' => ProposalStatus::COMPLETED,
            'start_year' => $currentYear,
            'submitter_id' => $this->dosen->id,
            'research_scheme_id' => $this->scheme->id,
        ]);

        ProgressReport::factory()->create([
            'proposal_id' => $completedApprovedReport->id,
            'reporting_period' => 'final',
            'reporting_year' => (int) $currentYear,
            'status' => ReportStatus::APPROVED,
            'submitted_by' => $this->dosen->id,
        ]);

        $this->actingAs($this->admin);

        // Filter: Belum Laporan
        Livewire::test(InstitutionalResearchReport::class)
            ->set('selectedReportStatus', 'belum_laporan')
            ->assertSee($completedNoReport->title)
            ->assertDontSee($completedApprovedReport->title);

        // Filter: Approved
        Livewire::test(InstitutionalResearchReport::class)
            ->set('selectedReportStatus', ReportStatus::APPROVED->value)
            ->assertSee($completedApprovedReport->title)
            ->assertDontSee($completedNoReport->title);
    }

    public function test_final_report_index_shows_informative_filters_and_stats(): void
    {
        $currentYear = (string) date('Y');

        $prop1 = Proposal::factory()->create([
            'detailable_type' => Research::class,
            'detailable_id' => Research::factory()->create()->id,
            'title' => 'Penelitian Dosen Sendiri Selesai',
            'status' => ProposalStatus::COMPLETED,
            'start_year' => $currentYear,
            'submitter_id' => $this->dosen->id,
            'research_scheme_id' => $this->scheme->id,
        ]);

        ProgressReport::factory()->create([
            'proposal_id' => $prop1->id,
            'reporting_period' => 'final',
            'reporting_year' => (int) $currentYear,
            'status' => ReportStatus::APPROVED,
            'submitted_by' => $this->dosen->id,
        ]);

        $prop2 = Proposal::factory()->create([
            'detailable_type' => Research::class,
            'detailable_id' => Research::factory()->create()->id,
            'title' => 'Penelitian Dosen Belum Lapor',
            'status' => ProposalStatus::COMPLETED,
            'start_year' => $currentYear,
            'submitter_id' => $this->dosen->id,
            'research_scheme_id' => $this->scheme->id,
        ]);

        $this->actingAs($this->dosen);

        Livewire::test(ResearchFinalReportIndex::class)
            ->assertSee('Total Wajib Lapor')
            ->assertSee('Laporan Selesai')
            ->assertSee('Belum Laporan')
            ->assertSee($prop1->title)
            ->assertSee($prop2->title)
            ->set('statusFilter', 'belum_laporan')
            ->assertSee($prop2->title)
            ->assertDontSee($prop1->title);
    }

    public function test_pdf_export_reflects_report_status(): void
    {
        $currentYear = (string) date('Y');

        $prop = Proposal::factory()->create([
            'detailable_type' => Research::class,
            'detailable_id' => Research::factory()->create()->id,
            'title' => 'Penelitian Ekspor PDF',
            'status' => ProposalStatus::COMPLETED,
            'start_year' => $currentYear,
            'submitter_id' => $this->dosen->id,
            'research_scheme_id' => $this->scheme->id,
        ]);

        ProgressReport::factory()->create([
            'proposal_id' => $prop->id,
            'reporting_period' => 'final',
            'reporting_year' => (int) $currentYear,
            'status' => ReportStatus::APPROVED,
            'submitted_by' => $this->dosen->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('reports.research.pdf', ['period' => $currentYear]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }
}
