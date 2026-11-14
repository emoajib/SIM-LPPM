<?php

namespace Tests\Feature\Api;

use App\Enums\ProposalStatus;
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

/**
 * Regression: BC-03 — kontrak payload React <-> API.
 *
 * Komponen ProposalShowIndex.jsx membaca field `proposal.<key>`. Kalau controller
 * berhenti mengirim salah satu field, halaman modern jadi kosong/undefined tanpa
 * error server. Test ini membandingkan field yang dipakai UI dengan payload nyata.
 */
class ModernProposalShowContractTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RoleSeeder::class);
        $this->seed(InstitutionSeeder::class);

        $faculty = Faculty::factory()->create(['institution_id' => Institution::first()->id]);

        ResearchScheme::factory()->create();
        FocusArea::factory()->create();
        Theme::factory()->create();
        Topic::factory()->create();
        ScienceCluster::factory()->create(['level' => 1]);

        $this->user = User::factory()->create();
        $this->user->assignRole('dosen');
        Identity::factory()->create([
            'user_id' => $this->user->id,
            'faculty_id' => $faculty->id,
        ]);
    }

    public function test_show_payload_contains_every_field_used_by_the_react_detail_page(): void
    {
        $research = Research::factory()->create();
        $proposal = Proposal::factory()->create([
            'submitter_id' => $this->user->id,
            'detailable_id' => $research->id,
            'detailable_type' => Research::class,
            'status' => ProposalStatus::DRAFT,
            'research_scheme_id' => ResearchScheme::first()->id,
            'focus_area_id' => FocusArea::first()->id,
            'theme_id' => Theme::first()->id,
            'topic_id' => Topic::first()->id,
            'cluster_level1_id' => ScienceCluster::first()->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('research.proposal.show.v1', $proposal))
            ->assertStatus(200);

        $payload = $response->json();
        $usedFields = $this->fieldsUsedByReactDetailPage();

        expect($usedFields)->not->toBeEmpty();

        $missing = array_values(array_diff($usedFields, array_keys($payload)));

        $this->assertSame([], $missing, 'Field dipakai React tapi tidak ada di payload API: '.implode(', ', $missing));
    }

    /**
     * @return array<int, string>
     */
    private function fieldsUsedByReactDetailPage(): array
    {
        $source = (string) file_get_contents(resource_path('js/modern/ProposalShowIndex.jsx'));

        preg_match_all('/\bproposal\.([a-z][a-z0-9_]*)/', $source, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }
}
