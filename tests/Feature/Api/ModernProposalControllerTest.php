<?php

namespace Tests\Feature\Api;

use App\Enums\ProposalStatus;
use App\Models\CommunityService;
use App\Models\Faculty;
use App\Models\FocusArea;
use App\Models\Identity;
use App\Models\Institution;
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

class ModernProposalControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->seed(RoleSeeder::class);
        $this->seed(InstitutionSeeder::class);

        $institution = Institution::first();
        $this->faculty = Faculty::factory()->create([
            'name' => 'Fakultas Teknik',
            'institution_id' => $institution->id,
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

    protected function createProposal(array $overrides = []): Proposal
    {
        $research = Research::factory()->create();

        return Proposal::factory()->create(array_merge([
            'submitter_id' => $this->user->id,
            'detailable_id' => $research->id,
            'detailable_type' => Research::class,
            'status' => ProposalStatus::DRAFT,
            'research_scheme_id' => ResearchScheme::first()->id,
            'focus_area_id' => FocusArea::first()->id,
            'theme_id' => Theme::first()->id,
            'topic_id' => Topic::first()->id,
            'cluster_level1_id' => ScienceCluster::first()->id,
        ], $overrides));
    }

    public function test_authenticated_user_can_access_proposals_api()
    {
        $this->createProposal();

        $response = $this->actingAs($this->user)->getJson(route('research.proposal.api'));

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'meta'])
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 1)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_api_returns_expected_fields()
    {
        $proposal = $this->createProposal();

        $response = $this->actingAs($this->user)->getJson(route('research.proposal.api'));

        $response->assertStatus(200);
        $item = $response->json('data.0');

        $this->assertArrayHasKey('id', $item);
        $this->assertArrayHasKey('title', $item);
        $this->assertArrayHasKey('focus_area', $item);
        $this->assertArrayHasKey('submitter', $item);
        $this->assertArrayHasKey('identity_id', $item);
        $this->assertArrayHasKey('status', $item);
        $this->assertArrayHasKey('status_label', $item);
        $this->assertArrayHasKey('status_color', $item);
        $this->assertArrayHasKey('created_at', $item);
        $this->assertArrayHasKey('show_url', $item);

        $this->assertEquals($proposal->id, $item['id']);
        $this->assertEquals($proposal->title, $item['title']);
        $this->assertEquals('draft', $item['status']);
        $this->assertEquals('Draft', $item['status_label']);
    }

    public function test_show_url_points_to_blade_route()
    {
        $proposal = $this->createProposal();

        $response = $this->actingAs($this->user)->getJson(route('research.proposal.api'));

        $response->assertStatus(200);
        $this->assertStringContainsString(route('research.proposal.show', $proposal), $response->json('data.0.show_url'));
    }

    public function test_user_only_sees_own_proposals()
    {
        $this->createProposal();

        $otherUser = User::factory()->create(['name' => 'Dosen Lain']);
        $otherUser->assignRole('dosen');
        Identity::factory()->create([
            'user_id' => $otherUser->id,
            'faculty_id' => $this->faculty->id,
        ]);
        $this->createProposal(['submitter_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)->getJson(route('research.proposal.api'));

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_search_filter_works()
    {
        $this->createProposal(['title' => 'Sistem Rekomendasi Batik']);
        $this->createProposal(['title' => 'Analisis Persepsi Wisatawan']);

        $response = $this->actingAs($this->user)->getJson(route('research.proposal.api', ['search' => 'Batik']));

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Sistem Rekomendasi Batik');
    }

    public function test_status_filter_works()
    {
        $this->createProposal(['status' => ProposalStatus::DRAFT]);
        $this->createProposal(['status' => ProposalStatus::SUBMITTED]);

        $response = $this->actingAs($this->user)->getJson(route('research.proposal.api', ['status' => 'submitted']));

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'submitted');
    }

    public function test_type_filter_community_service()
    {
        $communityService = CommunityService::factory()->create();

        $proposal = $this->createProposal([
            'detailable_id' => $communityService->id,
            'detailable_type' => CommunityService::class,
            'research_scheme_id' => null,
        ]);

        // Filter pengabdian → proposal pengabdian muncul.
        $this->actingAs($this->user)
            ->getJson(route('research.proposal.api', ['type' => 'community-service']))
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $proposal->id);

        // Filter penelitian → proposal pengabdian tidak boleh muncul.
        $this->actingAs($this->user)
            ->getJson(route('research.proposal.api', ['type' => 'research']))
            ->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_role_filter_anggota_excludes_own_proposals()
    {
        // Usulan sebagai ketua (submitter) — juga tercatat di pivot dengan role ketua.
        $asKetua = $this->createProposal(['submitter_id' => $this->user->id]);
        $asKetua->teamMembers()->attach($this->user->id, ['role' => 'ketua', 'status' => 'accepted']);

        // Usulan orang lain di mana user ini anggota.
        $ketuaLain = User::factory()->create();
        $asAnggota = $this->createProposal(['submitter_id' => $ketuaLain->id]);
        $asAnggota->teamMembers()->attach($this->user->id, ['role' => 'anggota', 'status' => 'accepted']);

        $this->actingAs($this->user)
            ->getJson(route('research.proposal.api', ['role' => 'anggota']))
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $asAnggota->id);

        $this->actingAs($this->user)
            ->getJson(route('research.proposal.api', ['role' => 'ketua']))
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $asKetua->id);
    }

    public function test_unauthenticated_user_cannot_access_api()
    {
        $response = $this->getJson(route('research.proposal.api'));

        $response->assertStatus(401);
    }

    public function test_available_filters_returns_real_metadata_for_research()
    {
        $this->createProposal();

        $response = $this->actingAs($this->user)
            ->getJson(route('research.proposal.filters.v1', ['type' => 'research']));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'years',
                'schemes' => [['id', 'name']],
                'statuses' => [['value', 'label']],
                'faculties',
                'study_programs',
            ]);

        // Tahun diambil dari data real, bukan hardcoded.
        $this->assertContains((int) now()->year, $response->json('years'));

        // Status berasal dari enum ProposalStatus (bukan daftar hardcoded di JS).
        $values = collect($response->json('statuses'))->pluck('value')->all();
        $this->assertContains(ProposalStatus::DRAFT->value, $values);
        $this->assertContains(ProposalStatus::COMPLETED->value, $values);
    }

    public function test_available_filters_hides_faculty_and_prodi_for_regular_dosen()
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('research.proposal.filters.v1', ['type' => 'research']));

        $response->assertStatus(200)
            ->assertJsonCount(0, 'faculties')
            ->assertJsonCount(0, 'study_programs');
    }

    public function test_available_filters_exposes_faculties_for_admin_lppm()
    {
        $this->user->assignRole('admin lppm');

        $response = $this->actingAs($this->user)
            ->withSession(['active_role' => 'admin lppm'])
            ->getJson(route('research.proposal.filters.v1', ['type' => 'research']));

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('faculties'));
    }
}
