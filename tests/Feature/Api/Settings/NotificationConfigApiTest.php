<?php

// Vetted by SOSIOMEN - Manual Review Required by Senior Engineer/Manager

namespace Tests\Feature\Api\Settings;

use App\Models\NotificationSetting;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationConfigApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /** @test */
    public function test_admin_lppm_can_view_notification_config(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin lppm');

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/settings/notification');

        $response->assertOk()
            ->assertJsonStructure([
                'smtp' => ['host', 'port', 'username', 'encryption', 'from_address', 'from_name'],
                'whatsapp' => ['api_url', 'sender_id'],
            ]);
    }

    /** @test */
    public function test_superadmin_can_view_notification_config(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');

        $response = $this->actingAs($superadmin)
            ->getJson('/api/v1/settings/notification');

        $response->assertOk();
    }

    /** @test */
    public function test_non_admin_cannot_view_notification_config(): void
    {
        $user = User::factory()->create();
        $user->assignRole('dosen');

        $response = $this->actingAs($user)
            ->getJson('/api/v1/settings/notification');

        $response->assertForbidden();
    }

    /** @test */
    public function test_guest_cannot_view_notification_config(): void
    {
        $response = $this->getJson('/api/v1/settings/notification');
        $response->assertUnauthorized();
    }

    /** @test */
    public function test_admin_can_update_notification_config(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin lppm');

        $payload = [
            'smtp' => [
                'host' => 'smtp.test.com',
                'port' => 587,
                'username' => 'test@test.com',
                'password' => 'secret123',
                'encryption' => 'tls',
                'from_address' => 'noreply@test.com',
                'from_name' => 'Test App',
            ],
            'whatsapp' => [
                'api_url' => 'https://api.test.com/send',
                'api_key' => 'test-key',
                'sender_id' => '628123456789',
            ],
        ];

        $response = $this->actingAs($admin)
            ->putJson('/api/v1/settings/notification', $payload);

        $response->assertOk()
            ->assertJson(['message' => 'Konfigurasi notifikasi disimpan.']);

        // Verify settings saved in DB
        $this->assertEquals('smtp.test.com', NotificationSetting::get('MAIL_HOST'));
        $this->assertEquals('587', NotificationSetting::get('MAIL_PORT'));
        $this->assertEquals('secret123', NotificationSetting::get('MAIL_PASSWORD'));
        $this->assertEquals('https://api.test.com/send', NotificationSetting::get('WHATSAPP_API_URL'));
        $this->assertEquals('test-key', NotificationSetting::get('WHATSAPP_API_KEY'));
    }

    /** @test */
    public function test_empty_password_does_not_overwrite_existing(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin lppm');

        // Set initial password
        NotificationSetting::set('MAIL_PASSWORD', 'original-password', true);

        // Update without password
        $payload = [
            'smtp' => [
                'host' => 'smtp.test.com',
                'port' => 587,
                'username' => 'test@test.com',
                'password' => '',
                'encryption' => 'tls',
                'from_address' => 'noreply@test.com',
                'from_name' => 'Test App',
            ],
            'whatsapp' => [],
        ];

        $response = $this->actingAs($admin)
            ->putJson('/api/v1/settings/notification', $payload);

        $response->assertOk();

        // Original password should remain
        $this->assertEquals('original-password', NotificationSetting::get('MAIL_PASSWORD'));
    }

    /** @test */
    public function test_email_requires_valid_email(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin lppm');

        $response = $this->actingAs($admin)
            ->postJson('/api/v1/settings/notification/test-email', ['email' => 'invalid']);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function test_whatsapp_requires_valid_phone_format(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin lppm');

        $response = $this->actingAs($admin)
            ->postJson('/api/v1/settings/notification/test-whatsapp', ['phone' => '08123456789']);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['phone']);
    }

    /** @test */
    public function test_whatsapp_accepts_62_format(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin lppm');

        $response = $this->actingAs($admin)
            ->postJson('/api/v1/settings/notification/test-whatsapp', ['phone' => '628123456789']);

        // May fail due to no API config, but validation should pass (not 422)
        $this->assertNotEquals(422, $response->status());
    }
}
