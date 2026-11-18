<?php

namespace Tests\Feature\Settings;

use App\Livewire\Settings\Tabs\BudgetCapManager;
use App\Livewire\Settings\Tabs\BudgetComponentManager;
use App\Livewire\Settings\Tabs\BudgetGroupManager;
use App\Models\BudgetCap;
use App\Models\ResearchScheme;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Regression: BC-10 (Alpine dot-path watcher) + BC-02 (active role).
 *
 * Admin LPPM must be able to open the edit modal of a budget cap and persist
 * per-scheme caps. The previous client-side watcher used `$watch(`$wire.${name}`)`
 * which throws "SyntaxError: Unexpected number" for dot-path models such as
 * `research_scheme_caps.12`, aborting Alpine's directive queue and leaving the
 * modal read-only. The server side was always healthy, so these tests pin the
 * server contract while the source-contract test pins the JS fix.
 */
class BudgetCapManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function actingAsRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user);
        Session::put('active_role', $role);

        return $user;
    }

    private function makeCap(int $schemeId): BudgetCap
    {
        return BudgetCap::create([
            'year' => 2031,
            'semester' => 'ganjil',
            'research_budget_cap' => 50000000,
            'community_service_budget_cap' => 30000000,
            'scheme_caps' => [
                'research' => [$schemeId => 1234567],
                'community_service' => [],
            ],
            'enforce_percentage' => true,
            'amendment_tolerance_percent' => 10,
        ]);
    }

    public function test_admin_lppm_can_open_budget_cap_manager(): void
    {
        $this->actingAsRole('admin lppm');

        Livewire::test(BudgetCapManager::class)
            ->assertStatus(200)
            ->assertSee('Pengaturan Anggaran Tahunan');
    }

    public function test_edit_populates_per_scheme_caps_and_save_persists_them(): void
    {
        $this->actingAsRole('admin lppm');

        $scheme = ResearchScheme::factory()->create();
        $cap = $this->makeCap($scheme->id);

        // Livewire drives the same prop the Alpine money input watches.
        $component = Livewire::test(BudgetCapManager::class)
            ->call('edit', $cap->id)
            ->assertSet('editingId', $cap->id)
            ->assertSet('year', '2031')
            ->assertSet('research_budget_cap', '50000000')
            ->assertSet('research_scheme_caps', [$scheme->id => '1234567'])
            ->assertSet('amendment_tolerance_percent', '10');

        $component
            ->set('research_budget_cap', '60000000')
            ->set('research_scheme_caps.'.$scheme->id, '999999')
            ->set('amendment_tolerance_percent', '15')
            ->call('save')
            ->assertHasNoErrors();

        $cap->refresh();

        $this->assertSame(60000000.0, (float) $cap->research_budget_cap);
        $this->assertSame(15.0, (float) $cap->amendment_tolerance_percent);
        $this->assertSame(999999, (int) $cap->scheme_caps['research'][$scheme->id]);
    }

    public function test_duplicate_year_and_semester_is_rejected(): void
    {
        $this->actingAsRole('admin lppm');

        $scheme = ResearchScheme::factory()->create();
        $this->makeCap($scheme->id);

        Livewire::test(BudgetCapManager::class)
            ->call('create')
            ->set('year', '2031')
            ->set('semester', 'ganjil')
            ->set('research_budget_cap', '1000000')
            ->call('save')
            ->assertHasErrors('year');

        $this->assertSame(1, BudgetCap::where('year', 2031)->count());
    }

    public function test_per_scheme_money_inputs_use_dot_path_models(): void
    {
        $this->actingAsRole('admin lppm');

        $scheme = ResearchScheme::factory()->create();

        $html = Livewire::test(BudgetCapManager::class)->html();

        // The dot-path model is exactly what broke the old string watcher, so it
        // must stay covered by the source-contract test as well.
        $this->assertStringContainsString(
            "moneyInputSingle('research_scheme_caps.{$scheme->id}')",
            $html
        );
    }

    public function test_dosen_and_dekan_are_forbidden(): void
    {
        foreach (['dosen', 'dekan'] as $role) {
            $user = User::factory()->create();
            $user->assignRole($role);

            $this->actingAs($user);
            Session::put('active_role', $role);

            Livewire::test(BudgetCapManager::class)->assertStatus(403);
        }
    }

    public function test_superadmin_inherits_admin_lppm_access(): void
    {
        $this->actingAsRole('superadmin');

        Livewire::test(BudgetCapManager::class)->assertStatus(200);
    }

    public function test_group_and_component_managers_are_admin_only(): void
    {
        $this->actingAsRole('admin lppm');
        Livewire::test(BudgetGroupManager::class)->assertStatus(200);
        Livewire::test(BudgetComponentManager::class)->assertStatus(200);

        $dekan = User::factory()->create();
        $dekan->assignRole('dekan');
        $this->actingAs($dekan);
        Session::put('active_role', 'dekan');

        foreach ([BudgetGroupManager::class, BudgetComponentManager::class] as $component) {
            Livewire::test($component)->assertStatus(403);
        }
    }
}
