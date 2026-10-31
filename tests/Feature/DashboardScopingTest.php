<?php

namespace Tests\Feature;

use App\Enums\ProposalStatus;
use App\Livewire\Dashboard;
use App\Models\Proposal;
use App\Models\Research;
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

        $dosenA = User::factory()->create(['name' => 'Dosen A']);
        $dosenA->assignRole('dosen');

        $dosenB = User::factory()->create(['name' => 'Dosen B']);
        $dosenB->assignRole('dosen');

        $researchA = Research::factory()->create();
        Proposal::factory()->create([
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

        $adminStats = $this->statsFor($admin, 'admin lppm');
        $this->assertSame(2, $adminStats['total_usulan']);
        $this->assertSame(1, $adminStats['total_didanai']);
        $this->assertSame(1, $adminStats['total_desk_review']);

        $dosenStats = $this->statsFor($dosenA, 'dosen');
        $this->assertSame(1, $dosenStats['total_usulan']);
        $this->assertSame(1, $dosenStats['total_didanai']);
        $this->assertSame(0, $dosenStats['total_desk_review']);
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

    /**
     * @return array<string, int|float>
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
