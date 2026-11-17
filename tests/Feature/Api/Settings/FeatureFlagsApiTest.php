<?php

// Vetted by SOSIOMEN - Manual Review Required by Senior Engineer/Manager

namespace Tests\Feature\Api\Settings;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeatureFlagsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /** @test */
    public function test_non_admin_cannot_view_feature_flags(): void
    {
        $user = User::factory()->create();
        $user->assignRole('dosen');

        $this->actingAs($user)->getJson('/api/v1/settings/feature-flags')->assertForbidden();
    }

    /** @test */
    public function test_admin_can_view_feature_flags(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin lppm');

        $response = $this->actingAs($admin)->getJson('/api/v1/settings/feature-flags');

        $response->assertOk()->assertJsonStructure([
            'feature_roadmap_active',
            'feature_kaprodi_validation',
            'module_persuratan_active',
            'surat_signature_mode',
            'surat_wet_signature_bypass',
            'feature_community_partner_required',
            'reviewer_count_required',
        ]);
    }

    /** @test */
    public function test_admin_can_update_feature_flags_and_menu_cache_is_busted(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin lppm');

        Setting::set('menu_cache_version', 5, 'integer');

        $response = $this->actingAs($admin)->putJson('/api/v1/settings/feature-flags', [
            'feature_roadmap_active' => true,
            'feature_kaprodi_validation' => true,
            'reviewer_count_required' => 2,
            'surat_signature_mode' => 'manual',
            'surat_wet_signature_bypass' => true,
        ]);

        $response->assertOk();

        $this->assertTrue((bool) Setting::get('feature_roadmap_active'));
        $this->assertTrue((bool) Setting::get('feature_kaprodi_validation'));
        $this->assertSame(2, (int) Setting::get('reviewer_count_required'));
        $this->assertSame('manual', Setting::get('surat_signature_mode'));
        $this->assertTrue((bool) Setting::get('surat_wet_signature_bypass'));

        $this->assertGreaterThan(5, (int) Setting::get('menu_cache_version'));
    }

    /** @test */
    public function test_switching_to_tte_resets_wet_signature_bypass(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin lppm');

        Setting::set('surat_wet_signature_bypass', true, 'boolean');

        $this->actingAs($admin)->putJson('/api/v1/settings/feature-flags', [
            'surat_signature_mode' => 'tte',
        ])->assertOk();

        $this->assertFalse((bool) Setting::get('surat_wet_signature_bypass'));
    }

    /** @test */
    public function test_reviewer_count_must_be_one_to_three(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin lppm');

        $this->actingAs($admin)->putJson('/api/v1/settings/feature-flags', [
            'reviewer_count_required' => 9,
        ])->assertUnprocessable()->assertJsonValidationErrors(['reviewer_count_required']);
    }

    /** @test */
    public function test_invalid_signature_mode_is_rejected(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin lppm');

        $this->actingAs($admin)->putJson('/api/v1/settings/feature-flags', [
            'surat_signature_mode' => 'unknown',
        ])->assertUnprocessable()->assertJsonValidationErrors(['surat_signature_mode']);
    }
}
