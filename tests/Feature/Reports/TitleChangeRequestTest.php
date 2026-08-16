<?php

namespace Tests\Feature\Reports;

use App\Enums\ProposalStatus;
use App\Livewire\CommunityService\FinalReport\Show;
use App\Livewire\Research\FinalReport\Show as ResearchFinalReportShow;
use App\Models\CommunityService;
use App\Models\Identity;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\Research;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TitleChangeRequestTest extends TestCase
{
    use RefreshDatabase;

    protected User $lecturer;

    protected User $adminLppm;

    protected Proposal $proposal;

    protected ProgressReport $progressReport;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\RoleSeeder'])->run();

        $this->lecturer = User::factory()->create();
        $this->lecturer->assignRole('dosen');
        Identity::factory()->create(['user_id' => $this->lecturer->id, 'type' => 'dosen']);

        $this->adminLppm = User::factory()->create();
        $this->adminLppm->assignRole('admin lppm');
        Identity::factory()->create(['user_id' => $this->adminLppm->id, 'type' => 'admin']);

        $research = Research::factory()->create();

        $this->proposal = Proposal::factory()->create([
            'submitter_id' => $this->lecturer->id,
            'detailable_type' => 'App\Models\Research',
            'detailable_id' => $research->id,
            'status' => ProposalStatus::COMPLETED,
            'title' => 'Judul Awal Penelitian 2026',
        ]);

        $this->proposal->teamMembers()->attach($this->lecturer->id, [
            'role' => 'ketua',
            'tasks' => 'Ketua Peneliti',
        ]);

        $this->progressReport = ProgressReport::factory()->create([
            'proposal_id' => $this->proposal->id,
            'reporting_period' => 'final',
            'reporting_year' => 2026,
            'status' => 'draft',
            'submitted_by' => $this->lecturer->id,
        ]);
    }

    public function test_lecturer_can_submit_title_change_request(): void
    {
        $this->actingAs($this->lecturer);
        session(['active_role' => 'dosen']);

        Livewire::test(ResearchFinalReportShow::class, ['proposal' => $this->proposal])
            ->set('proposedTitle', 'Judul Baru yang Disesuaikan dengan Hasil Riset 2026')
            ->set('titleChangeReason', 'Disesuaikan karena fokus riset mengalami pendalaman pada model neural network.')
            ->call('saveTitleChangeRequest')
            ->assertHasNoErrors()
            ->assertDispatched('banner-message');

        $this->progressReport->refresh();

        $this->assertEquals('Judul Baru yang Disesuaikan dengan Hasil Riset 2026', $this->progressReport->proposed_title);
        $this->assertEquals('pending', $this->progressReport->title_change_status);
        $this->assertEquals('Judul Awal Penelitian 2026', $this->proposal->fresh()->title);
    }

    public function test_title_change_request_requires_valid_data(): void
    {
        $this->actingAs($this->lecturer);
        session(['active_role' => 'dosen']);

        Livewire::test(ResearchFinalReportShow::class, ['proposal' => $this->proposal])
            ->set('proposedTitle', '')
            ->set('titleChangeReason', '')
            ->call('saveTitleChangeRequest')
            ->assertHasErrors(['proposedTitle', 'titleChangeReason']);
    }

    public function test_lecturer_can_cancel_pending_title_change_request(): void
    {
        $this->actingAs($this->lecturer);
        session(['active_role' => 'dosen']);

        $this->progressReport->update([
            'proposed_title' => 'Judul Sementara yang Ingin Dibatalkan',
            'title_change_reason' => 'Alasan sementara',
            'title_change_status' => 'pending',
        ]);

        Livewire::test(ResearchFinalReportShow::class, ['proposal' => $this->proposal])
            ->call('cancelTitleChangeRequest')
            ->assertDispatched('banner-message');

        $this->progressReport->refresh();

        $this->assertNull($this->progressReport->proposed_title);
        $this->assertNull($this->progressReport->title_change_status);
    }

    public function test_admin_lppm_can_approve_title_change(): void
    {
        $this->actingAs($this->adminLppm);
        session(['active_role' => 'admin lppm']);

        $this->progressReport->update([
            'proposed_title' => 'Judul Baru Disetujui LPPM 2026',
            'title_change_reason' => 'Sesuai dengan arahan reviewer pada seminar hasil.',
            'title_change_status' => 'pending',
        ]);

        Livewire::test(ResearchFinalReportShow::class, ['proposal' => $this->proposal])
            ->set('titleChangeReviewNotes', 'Perubahan judul disetujui.')
            ->call('approveTitleChange')
            ->assertDispatched('banner-message');

        $this->progressReport->refresh();
        $this->proposal->refresh();

        $this->assertEquals('approved', $this->progressReport->title_change_status);
        $this->assertEquals($this->adminLppm->id, $this->progressReport->title_change_reviewer_id);
        $this->assertEquals('Judul Baru Disetujui LPPM 2026', $this->proposal->title);
    }

    public function test_admin_lppm_can_reject_title_change_with_notes(): void
    {
        $this->actingAs($this->adminLppm);
        session(['active_role' => 'admin lppm']);

        $this->progressReport->update([
            'proposed_title' => 'Judul Baru yang Tidak Sesuai Kontrak',
            'title_change_reason' => 'Perubahan sepihak',
            'title_change_status' => 'pending',
        ]);

        Livewire::test(ResearchFinalReportShow::class, ['proposal' => $this->proposal])
            ->set('titleChangeReviewNotes', 'Judul terlalu menyimpang dari kontrak awal.')
            ->call('rejectTitleChange')
            ->assertDispatched('banner-message');

        $this->progressReport->refresh();
        $this->proposal->refresh();

        $this->assertEquals('rejected', $this->progressReport->title_change_status);
        $this->assertEquals('Judul Awal Penelitian 2026', $this->proposal->title);
        $this->assertEquals('Judul terlalu menyimpang dari kontrak awal.', $this->progressReport->title_change_review_notes);
    }

    public function test_community_service_title_change_flow(): void
    {
        $cs = CommunityService::factory()->create();
        $csProposal = Proposal::factory()->create([
            'submitter_id' => $this->lecturer->id,
            'detailable_type' => 'App\Models\CommunityService',
            'detailable_id' => $cs->id,
            'status' => ProposalStatus::COMPLETED,
            'title' => 'Judul Awal PKM 2026',
        ]);
        $csProposal->teamMembers()->attach($this->lecturer->id, [
            'role' => 'ketua',
            'tasks' => 'Ketua Pengabdi',
        ]);
        $csReport = ProgressReport::factory()->create([
            'proposal_id' => $csProposal->id,
            'reporting_period' => 'final',
            'reporting_year' => 2026,
            'status' => 'draft',
            'submitted_by' => $this->lecturer->id,
        ]);

        $this->actingAs($this->lecturer);
        session(['active_role' => 'dosen']);

        Livewire::test(Show::class, ['proposal' => $csProposal])
            ->set('proposedTitle', 'Judul Baru PKM yang Disesuaikan dengan Permintaan Mitra 2026')
            ->set('titleChangeReason', 'Permintaan khusus dari pihak mitra UMKM desa.')
            ->call('saveTitleChangeRequest')
            ->assertHasNoErrors();

        $csReport->refresh();
        $this->assertEquals('pending', $csReport->title_change_status);

        // Admin approves
        $this->actingAs($this->adminLppm);
        session(['active_role' => 'admin lppm']);

        Livewire::test(Show::class, ['proposal' => $csProposal])
            ->call('approveTitleChange')
            ->assertDispatched('banner-message');

        $csReport->refresh();
        $csProposal->refresh();

        $this->assertEquals('approved', $csReport->title_change_status);
        $this->assertEquals('Judul Baru PKM yang Disesuaikan dengan Permintaan Mitra 2026', $csProposal->title);
    }
}
