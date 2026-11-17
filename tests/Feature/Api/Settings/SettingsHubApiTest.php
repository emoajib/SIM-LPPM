<?php

// Vetted by SOSIOMEN - Manual Review Required by Senior Engineer/Manager

namespace Tests\Feature\Api\Settings;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsHubApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /** @test */
    public function test_guest_cannot_view_settings_hub(): void
    {
        $this->getJson('/api/v1/settings/hub')->assertUnauthorized();
    }

    /** @test */
    public function test_dosen_sees_account_settings_only(): void
    {
        $user = User::factory()->create();
        $user->assignRole('dosen');

        $response = $this->actingAs($user)->getJson('/api/v1/settings/hub');

        $response->assertOk()->assertJsonStructure(['sections']);

        $keys = collect($response->json('sections'))
            ->flatMap(fn (array $section): array => array_column($section['items'], 'key'))
            ->all();

        $this->assertContains('profile', $keys);
        $this->assertContains('password', $keys);
        $this->assertNotContains('notification-config', $keys);
        $this->assertNotContains('feature-flags', $keys);
        $this->assertNotContains('master-data', $keys);
    }

    /** @test */
    public function test_admin_lppm_sees_system_and_master_data_settings(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin lppm');

        $response = $this->actingAs($admin)->getJson('/api/v1/settings/hub');

        $response->assertOk();

        $keys = collect($response->json('sections'))
            ->flatMap(fn (array $section): array => array_column($section['items'], 'key'))
            ->all();

        foreach (['profile', 'password', 'notification-config', 'feature-flags', 'captcha', 'master-data', 'proposal-schedule'] as $expected) {
            $this->assertContains($expected, $keys, "Hub harus memuat item '{$expected}' untuk admin LPPM.");
        }
    }

    /** @test */
    public function test_every_hub_item_has_a_reachable_href(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('superadmin');

        $response = $this->actingAs($admin)->getJson('/api/v1/settings/hub');

        $response->assertOk();

        $items = collect($response->json('sections'))->flatMap(fn (array $section): array => $section['items']);

        $this->assertNotEmpty($items);

        foreach ($items as $item) {
            $this->assertNotEmpty($item['href'], "Item '{$item['key']}' tidak punya href.");
            $path = parse_url($item['href'], PHP_URL_PATH);
            $this->assertIsString($path, "Item '{$item['key']}' href tidak valid.");
            $this->assertStringStartsWith('/', $path, "Item '{$item['key']}' href harus berupa path absolut.");
        }
    }

    /** @test */
    public function test_classic_tab_items_deep_link_to_their_tab(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('superadmin');

        $response = $this->actingAs($admin)->getJson('/api/v1/settings/hub');

        $response->assertOk();

        $items = collect($response->json('sections'))
            ->flatMap(fn (array $section): array => $section['items'])
            ->keyBy('key');

        foreach (['pdf-export', 'tkt-manager', 'sync', 'backup', 'restore'] as $key) {
            $this->assertTrue($items->has($key), "Item '{$key}' harus ada di hub untuk superadmin.");

            $query = [];
            parse_str((string) parse_url($items[$key]['href'], PHP_URL_QUERY), $query);

            $this->assertSame($key, $query['tab'] ?? null, "Item '{$key}' harus deep-link ke ?tab={$key}.");
        }
    }

    /** @test */
    public function test_superadmin_sees_the_complete_settings_surface(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('superadmin');

        $response = $this->actingAs($admin)->getJson('/api/v1/settings/hub');

        $response->assertOk();

        $keys = collect($response->json('sections'))
            ->flatMap(fn (array $section): array => array_column($section['items'], 'key'))
            ->all();

        // Every settings area must be reachable for a superadmin. If a route name
        // in the hub is wrong, the item is silently dropped and this fails.
        $expected = [
            'profile',
            'password',
            'security',
            'notification-config',
            'feature-flags',
            'captcha',
            'appearance',
            'pdf-export',
            'tkt-manager',
            'audit',
            'sync',
            'backup',
            'restore',
            'master-data',
            'proposal-schedule',
            'proposal-template',
            'contracts',
            'manual-books',
        ];

        foreach ($expected as $key) {
            $this->assertContains($key, $keys, "Superadmin harus melihat item pengaturan '{$key}'.");
        }
    }

    /** @test */
    public function test_hub_exposes_no_admin_items_to_dosen(): void
    {
        $user = User::factory()->create();
        $user->assignRole('dosen');

        $response = $this->actingAs($user)->getJson('/api/v1/settings/hub');

        $response->assertOk();

        $keys = collect($response->json('sections'))
            ->flatMap(fn (array $section): array => array_column($section['items'], 'key'))
            ->all();

        foreach (['pdf-export', 'tkt-manager', 'sync', 'backup', 'restore', 'audit', 'appearance', 'contracts', 'manual-books'] as $forbidden) {
            $this->assertNotContains($forbidden, $keys, "Item admin '{$forbidden}' tidak boleh muncul untuk dosen.");
        }
    }
}
