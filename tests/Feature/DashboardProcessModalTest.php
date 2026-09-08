<?php

// Vetted by AI - Manual Review Required by Senior Engineer/Manager

namespace Tests\Feature;

use App\Enums\ProposalStatus;
use App\Livewire\Dashboard\AdminDashboard;
use App\Livewire\Dashboard\KepalaLppmDashboard;
use App\Models\MandatoryOutput;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\ProposalOutput;
use App\Models\Research;
use App\Models\User;
use Database\Seeders\InstitutionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardProcessModalTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminLppm;

    protected User $kepalaLppm;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(InstitutionSeeder::class);

        $this->adminLppm = User::factory()->create(['name' => 'Admin LPPM']);
        $this->adminLppm->assignRole('admin lppm');

        $this->kepalaLppm = User::factory()->create(['name' => 'Kepala LPPM']);
        $this->kepalaLppm->assignRole('kepala lppm');
    }

    public function test_can_open_and_close_process_modal_for_all_types()
    {
        $this->actingAs($this->kepalaLppm);
        Session::put('active_role', 'kepala lppm');

        $component = Livewire::test(KepalaLppmDashboard::class);

        $types = ['usulan', 'perbaikan_usulan', 'catatan_harian_keuangan', 'laporan_akhir', 'review', 'monev', 'iku'];

        foreach ($types as $type) {
            $component->call('openProcessModal', $type);
            $this->assertEquals($type, $component->get('activeProcessType'));
            $this->assertNotEmpty($component->get('processModalTitle'));
            $this->assertNotEmpty($component->get('processModalDescription'));
            $this->assertNotEmpty($component->get('processModalIcon'));
            $this->assertNotEmpty($component->get('processModalColor'));
        }

        $component->call('closeProcessModal');
        $this->assertNull($component->get('activeProcessType'));
    }

    public function test_process_modal_data_filtering_and_search()
    {
        $this->actingAs($this->adminLppm);
        Session::put('active_role', 'admin lppm');

        $research = Research::factory()->create();
        $proposal = Proposal::factory()->create([
            'title' => 'Proposal Riset AI Kecerdasan Buatan',
            'detailable_type' => 'App\Models\Research',
            'detailable_id' => $research->id,
            'start_year' => date('Y'),
            'submitter_id' => $this->adminLppm->id,
        ]);

        $component = Livewire::test(AdminDashboard::class);
        $component->call('openProcessModal', 'usulan');

        $data = $component->get('processModalData');
        $this->assertTrue($data->contains('id', $proposal->id));

        // Test search filter
        $component->set('processSearch', 'Kecerdasan Buatan');
        $filtered = $component->get('processModalData');
        $this->assertTrue($filtered->contains('id', $proposal->id));

        $component->set('processSearch', 'Pencarian Tidak Ada');
        $empty = $component->get('processModalData');
        $this->assertFalse($empty->contains('id', $proposal->id));

        // Reset search and test type filter
        $component->set('processSearch', '');
        $component->set('processTypeFilter', 'community_service');
        $filteredCS = $component->get('processModalData');
        $this->assertFalse($filteredCS->contains('id', $proposal->id));

        $component->set('processTypeFilter', 'research');
        $filteredRes = $component->get('processModalData');
        $this->assertTrue($filteredRes->contains('id', $proposal->id));
    }

    public function test_iku_modal_renders_evidence_and_links_to_laporan()
    {
        $this->actingAs($this->kepalaLppm);
        Session::put('active_role', 'kepala lppm');

        $research = Research::factory()->create();
        $proposal = Proposal::factory()->create([
            'title' => 'Proposal Riset dengan Bukti Luaran YouTube',
            'detailable_type' => 'App\Models\Research',
            'detailable_id' => $research->id,
            'start_year' => date('Y'),
            'status' => ProposalStatus::COMPLETED->value,
            'submitter_id' => $this->kepalaLppm->id,
        ]);

        $proposalOutput = ProposalOutput::create([
            'proposal_id' => $proposal->id,
            'output_year' => 1,
            'category' => 'Wajib',
            'group' => 'video',
            'type' => 'Video Kegiatan (Publikasi Youtube/Medsos)',
            'target_status' => 'Published',
        ]);

        $progressReport = ProgressReport::create([
            'proposal_id' => $proposal->id,
            'reporting_year' => date('Y'),
            'reporting_period' => 'final',
            'status' => 'submitted',
            'submitted_by' => $this->kepalaLppm->id,
        ]);

        MandatoryOutput::factory()->create([
            'progress_report_id' => $progressReport->id,
            'proposal_output_id' => $proposalOutput->id,
            'status_type' => 'published',
            'video_url' => 'https://youtu.be/testvideo123',
            'platform' => 'YouTube',
        ]);

        $component = Livewire::test(KepalaLppmDashboard::class);
        $component->call('openProcessModal', 'iku');

        $component->assertSee('Buka Laporan')
            ->assertSee('YouTube')
            ->assertSee('https://youtu.be/testvideo123')
            ->assertSee('Bukti di Laporan')
            ->assertSee(route('research.final-report.show', $proposal->id));
    }
}
