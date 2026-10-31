<?php

namespace Tests\Feature;

use App\Enums\ProposalStatus;
use App\Livewire\Dashboard;
use App\Models\Proposal;
use App\Models\Research;
use App\Models\ReviewLog;
use App\Models\User;
use Database\Seeders\InstitutionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class DashboardScopingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(InstitutionSeeder::class);
    }

    public function test_institution_role_sees_all_proposals_while_dosen_sees_only_own(): void
    {
        $admin = User::factory()->create(['name' => 'Admin LPPM']);
        $admin->assignRole('admin lppm');

        $reviewer = User::factory()->create(['name' => 'Reviewer']);
        $reviewer->assignRole('reviewer');

        $dosenA = User::factory()->create(['name' => 'Dosen A']);
        $dosenA->assignRole('dosen');

        $dosenB = User::factory()->create(['name' => 'Dosen B']);
        $dosenB->assignRole('dosen');

        $researchA = Research::factory()->create();
        $proposalA = Proposal::factory()->create([
            'submitter_id' => $dosenA->id,
            'detailable_id' => $researchA->id,
            'detailable_type' => Research::class,
            'status' => ProposalStatus::APPROVED,
        ]);

        $researchB = Research::factory()->create();
        Proposal::factory()->create([
            'submitter_id' => $dosenB->id,
            'detailable_id' => $researchB->id,
            'detailable_type' => Research::class,
            'status' => ProposalStatus::SUBMITTED,
        ]);

        // Proposal A sudah melewati desk review (punya ReviewLog)
        $assignment = $proposalA->reviewers()->create(['user_id' => $reviewer->id]);
        ReviewLog::create([
            'proposal_reviewer_id' => $assignment->id,
            'proposal_id' => $proposalA->id,
            'user_id' => $reviewer->id,
            'round' => 1,
            'completed_at' => now(),
        ]);

        $adminStats = $this->statsFor($admin, 'admin lppm');
        $this->assertSame(2, $adminStats['total_usulan']);
        $this->assertSame(1, $adminStats['total_didanai']);
        $this->assertSame(1, $adminStats['total_desk_review']);
        $this->assertSame(2, $adminStats['scheme']['all']['usulan']);
        $this->assertSame(1, $adminStats['scheme']['penelitian']['didanai']);
        $this->assertSame(0, $adminStats['scheme']['pengabdian']['usulan']);

        $dosenStats = $this->statsFor($dosenA, 'dosen');
        $this->assertSame(1, $dosenStats['total_usulan']);
        $this->assertSame(1, $dosenStats['total_didanai']);
        $this->assertSame(1, $dosenStats['total_desk_review']);
    }

    public function test_superadmin_is_treated_as_institution_wide_role(): void
    {
        $superadmin = User::factory()->create(['name' => 'Superadmin']);
        $superadmin->assignRole('superadmin');

        $dosen = User::factory()->create(['name' => 'Dosen']);
        $dosen->assignRole('dosen');

        $research = Research::factory()->create();
        Proposal::factory()->create([
            'submitter_id' => $dosen->id,
            'detailable_id' => $research->id,
            'detailable_type' => Research::class,
            'status' => ProposalStatus::APPROVED,
        ]);

        $stats = $this->statsFor($superadmin, 'superadmin');

        $this->assertSame(1, $stats['total_usulan']);
    }

    public function test_combined_totals_include_research_and_community_service(): void
    {
        $admin = User::factory()->create(['name' => 'Admin LPPM']);
        $admin->assignRole('admin lppm');

        $dosen = User::factory()->create(['name' => 'Dosen']);
        $dosen->assignRole('dosen');

        $research = Research::factory()->create();
        Proposal::factory()->create([
            'submitter_id' => $dosen->id,
            'detailable_id' => $research->id,
            'detailable_type' => Research::class,
            'status' => ProposalStatus::APPROVED,
        ]);

        $communityService = Research::factory()->create();
        Proposal::factory()->create([
            'submitter_id' => $dosen->id,
            'detailable_id' => $communityService->id,
            'detailable_type' => 'App\Models\CommunityService',
            'status' => ProposalStatus::SUBMITTED,
        ]);

        $stats = $this->statsFor($admin, 'admin lppm');

        $this->assertSame(2, $stats['total_usulan']);
        $this->assertSame(1, $stats['total_didanai']);
        $this->assertSame(2, $stats['scheme']['all']['usulan']);
        $this->assertSame(1, $stats['scheme']['penelitian']['usulan']);
        $this->assertSame(1, $stats['scheme']['pengabdian']['usulan']);
        $this->assertSame(1, $stats['scheme']['pengabdian']['usulan'] - $stats['scheme']['pengabdian']['didanai']);
    }

    public function test_stats_are_broken_down_per_academic_year_and_semester(): void
    {
        $admin = User::factory()->create(['name' => 'Admin LPPM']);
        $admin->assignRole('admin lppm');

        $dosen = User::factory()->create(['name' => 'Dosen']);
        $dosen->assignRole('dosen');

        $ganjilBaru = Research::factory()->create();
        Proposal::factory()->create([
            'submitter_id' => $dosen->id,
            'detailable_id' => $ganjilBaru->id,
            'detailable_type' => Research::class,
            'status' => ProposalStatus::APPROVED,
            'start_year' => 2026,
            'semester' => 'ganjil',
        ]);

        $genapLama = Research::factory()->create();
        Proposal::factory()->create([
            'submitter_id' => $dosen->id,
            'detailable_id' => $genapLama->id,
            'detailable_type' => Research::class,
            'status' => ProposalStatus::SUBMITTED,
            'start_year' => 2026,
            'semester' => 'genap',
        ]);

        $ganjilDepan = Research::factory()->create();
        Proposal::factory()->create([
            'submitter_id' => $dosen->id,
            'detailable_id' => $ganjilDepan->id,
            'detailable_type' => Research::class,
            'status' => ProposalStatus::APPROVED,
            'start_year' => 2027,
            'semester' => 'ganjil',
        ]);

        $stats = $this->statsFor($admin, 'admin lppm');

        $this->assertSame(['2027/2028', '2026/2027', '2025/2026'], $stats['academic_years']);
        $this->assertCount(3, $stats['periods']);

        $byPeriod = collect($stats['periods'])->keyBy(
            fn (array $row) => $row['academic_year'].'|'.$row['semester']
        );

        $this->assertSame(1, $byPeriod['2026/2027|ganjil']['usulan_penelitian']);
        $this->assertSame(1, $byPeriod['2026/2027|ganjil']['didanai_penelitian']);

        // Genap 2026 jatuh pada tahun akademik 2025/2026
        $this->assertSame(1, $byPeriod['2025/2026|genap']['usulan_penelitian']);
        $this->assertSame(0, $byPeriod['2025/2026|genap']['didanai_penelitian']);

        $this->assertSame(1, $byPeriod['2027/2028|ganjil']['usulan_penelitian']);
        $this->assertSame(3, $stats['total_usulan']);
    }

    /**
     * @return array<string, mixed>
     */
    private function statsFor(User $user, string $role): array
    {
        session(['active_role' => $role]);

        $component = new Dashboard;
        $component->user = $user;

        $method = new ReflectionMethod($component, 'loadInfographicStats');
        $method->setAccessible(true);

        return $method->invoke($component);
    }
}
