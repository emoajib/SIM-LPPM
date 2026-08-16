<?php

namespace Tests\Feature\Reports;

use App\Enums\ProposalStatus;
use App\Livewire\CommunityService\DailyNote\Show;
use App\Livewire\Research\DailyNote\Show as ResearchDailyNoteShow;
use App\Models\CommunityService;
use App\Models\Identity;
use App\Models\Proposal;
use App\Models\Research;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DailyNoteApprovalUploadTest extends TestCase
{
    use RefreshDatabase;

    protected User $lecturer;

    protected Proposal $proposal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\RoleSeeder'])->run();

        Storage::fake('public');

        $this->lecturer = User::factory()->create();
        $this->lecturer->assignRole('dosen');
        Identity::factory()->create(['user_id' => $this->lecturer->id, 'type' => 'dosen']);

        $research = Research::factory()->create();

        $this->proposal = Proposal::factory()->create([
            'submitter_id' => $this->lecturer->id,
            'detailable_type' => 'App\Models\Research',
            'detailable_id' => $research->id,
            'status' => ProposalStatus::COMPLETED,
            'title' => 'Penelitian Pengujian Pengesahan Logbook 2026',
        ]);

        $this->proposal->teamMembers()->attach($this->lecturer->id, [
            'role' => 'ketua',
            'tasks' => 'Ketua Peneliti',
        ]);

        Setting::updateOrCreate(['key' => 'logbook_approval_mode'], ['value' => 'upload']);
    }

    public function test_lecturer_can_upload_and_remove_logbook_approval_file(): void
    {
        $this->actingAs($this->lecturer);
        session(['active_role' => 'dosen']);

        $dummyPdf = UploadedFile::fake()->create('lembar_pengesahan_basah.pdf', 100, 'application/pdf');

        Livewire::test(ResearchDailyNoteShow::class, ['proposal' => $this->proposal])
            ->set('logbookApprovalFile', $dummyPdf)
            ->call('saveLogbookApprovalFile')
            ->assertHasNoErrors();

        $this->proposal->refresh();
        $this->assertTrue($this->proposal->hasMedia('logbook_approval_file'));

        // Test remove
        Livewire::test(ResearchDailyNoteShow::class, ['proposal' => $this->proposal])
            ->call('removeLogbookApprovalFile')
            ->assertHasNoErrors();

        $this->proposal->refresh();
        $this->assertFalse($this->proposal->hasMedia('logbook_approval_file'));
    }

    public function test_upload_requires_valid_pdf(): void
    {
        $this->actingAs($this->lecturer);
        session(['active_role' => 'dosen']);

        $dummyTxt = UploadedFile::fake()->create('dokumen.txt', 100, 'text/plain');

        Livewire::test(ResearchDailyNoteShow::class, ['proposal' => $this->proposal])
            ->set('logbookApprovalFile', $dummyTxt)
            ->call('saveLogbookApprovalFile')
            ->assertHasErrors(['logbookApprovalFile']);
    }

    public function test_community_service_daily_note_upload(): void
    {
        $cs = CommunityService::factory()->create();
        $csProposal = Proposal::factory()->create([
            'submitter_id' => $this->lecturer->id,
            'detailable_type' => 'App\Models\CommunityService',
            'detailable_id' => $cs->id,
            'status' => ProposalStatus::COMPLETED,
            'title' => 'PKM Pengujian Pengesahan Logbook 2026',
        ]);
        $csProposal->teamMembers()->attach($this->lecturer->id, [
            'role' => 'ketua',
            'tasks' => 'Ketua Pengabdi',
        ]);

        $this->actingAs($this->lecturer);
        session(['active_role' => 'dosen']);

        $dummyPdf = UploadedFile::fake()->create('lembar_pengesahan_pkm.pdf', 100, 'application/pdf');

        Livewire::test(Show::class, ['proposal' => $csProposal])
            ->set('logbookApprovalFile', $dummyPdf)
            ->call('saveLogbookApprovalFile')
            ->assertHasNoErrors();

        $csProposal->refresh();
        $this->assertTrue($csProposal->hasMedia('logbook_approval_file'));
    }
}
