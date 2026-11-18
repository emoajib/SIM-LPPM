<?php

namespace Tests\Feature\Api;

use App\Models\CommunityService;
use App\Models\Proposal;
use App\Models\Research;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Regression: BC-03 — UI ↔ API drift pada dashboard modern.
 *
 * Dashboard sebelumnya memaksa `type=research` ketika filter "Semua Tipe",
 * sehingga data pengabdian tidak pernah tampil. API sendiri juga tidak
 * mengenal `type=all` (nilai selain "research" jatuh ke CommunityService),
 * jadi kontrak "all" harus dikunci di sini.
 */
class ModernProposalAllTypeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RoleSeeder::class);
    }

    public function test_type_all_returns_research_and_community_service_rows(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin lppm');
        $this->actingAs($admin);
        session(['active_role' => 'admin lppm']);

        $research = Research::factory()->create();
        $researchProposal = Proposal::factory()->create([
            'submitter_id' => $admin->id,
            'detailable_type' => Research::class,
            'detailable_id' => $research->id,
        ]);

        $community = CommunityService::factory()->create();
        $communityProposal = Proposal::factory()->create([
            'submitter_id' => $admin->id,
            'detailable_type' => CommunityService::class,
            'detailable_id' => $community->id,
            'community_service_scheme_id' => null,
        ]);

        $response = $this->getJson(route('research.proposal.api.v1', ['type' => 'all']));

        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertContains($researchProposal->id, $ids);
        $this->assertContains($communityProposal->id, $ids);
        $this->assertSame(2, $response->json('meta.total'));
        $this->assertSame(15, $response->json('meta.per_page'));

        $rows = collect($response->json('data'))->keyBy('id');

        // BC-03: show_url must follow the row's own module, not the request filter.
        $this->assertStringContainsString('/research/proposal/', $rows[$researchProposal->id]['show_url']);
        $this->assertStringContainsString('/community-service/proposal/', $rows[$communityProposal->id]['show_url']);
    }

    public function test_type_research_still_excludes_community_service_rows(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin lppm');
        $this->actingAs($admin);
        session(['active_role' => 'admin lppm']);

        $research = Research::factory()->create();
        $researchProposal = Proposal::factory()->create([
            'submitter_id' => $admin->id,
            'detailable_type' => Research::class,
            'detailable_id' => $research->id,
        ]);

        $community = CommunityService::factory()->create();
        $communityProposal = Proposal::factory()->create([
            'submitter_id' => $admin->id,
            'detailable_type' => CommunityService::class,
            'detailable_id' => $community->id,
            'community_service_scheme_id' => null,
        ]);

        $response = $this->getJson(route('research.proposal.api.v1', ['type' => 'research']));

        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertContains($researchProposal->id, $ids);
        $this->assertNotContains($communityProposal->id, $ids);
        $this->assertSame(1, $response->json('meta.total'));
    }
}
