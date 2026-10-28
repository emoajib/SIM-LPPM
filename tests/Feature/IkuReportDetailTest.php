<?php

namespace Tests\Feature;

use App\Enums\PolicyInvolvementStatus;
use App\Enums\ProposalStatus;
use App\Livewire\Reports\IkuReport;
use App\Models\Identity;
use App\Models\MandatoryOutput;
use App\Models\PolicyInvolvement;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\ProposalOutput;
use App\Models\Research;
use App\Models\User;
use Database\Seeders\InstitutionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class IkuReportDetailTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminLppm;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(InstitutionSeeder::class);

        $this->adminLppm = User::factory()->create();
        $this->adminLppm->assignRole('admin lppm');
        $this->actingAs($this->adminLppm);
    }

    private function makeDosen(string $name, array $ids = []): User
    {
        $user = User::factory()->create(['name' => $name]);
        $user->assignRole('dosen');

        Identity::factory()->create(array_merge([
            'type' => 'dosen',
            'user_id' => $user->id,
            'scopus_id' => null,
            'sinta_id' => null,
            'wos_id' => null,
        ], $ids));

        return $user;
    }

    private function makeProposal(): Proposal
    {
        $detail = Research::factory()->create();
        $submitter = User::factory()->create(['name' => 'Pengusul IKU']);

        return Proposal::factory()->create([
            'submitter_id' => $submitter->id,
            'detailable_id' => $detail->id,
            'detailable_type' => Research::class,
            'status' => ProposalStatus::SUBMITTED,
            'start_year' => 2026,
            'semester' => 'ganjil',
        ]);
    }

    public function test_iku04_detail_opens_and_lists_dosen_without_recognition(): void
    {
        $this->makeDosen('Dosen Test');

        Livewire::test(IkuReport::class)
            ->set('period', '2026/2027')
            ->call('toggleDetails', 'IKU4')
            ->assertSet('selectedIku', 'IKU04')
            ->assertSee('Detail Capaian IKU04')
            ->assertSee('Dosen Test')
            ->assertSee('Belum punya ID');
    }

    public function test_iku06_detail_lists_unverified_outputs_with_status(): void
    {
        $proposal = $this->makeProposal();
        $report = ProgressReport::create([
            'proposal_id' => $proposal->id,
            'reporting_year' => 2026,
            'reporting_period' => 'annual',
            'status' => 'submitted',
        ]);

        $proposalOutput = ProposalOutput::factory()->create([
            'proposal_id' => $proposal->id,
        ]);

        MandatoryOutput::factory()->create([
            'progress_report_id' => $report->id,
            'proposal_output_id' => $proposalOutput->id,
            'article_title' => 'Artikel Belum Verifikasi',
            'rank' => 'Q1',
            'is_verified' => false,
        ]);

        Livewire::test(IkuReport::class)
            ->set('period', '2026/2027')
            ->call('toggleDetails', 'IKU06')
            ->assertSee('Artikel Belum Verifikasi')
            ->assertSee('Belum verifikasi')
            ->assertSee('Bobot belum dihitung');
    }

    public function test_iku08_detail_lists_unverified_policy_involvement(): void
    {
        $dosen = User::factory()->create(['name' => 'Dosen Kebijakan']);
        $dosen->assignRole('dosen');

        PolicyInvolvement::create([
            'user_id' => $dosen->id,
            'title' => 'Kebijakan Belum Verifikasi',
            'organization' => 'Lembaga Penelitian',
            'date' => now()->format('Y-m-d'),
            'status' => PolicyInvolvementStatus::PENDING,
        ]);

        Livewire::test(IkuReport::class)
            ->set('period', '2026/2027')
            ->call('toggleDetails', 'IKU08')
            ->assertSee('Dosen Kebijakan')
            ->assertSee('Belum ada rekognisi pakar terverifikasi');
    }

    public function test_only_pending_filter_hides_achieved_rows(): void
    {
        $this->makeDosen('Dosen Tanpa Identitas');
        $this->makeDosen('Dosen Punya Scopus', ['scopus_id' => '12345678']);

        Livewire::test(IkuReport::class)
            ->set('period', '2026/2027')
            ->call('toggleDetails', 'IKU04')
            ->assertSee('Dosen Tanpa Identitas')
            ->assertSee('Dosen Punya Scopus')
            ->set('onlyPending', true)
            ->assertSee('Hanya yang belum tercapai')
            ->assertSee('Dosen Tanpa Identitas')
            ->assertDontSee('Dosen Punya Scopus');
    }
}
