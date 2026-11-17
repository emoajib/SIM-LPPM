<?php

// Vetted by SOSIOMEN - Manual Review Required by Senior Engineer/Manager

namespace Tests\Feature\Api\Settings;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaptchaApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /** @test */
    public function test_non_admin_cannot_view_captcha_settings(): void
    {
        $user = User::factory()->create();
        $user->assignRole('dosen');

        $this->actingAs($user)->getJson('/api/v1/settings/captcha')->assertForbidden();
    }

    /** @test */
    public function test_admin_can_view_captcha_settings(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin lppm');

        $response = $this->actingAs($admin)->getJson('/api/v1/settings/captcha');

        $response->assertOk()->assertJsonStructure([
            'audio_enabled',
            'audio_voice',
            'voices',
        ]);
    }

    /** @test */
    public function test_admin_can_toggle_audio(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('superadmin');

        $this->actingAs($admin)->putJson('/api/v1/settings/captcha', [
            'audio_enabled' => false,
        ])->assertOk();

        $this->assertFalse((bool) Setting::get('captcha_audio_enabled'));
    }

    /** @test */
    public function test_unavailable_voice_is_rejected(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin lppm');

        $response = $this->actingAs($admin)->putJson('/api/v1/settings/captcha', [
            'audio_voice' => 'tidak-ada-voice-ini',
        ]);

        $response->assertUnprocessable();
    }
}
