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
        $this->createProposal(['detailable_type' => CommunityService::class]);

        $response = $this->actingAs($this->user)->getJson(route('research.proposal.api', ['type' => 'community-service']));

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_unauthenticated_user_cannot_access_api()
    {
        $response = $this->getJson(route('research.proposal.api'));

        $response->assertStatus(401);
    }
}
