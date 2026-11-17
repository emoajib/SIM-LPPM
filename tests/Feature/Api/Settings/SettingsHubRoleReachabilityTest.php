<?php

// Vetted by SOSIOMEN - Manual Review Required by Senior Engineer/Manager

namespace Tests\Feature\Api\Settings;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards the zero-gap promise: whatever the Settings Hub advertises to a role
 * must actually open. A link that 403/404/500s is a settings gap.
 */
class SettingsHubRoleReachabilityTest extends TestCase
{
    use RefreshDatabase;

    private const ROLES = [
        'dosen',
        'reviewer',
        'kaprodi',
        'dekan',
        'kepala lppm',
        'rektor',
        'admin lppm',
        'superadmin',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Setting::clearCache();
    }

    /** @test */
    public function test_every_role_hub_links_are_reachable_in_both_themes(): void
    {
        $problems = [];

        foreach (self::ROLES as $role) {
            foreach (['classic', 'modern'] as $theme) {
                $user = User::factory()->create();
                $user->assignRole($role);

                session(['active_role' => $role, 'sim_lppm_app_theme' => $theme]);

                $res = $this->actingAs($user)->getJson('/api/v1/settings/hub');

                if ($res->status() !== 200) {
                    $problems[] = "{$role}/{$theme} hub => HTTP {$res->status()}";

                    continue;
                }

                $items = collect($res->json('sections'))->flatMap(fn (array $s): array => $s['items']);

                foreach ($items as $item) {
                    $path = parse_url($item['href'], PHP_URL_PATH).'?'.(parse_url($item['href'], PHP_URL_QUERY) ?? '');
                    $status = $this->actingAs($user)->get($path)->getStatusCode();

                    if (in_array($status, [403, 404, 405, 500], true)) {
                        $problems[] = "{$role}/{$theme} :: {$item['key']} => {$path} = HTTP {$status}";
                    }
                }
            }
        }

        $this->assertSame([], $problems, "Hub links bermasalah:\n".implode("\n", $problems));
    }

    /** @test */
    public function test_dekan_and_kaprodi_only_see_master_data_when_roadmap_is_active(): void
    {
        Setting::set('feature_roadmap_active', false, 'boolean');

        foreach (['dekan', 'kaprodi'] as $role) {
            $user = User::factory()->create();
            $user->assignRole($role);

            session(['active_role' => $role]);

            $keys = $this->hubKeys($user);
            $this->assertNotContains('master-data', $keys, "{$role} tidak boleh melihat master-data saat roadmap nonaktif.");

            Setting::set('feature_roadmap_active', true, 'boolean');

            $keys = $this->hubKeys($user);
            $this->assertContains('master-data', $keys, "{$role} harus melihat master-data saat roadmap aktif.");

            Setting::set('feature_roadmap_active', false, 'boolean');
        }
    }

    /**
     * @return array<int, string>
     */
    private function hubKeys(User $user): array
    {
        $res = $this->actingAs($user)->getJson('/api/v1/settings/hub');
        $res->assertOk();

        return collect($res->json('sections'))
            ->flatMap(fn (array $s): array => array_column($s['items'], 'key'))
            ->all();
    }
}
