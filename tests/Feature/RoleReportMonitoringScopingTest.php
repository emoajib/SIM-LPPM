<?php

namespace Tests\Feature;

use App\Enums\ProposalStatus;
use App\Enums\ReportStatus;
use App\Models\Faculty;
use App\Models\Identity;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\Research;
use App\Models\StudyProgram;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleReportMonitoringScopingTest extends TestCase
{
    // Vetted by AI - Manual Review Required by Senior Engineer/Manager
    use RefreshDatabase;

    protected User $dosen1;

    protected User $dekanFaculty1;

    protected User $kaprodiProdi1;

    protected User $adminLppm;

    protected Faculty $faculty1;

    protected Faculty $faculty2;

    protected StudyProgram $prodi1;

    protected StudyProgram $prodi2;

    protected Proposal $proposalFaculty1;

    protected ProgressReport $reportFaculty1;

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
            'name' => 'Manajemen',
        ]);

        // Dosen in Prodi 1 (Faculty 1)
        $this->dosen1 = User::factory()->create(['name' => 'Dosen Prodi 1']);
        $this->dosen1->assignRole('dosen');
        Identity::factory()->create([
            'user_id' => $this->dosen1->id,
            'faculty_id' => $this->faculty1->id,
            'study_program_id' => $this->prodi1->id,
        ]);

        // Dekan of Faculty 1
        $this->dekanFaculty1 = User::factory()->create(['name' => 'Dekan Fakultas 1']);
        $this->dekanFaculty1->assignRole('dekan');
        Identity::factory()->create([
            'user_id' => $this->dekanFaculty1->id,
            'faculty_id' => $this->faculty1->id,
        ]);

        // Kaprodi of Prodi 1
        $this->kaprodiProdi1 = User::factory()->create(['name' => 'Kaprodi Prodi 1']);
        $this->kaprodiProdi1->assignRole('kaprodi');
        Identity::factory()->create([
            'user_id' => $this->kaprodiProdi1->id,
            'faculty_id' => $this->faculty1->id,
            'study_program_id' => $this->prodi1->id,
        ]);
        $this->prodi1->update(['kaprodi_user_id' => $this->kaprodiProdi1->id]);

        // Admin LPPM
        $this->adminLppm = User::factory()->create(['name' => 'Admin LPPM']);
        $this->adminLppm->assignRole('admin lppm');

        // Create completed research proposal for Dosen 1
        $research = Research::factory()->create();
        $this->proposalFaculty1 = Proposal::factory()->create([
            'submitter_id' => $this->dosen1->id,
            'detailable_type' => Research::class,
            'detailable_id' => $research->id,
            'status' => ProposalStatus::COMPLETED,
            'start_year' => (int) date('Y'),
        ]);

        // Create final report
        $this->reportFaculty1 = ProgressReport::create([
            'proposal_id' => $this->proposalFaculty1->id,
            'reporting_year' => (int) date('Y'),
            'reporting_period' => 'final',
            'status' => ReportStatus::SUBMITTED->value,
            'submitted_by' => $this->dosen1->id,
            'submitted_at' => now(),
        ]);
    }

    public function test_dekan_can_view_final_report_from_own_faculty()
    {
        $response = $this->actingAs($this->dekanFaculty1)
            ->withSession(['active_role' => 'dekan'])
            ->get(route('research.final-report.show', $this->proposalFaculty1->id));

        $response->assertStatus(200);
    }

    public function test_dekan_from_other_faculty_is_forbidden()
    {
        $dekanOther = User::factory()->create(['name' => 'Dekan Fakultas 2']);
        $dekanOther->assignRole('dekan');
        Identity::factory()->create([
            'user_id' => $dekanOther->id,
            'faculty_id' => $this->faculty2->id,
        ]);

        $response = $this->actingAs($dekanOther)
            ->withSession(['active_role' => 'dekan'])
            ->get(route('research.final-report.show', $this->proposalFaculty1->id));

        $response->assertStatus(403);
    }

    public function test_kaprodi_can_view_final_report_from_own_study_program()
    {
        $response = $this->actingAs($this->kaprodiProdi1)
            ->withSession(['active_role' => 'kaprodi'])
            ->get(route('research.final-report.show', $this->proposalFaculty1->id));

        $response->assertStatus(200);
    }

    public function test_kaprodi_from_other_study_program_is_forbidden()
    {
        $kaprodiOther = User::factory()->create(['name' => 'Kaprodi Prodi 2']);
        $kaprodiOther->assignRole('kaprodi');
        Identity::factory()->create([
            'user_id' => $kaprodiOther->id,
            'faculty_id' => $this->faculty2->id,
            'study_program_id' => $this->prodi2->id,
        ]);
        $this->prodi2->update(['kaprodi_user_id' => $kaprodiOther->id]);

        $response = $this->actingAs($kaprodiOther)
            ->withSession(['active_role' => 'kaprodi'])
            ->get(route('research.final-report.show', $this->proposalFaculty1->id));

        $response->assertStatus(403);
    }

    public function test_kaprodi_can_access_daily_notes_of_own_study_program()
    {
        $response = $this->actingAs($this->kaprodiProdi1)
            ->withSession(['active_role' => 'kaprodi'])
            ->get(route('research.daily-note.show', $this->proposalFaculty1->id));

        $response->assertStatus(200);
    }

    public function test_kaprodi_from_other_study_program_cannot_access_daily_notes()
    {
        $kaprodiOther = User::factory()->create(['name' => 'Kaprodi Prodi 2']);
        $kaprodiOther->assignRole('kaprodi');
        Identity::factory()->create([
            'user_id' => $kaprodiOther->id,
            'faculty_id' => $this->faculty2->id,
            'study_program_id' => $this->prodi2->id,
        ]);
        $this->prodi2->update(['kaprodi_user_id' => $kaprodiOther->id]);

        $response = $this->actingAs($kaprodiOther)
            ->withSession(['active_role' => 'kaprodi'])
            ->get(route('research.daily-note.show', $this->proposalFaculty1->id));

        $response->assertStatus(403);
    }

    public function test_admin_lppm_can_view_all_reports()
    {
        $response = $this->actingAs($this->adminLppm)
            ->withSession(['active_role' => 'admin lppm'])
            ->get(route('research.final-report.show', $this->proposalFaculty1->id));

        $response->assertStatus(200);
    }

    public function test_dekan_and_kaprodi_can_view_report_indexes()
    {
        $this->actingAs($this->dekanFaculty1)
            ->withSession(['active_role' => 'dekan'])
            ->get(route('research.final-report.index'))
            ->assertStatus(200)
            ->assertSee($this->proposalFaculty1->title);

        $this->actingAs($this->kaprodiProdi1)
            ->withSession(['active_role' => 'kaprodi'])
            ->get(route('research.daily-note.index'))
            ->assertStatus(200)
            ->assertSee($this->proposalFaculty1->title);
    }

    public function test_cross_faculty_dekan_cannot_export_financial_or_daily_note()
    {
        $dekanOther = User::factory()->create(['name' => 'Dekan Fakultas 2']);
        $dekanOther->assignRole('dekan');
        Identity::factory()->create([
            'user_id' => $dekanOther->id,
            'faculty_id' => $this->faculty2->id,
        ]);

        $this->actingAs($dekanOther)
            ->withSession(['active_role' => 'dekan'])
            ->get(route('financial-reports.export-pdf', $this->proposalFaculty1->id))
            ->assertStatus(403);

        $this->actingAs($dekanOther)
            ->withSession(['active_role' => 'dekan'])
            ->get(route('daily-notes.export-pdf', $this->proposalFaculty1->id))
            ->assertStatus(403);
    }

    public function test_cross_prodi_kaprodi_cannot_export_financial_or_daily_note()
    {
        $kaprodiOther = User::factory()->create(['name' => 'Kaprodi Prodi 2']);
        $kaprodiOther->assignRole('kaprodi');
        Identity::factory()->create([
            'user_id' => $kaprodiOther->id,
            'faculty_id' => $this->faculty2->id,
            'study_program_id' => $this->prodi2->id,
        ]);
        $this->prodi2->update(['kaprodi_user_id' => $kaprodiOther->id]);

        $this->actingAs($kaprodiOther)
            ->withSession(['active_role' => 'kaprodi'])
            ->get(route('financial-reports.export-pdf', $this->proposalFaculty1->id))
            ->assertStatus(403);

        $this->actingAs($kaprodiOther)
            ->withSession(['active_role' => 'kaprodi'])
            ->get(route('daily-notes.export-pdf', $this->proposalFaculty1->id))
            ->assertStatus(403);
    }
}
