<?php

namespace Tests\Feature;

use App\Enums\ProposalStatus;
use App\Enums\ReportStatus;
use App\Livewire\Research\FinalReport\Show;
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
}
