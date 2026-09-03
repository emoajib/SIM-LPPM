<?php

namespace Tests\Feature;

use App\Enums\ProposalStatus;
use App\Enums\ReportStatus;
use App\Livewire\Research\FinalReport\Show;
use App\Models\BudgetItem;
use App\Models\CommunityService;
use App\Models\DailyNote;
use App\Models\Faculty;
use App\Models\Identity;
use App\Models\Institution;
use App\Models\Proposal;
use App\Models\ProposalOutput;
use App\Models\Research;
use App\Models\User;
use Database\Seeders\InstitutionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ReportWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $dosen;

    protected Proposal $proposal;

    protected User $dekan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->seed(RoleSeeder::class);
        $this->seed(InstitutionSeeder::class);
        $institution = Institution::first();

        $faculty = Faculty::factory()->create([
            'institution_id' => $institution->id,
        ]);

        $this->dosen = User::factory()->create();
        $this->dosen->assignRole('dosen');
        Identity::factory()->create(['user_id' => $this->dosen->id, 'faculty_id' => $faculty->id]);

        $research = Research::factory()->create();
        $this->proposal = Proposal::factory()->create([
            'submitter_id' => $this->dosen->id,
            'detailable_id' => $research->id,
            'detailable_type' => Research::class,
            'status' => ProposalStatus::COMPLETED,
        ]);

        // Add mandatory output to proposal
        ProposalOutput::factory()->create([
            'proposal_id' => $this->proposal->id,
            'category' => 'Wajib',
            'type' => 'Jurnal Nasional',
        ]);

        $this->dekan = User::factory()->create();
        $this->dekan->assignRole('dekan');
        Identity::factory()->create(['user_id' => $this->dekan->id, 'faculty_id' => $faculty->id]);
    }

    public function test_dosen_can_create_and_submit_final_report()
    {
        $this->actingAs($this->dosen);

        $file = UploadedFile::fake()->create('laporan.pdf', 100);
        $realizationFile = UploadedFile::fake()->create('realization.pdf', 100);
        $presentationFile = UploadedFile::fake()->create('presentation.pdf', 100);

        $component = Livewire::test(Show::class, [
            'proposal' => $this->proposal,
        ])
            ->set('form.summaryUpdate', 'This is the final summary.')
            ->set('form.keywordsInput', 'final; report; research')
            ->set('substanceFile', $file)
            ->set('realizationFile', $realizationFile)
            ->set('presentationFile', $presentationFile)
            ->call('save');

        $component->assertHasNoErrors();

        $this->proposal->refresh();
        $report = $this->proposal->progressReports()->where('reporting_period', 'final')->first();

        $this->assertNotNull($report);
        $this->assertEquals(ReportStatus::DRAFT, $report->status);

        // Submit
        $component->call('submit');
        $component->assertHasNoErrors();
        $this->assertEquals(ReportStatus::SUBMITTED, $report->fresh()->status);
    }

    public function test_dosen_can_submit_final_report_without_presentation_file()
    {
        $this->actingAs($this->dosen);

        $file = UploadedFile::fake()->create('laporan.pdf', 100);
        $realizationFile = UploadedFile::fake()->create('realization.pdf', 100);

        $component = Livewire::test(Show::class, [
            'proposal' => $this->proposal,
        ])
            ->set('form.summaryUpdate', 'This is the final summary.')
            ->set('form.keywordsInput', 'final; report; research')
            ->set('substanceFile', $file)
            ->set('realizationFile', $realizationFile)
            ->call('save');

        $component->assertHasNoErrors();

        $this->proposal->refresh();
        $report = $this->proposal->progressReports()->where('reporting_period', 'final')->first();

        $this->assertNotNull($report);
        $this->assertEquals(ReportStatus::DRAFT, $report->status);

        // Submit without presentation file (presentation should only be required for Community Service/PKM)
        $component->call('submit');
        $component->assertHasNoErrors();
        $this->assertEquals(ReportStatus::SUBMITTED, $report->fresh()->status);
    }

    public function test_dosen_can_submit_final_report_without_realization_file()
    {
        $this->actingAs($this->dosen);

        $file = UploadedFile::fake()->create('laporan.pdf', 100);

        $component = Livewire::test(Show::class, [
            'proposal' => $this->proposal,
        ])
            ->set('form.summaryUpdate', 'This is the final summary without realization file.')
            ->set('form.keywordsInput', 'final; report; research')
            ->set('substanceFile', $file)
            ->call('save');

        $component->assertHasNoErrors();

        $this->proposal->refresh();
        $report = $this->proposal->progressReports()->where('reporting_period', 'final')->first();

        $this->assertNotNull($report);
        $this->assertEquals(ReportStatus::DRAFT, $report->status);

        // Submit without realization file (realization file is optional)
        $component->call('submit');
        $component->assertHasNoErrors();
        $this->assertEquals(ReportStatus::SUBMITTED, $report->fresh()->status);
    }

    public function test_dosen_can_add_daily_note()
    {
        $this->actingAs($this->dosen);

        $component = Livewire::test(\App\Livewire\Research\DailyNote\Show::class, [
            'proposal' => $this->proposal,
        ])
            ->set('activity_date', now()->format('Y-m-d'))
            ->set('activity_description', 'Doing some research today.')
            ->set('progress_percentage', 10)
            ->call('save');

        $component->assertHasNoErrors();

        $this->proposal->refresh();
        $note = $this->proposal->dailyNotes()->first();

        $this->assertNotNull($note);
        $this->assertEquals('Doing some research today.', $note->activity_description);
        $this->assertEquals(10, $note->progress_percentage);
    }

    public function test_dekan_can_view_daily_notes()
    {
        $this->actingAs($this->dekan)
            ->withSession(['active_role' => 'dekan']);

        Livewire::test(\App\Livewire\Research\DailyNote\Show::class, [
            'proposal' => $this->proposal,
        ])
            ->assertStatus(200);
    }

    public function test_dosen_can_set_partner_changes_in_final_report()
    {
        $this->actingAs($this->dosen);

        $file = UploadedFile::fake()->create('laporan.pdf', 100);
        $realizationFile = UploadedFile::fake()->create('realization.pdf', 100);

        $component = Livewire::test(Show::class, [
            'proposal' => $this->proposal,
        ])
            ->set('form.summaryUpdate', 'Final summary with partner changes.')
            ->set('form.keywordsInput', 'final; partner; change')
            ->set('form.partnerChanges', 'Mitra baru ditambahkan: Universitas ABC.')
            ->set('substanceFile', $file)
            ->set('realizationFile', $realizationFile)
            ->call('save');

        $component->assertHasNoErrors();

        $this->proposal->refresh();
        $report = $this->proposal->progressReports()->where('reporting_period', 'final')->first();

        $this->assertNotNull($report);
        $this->assertEquals('Mitra baru ditambahkan: Universitas ABC.', $report->partner_changes);
    }

    public function test_dosen_can_save_substance_file_without_submitting_report()
    {
        $this->actingAs($this->dosen);

        $file = UploadedFile::fake()->createWithContent(
            'substansi.pdf',
            "%PDF-1.4\n% Test PDF content for substance file.\n%%EOF"
        );

        Livewire::test(Show::class, [
            'proposal' => $this->proposal,
        ])
            ->set('substanceFile', $file)
            ->call('saveSubstanceFileNow')
            ->assertHasNoErrors()
            ->assertDispatched('report-saved');

        $this->proposal->refresh();
        $report = $this->proposal->progressReports()->where('reporting_period', 'final')->first();

        $this->assertNotNull($report);
        $this->assertEquals(ReportStatus::DRAFT, $report->status);
        $this->assertTrue($report->hasMedia('substance_file'));
        $this->assertEquals('substansi.pdf', $report->getFirstMedia('substance_file')->name);
    }

    public function test_save_substance_file_now_requires_file()
    {
        $this->actingAs($this->dosen);

        Livewire::test(Show::class, [
            'proposal' => $this->proposal,
        ])
            ->call('saveSubstanceFileNow');

        $this->assertDatabaseMissing('progress_reports', [
            'proposal_id' => $this->proposal->id,
            'reporting_period' => 'final',
        ]);
    }

    public function test_dosen_cannot_submit_final_report_if_daily_notes_under_70_percent_of_budget()
    {
        $this->actingAs($this->dosen);

        // Add 10,000,000 budget
        BudgetItem::factory()->create([
            'proposal_id' => $this->proposal->id,
            'volume' => 1,
            'unit_price' => 10000000,
            'total_price' => 10000000,
        ]);

        // Add 5,000,000 daily note (50% - under 70%)
        DailyNote::factory()->create([
            'proposal_id' => $this->proposal->id,
            'amount' => 5000000,
            'activity_date' => now(),
            'activity_description' => 'Field research',
        ]);

        $file = UploadedFile::fake()->create('laporan.pdf', 100);
        $realizationFile = UploadedFile::fake()->create('realization.pdf', 100);

        $component = Livewire::test(Show::class, [
            'proposal' => $this->proposal,
        ])
            ->set('form.summaryUpdate', 'Final summary')
            ->set('form.keywordsInput', 'final; test')
            ->set('substanceFile', $file)
            ->set('realizationFile', $realizationFile)
            ->call('save');

        $component->call('submit');

        $report = $this->proposal->progressReports()->where('reporting_period', 'final')->first();
        $this->assertEquals(ReportStatus::DRAFT, $report->status);
    }

    public function test_dosen_can_submit_final_report_if_daily_notes_reach_70_percent_of_budget()
    {
        $this->actingAs($this->dosen);

        // Add 10,000,000 budget
        BudgetItem::factory()->create([
            'proposal_id' => $this->proposal->id,
            'volume' => 1,
            'unit_price' => 10000000,
            'total_price' => 10000000,
        ]);

        // Add 7,000,000 daily note (70% - should pass)
        DailyNote::factory()->create([
            'proposal_id' => $this->proposal->id,
            'amount' => 7000000,
            'activity_date' => now(),
            'activity_description' => 'Field research',
        ]);

        $file = UploadedFile::fake()->create('laporan.pdf', 100);
        $realizationFile = UploadedFile::fake()->create('realization.pdf', 100);

        $component = Livewire::test(Show::class, [
            'proposal' => $this->proposal,
        ])
            ->set('form.summaryUpdate', 'Final summary')
            ->set('form.keywordsInput', 'final; test')
            ->set('substanceFile', $file)
            ->set('realizationFile', $realizationFile)
            ->call('save');

        $component->call('submit');

        $report = $this->proposal->progressReports()->where('reporting_period', 'final')->first();
        $this->assertEquals(ReportStatus::SUBMITTED, $report->status);
    }

    public function test_dosen_can_upload_and_save_research_attachments_and_signature()
    {
        $this->actingAs($this->dosen);

        $pdfContent = "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF";
        $file = UploadedFile::fake()->createWithContent('laporan.pdf', $pdfContent);
        $realizationFile = UploadedFile::fake()->createWithContent('realization.pdf', $pdfContent);
        $signatureFile = UploadedFile::fake()->createWithContent('pengesahan.pdf', $pdfContent);
        $teachingMaterialFile = UploadedFile::fake()->createWithContent('bahan_ajar.pdf', $pdfContent);

        $component = Livewire::test(Show::class, [
            'proposal' => $this->proposal,
        ])
            ->set('form.summaryUpdate', 'Final summary with attachments')
            ->set('form.keywordsInput', 'final; test; attachment')
            ->set('substanceFile', $file)
            ->set('realizationFile', $realizationFile)
            ->set('signatureFile', $signatureFile)
            ->set('teachingMaterialFile', $teachingMaterialFile)
            ->call('save');

        $component->assertHasNoErrors();

        $report = $this->proposal->progressReports()->where('reporting_period', 'final')->first();
        $this->assertNotNull($report);
        $this->assertTrue($report->hasMedia('signature_page'));
        $this->assertTrue($report->hasMedia('teaching_material_file'));

        // Test removing teaching material file
        $component->call('removeTeachingMaterialFile');
        $report->refresh();
        $this->assertFalse($report->hasMedia('teaching_material_file'));
    }

    public function test_dosen_can_upload_and_save_community_service_attachments()
    {
        $communityService = CommunityService::factory()->create();
        $csProposal = Proposal::factory()->create([
            'submitter_id' => $this->dosen->id,
            'detailable_id' => $communityService->id,
            'detailable_type' => CommunityService::class,
            'status' => ProposalStatus::COMPLETED,
        ]);

        $this->actingAs($this->dosen);

        $pdfContent = "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF";
        $file = UploadedFile::fake()->createWithContent('laporan_pkm.pdf', $pdfContent);
        $realizationFile = UploadedFile::fake()->createWithContent('realization_pkm.pdf', $pdfContent);
        $partnerAgreement = UploadedFile::fake()->createWithContent('kesediaan_mitra.pdf', $pdfContent);
        $chairpersonStatement = UploadedFile::fake()->createWithContent('pernyataan_ketua.pdf', $pdfContent);
        $serviceLocationMap = UploadedFile::fake()->createWithContent('peta_lokasi.pdf', $pdfContent);
        $officialReport = UploadedFile::fake()->createWithContent('berita_acara.pdf', $pdfContent);
        $assignmentLetter = UploadedFile::fake()->createWithContent('surat_tugas.pdf', $pdfContent);
        $questionnaire = UploadedFile::fake()->createWithContent('kuisioner.pdf', $pdfContent);
        $teamAttendance = UploadedFile::fake()->createWithContent('hadir_tim.pdf', $pdfContent);
        $participantAttendance = UploadedFile::fake()->createWithContent('hadir_peserta.pdf', $pdfContent);
        $trainingMaterial = UploadedFile::fake()->createWithContent('materi_pkm.pdf', $pdfContent);
        $activityPhoto = UploadedFile::fake()->createWithContent('foto_kegiatan.pdf', $pdfContent);

        $component = Livewire::test(\App\Livewire\CommunityService\FinalReport\Show::class, [
            'proposal' => $csProposal,
        ])
            ->set('form.summaryUpdate', 'Final summary PKM with attachments')
            ->set('form.keywordsInput', 'pkm; final; test')
            ->set('substanceFile', $file)
            ->set('realizationFile', $realizationFile)
            ->set('partnerAgreementFile', $partnerAgreement)
            ->set('chairpersonStatementFile', $chairpersonStatement)
            ->set('serviceLocationMapFile', $serviceLocationMap)
            ->set('officialReportPkmFile', $officialReport)
            ->set('assignmentLetterPkmFile', $assignmentLetter)
            ->set('questionnairePkmFile', $questionnaire)
            ->set('teamAttendanceFile', $teamAttendance)
            ->set('participantAttendanceFile', $participantAttendance)
            ->set('trainingMaterialFile', $trainingMaterial)
            ->set('activityPhotosFiles', [$activityPhoto])
            ->call('save');

        $component->assertHasNoErrors();

        $report = $csProposal->progressReports()->where('reporting_period', 'final')->first();
        $this->assertNotNull($report);
        $this->assertTrue($report->hasMedia('partner_agreement_letter'));
        $this->assertTrue($report->hasMedia('chairperson_statement_letter'));
        $this->assertTrue($report->hasMedia('service_location_map'));
        $this->assertTrue($report->hasMedia('official_report_pkm'));
        $this->assertTrue($report->hasMedia('assignment_letter_pkm'));
        $this->assertTrue($report->hasMedia('questionnaire_pkm'));
        $this->assertTrue($report->hasMedia('team_attendance_list'));
        $this->assertTrue($report->hasMedia('participant_attendance_list'));
        $this->assertTrue($report->hasMedia('training_material_pkm'));
        $this->assertTrue($report->hasMedia('activity_photos_pkm'));

        // Test removing an attachment
        $component->call('removePartnerAgreementFile');
        $report->refresh();
        $this->assertFalse($report->hasMedia('partner_agreement_letter'));

        // Test sequential uploading of Lampiran 10 and Lampiran 11
        $newParticipantAttendance = UploadedFile::fake()->createWithContent('hadir_peserta_baru.pdf', $pdfContent);
        $newTrainingMaterial = UploadedFile::fake()->createWithContent('materi_pkm_baru.pdf', $pdfContent);

        $component->set('participantAttendanceFile', $newParticipantAttendance)
            ->set('trainingMaterialFile', $newTrainingMaterial)
            ->call('save')
            ->assertHasNoErrors();

        $report->refresh();
        $this->assertTrue($report->hasMedia('participant_attendance_list'));
        $this->assertTrue($report->hasMedia('training_material_pkm'));
        $this->assertEquals('hadir_peserta_baru.pdf', $report->getFirstMedia('participant_attendance_list')->name);
        $this->assertEquals('materi_pkm_baru.pdf', $report->getFirstMedia('training_material_pkm')->name);
    }
}
