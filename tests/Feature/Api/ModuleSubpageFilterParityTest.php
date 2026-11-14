<?php

namespace Tests\Feature\Api;

use App\Enums\ProposalStatus;
use App\Enums\ReportStatus;
use App\Models\CommunityService;
use App\Models\Faculty;
use App\Models\FocusArea;
use App\Models\Identity;
use App\Models\Institution;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\Research;
use App\Models\ResearchScheme;
use App\Models\ScienceCluster;
use App\Models\Theme;
use App\Models\Topic;
use App\Models\User;
use Database\Seeders\InstitutionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Regression: BC-01 — paritas filter modern vs klasik untuk halaman sub-modul
 * (Perbaikan Usulan, Laporan Akhir, Realisasi Anggaran).
 *
 * Sebelum perbaikan, ModuleSubpageIndex hanya mengirim `search` sehingga dosen
 * kehilangan role tabs dan admin kehilangan filter status/tahun/fakultas.
 * Selain itu halaman pengabdian memanggil endpoint modul penelitian.
 */
class ModuleSubpageFilterParityTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Faculty $faculty;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->seed(RoleSeeder::class);
        $this->seed(InstitutionSeeder::class);

        $this->faculty = Faculty::factory()->create([
            'name' => 'Fakultas Teknik',
            'institution_id' => Institution::first()->id,
        ]);

        ResearchScheme::factory()->create();
        FocusArea::factory()->create();
        Theme::factory()->create();
        Topic::factory()->create();
        ScienceCluster::factory()->create(['level' => 1]);

        $this->user = User::factory()->create(['name' => 'Dosen Pengusul']);
        $this->user->assignRole('dosen');
        Identity::factory()->create([
            'user_id' => $this->user->id,
            'faculty_id' => $this->faculty->id,
        ]);
    }

    private function createProposal(array $overrides = []): Proposal
    {
        $research = Research::factory()->create();

        return Proposal::factory()->create(array_merge([
            'submitter_id' => $this->user->id,
            'detailable_id' => $research->id,
            'detailable_type' => Research::class,
            'status' => ProposalStatus::COMPLETED,
            'research_scheme_id' => ResearchScheme::first()->id,
            'focus_area_id' => FocusArea::first()->id,
            'theme_id' => Theme::first()->id,
            'topic_id' => Topic::first()->id,
            'cluster_level1_id' => ScienceCluster::first()->id,
        ], $overrides));
    }

    private function attachFinalReport(Proposal $proposal, ReportStatus $status): ProgressReport
    {
        return ProgressReport::create([
            'proposal_id' => $proposal->id,
            'reporting_year' => (int) now()->year,
            'reporting_period' => 'final',
            'status' => $status->value,
            'submitted_by' => $this->user->id,
        ]);
    }

    public function test_final_report_filter_belum_laporan_returns_proposals_without_final_report(): void
    {
        $without = $this->createProposal(['title' => 'Belum Lapor']);
        $with = $this->createProposal(['title' => 'Sudah Lapor']);
        $this->attachFinalReport($with, ReportStatus::APPROVED);

        $this->actingAs($this->user)
            ->getJson(route('research.proposal.api.v1', [
                'type' => 'research',
                'submodule' => 'final-report',
                'status' => 'belum_laporan',
            ]))
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $without->id);
    }

    public function test_final_report_filter_approved_returns_only_approved_reports(): void
    {
        $approved = $this->createProposal(['title' => 'Disahkan']);
        $this->attachFinalReport($approved, ReportStatus::APPROVED);

        $draft = $this->createProposal(['title' => 'Draft']);
        $this->attachFinalReport($draft, ReportStatus::DRAFT);

        $this->actingAs($this->user)
            ->getJson(route('research.proposal.api.v1', [
                'type' => 'research',
                'submodule' => 'final-report',
                'status' => 'approved',
            ]))
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $approved->id);
    }

    public function test_final_report_filter_submitted_groups_submitted_and_approved_by_dekan(): void
    {
        $submitted = $this->createProposal(['title' => 'Diajukan']);
        $this->attachFinalReport($submitted, ReportStatus::SUBMITTED);

        $byDekan = $this->createProposal(['title' => 'Disetujui Dekan']);
        $this->attachFinalReport($byDekan, ReportStatus::APPROVED_BY_DEKAN);

        $this->actingAs($this->user)
            ->getJson(route('research.proposal.api.v1', [
                'type' => 'research',
                'submodule' => 'final-report',
                'status' => 'submitted',
            ]))
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_final_report_meta_contains_status_statistics(): void
    {
        $approved = $this->createProposal(['title' => 'Disahkan']);
        $this->attachFinalReport($approved, ReportStatus::APPROVED);

        $submitted = $this->createProposal(['title' => 'Diajukan']);
        $this->attachFinalReport($submitted, ReportStatus::SUBMITTED);

        $this->createProposal(['title' => 'Belum Lapor']);

        $this->actingAs($this->user)
            ->getJson(route('research.proposal.api.v1', [
                'type' => 'research',
                'submodule' => 'final-report',
            ]))
            ->assertStatus(200)
            ->assertJsonPath('meta.stats.total', 3)
            ->assertJsonPath('meta.stats.approved', 1)
            ->assertJsonPath('meta.stats.in_review', 1)
            ->assertJsonPath('meta.stats.belum_laporan', 1);
    }

    public function test_final_report_payload_exposes_report_status(): void
    {
        $proposal = $this->createProposal();
        $this->attachFinalReport($proposal, ReportStatus::APPROVED_BY_DEKAN);

        $this->actingAs($this->user)
            ->getJson(route('research.proposal.api.v1', [
                'type' => 'research',
                'submodule' => 'final-report',
            ]))
            ->assertStatus(200)
            ->assertJsonPath('data.0.report_status', ReportStatus::APPROVED_BY_DEKAN->value)
            ->assertJsonPath('data.0.report_status_label', ReportStatus::APPROVED_BY_DEKAN->label());
    }

    public function test_academic_year_filter_matches_start_year_and_semester(): void
    {
        $ganjil = $this->createProposal([
            'title' => 'Ganjil 2024',
            'start_year' => 2024,
            'semester' => 'ganjil',
        ]);
        $genap = $this->createProposal([
            'title' => 'Genap 2025',
            'start_year' => 2025,
            'semester' => 'genap',
        ]);
        $this->createProposal([
            'title' => 'Ganjil 2023',
            'start_year' => 2023,
            'semester' => 'ganjil',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('research.proposal.api.v1', [
                'type' => 'research',
                'academic_year' => '2024/2025',
            ]))
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($ganjil->id, $ids);
        $this->assertContains($genap->id, $ids);
    }

    public function test_available_filters_exposes_academic_years_and_report_statuses(): void
    {
        $this->createProposal(['start_year' => 2024, 'semester' => 'ganjil']);

        $this->actingAs($this->user)
            ->getJson(route('research.proposal.filters.v1', ['type' => 'research']))
            ->assertStatus(200)
            ->assertJsonStructure(['academic_years', 'report_statuses' => [['value', 'label']]]);

        $reportValues = collect($this->getJson(route('research.proposal.filters.v1', ['type' => 'research']))->json('report_statuses'))
            ->pluck('value')
            ->all();

        $this->assertContains('belum_laporan', $reportValues);
        $this->assertContains(ReportStatus::APPROVED->value, $reportValues);
    }

    public function test_community_service_has_its_own_index_and_filters_endpoint(): void
    {
        $communityService = CommunityService::factory()->create();
        $proposal = $this->createProposal([
            'detailable_id' => $communityService->id,
            'detailable_type' => CommunityService::class,
            'research_scheme_id' => null,
        ]);

        $this->actingAs($this->user)
            ->getJson(route('community-service.community.proposal.api.v1', ['type' => 'community-service']))
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $proposal->id);

        $this->actingAs($this->user)
            ->getJson(route('community-service.community.proposal.filters.v1', ['type' => 'community-service']))
            ->assertStatus(200)
            ->assertJsonStructure(['schemes', 'statuses', 'report_statuses']);
    }
}
