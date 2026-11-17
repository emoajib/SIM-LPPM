<?php

// Vetted by SOSIOMEN - Manual Review Required by Senior Engineer/Manager

namespace Tests\Feature\Api\Settings;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /** @test */
    public function test_guest_cannot_update_password(): void
    {
        $this->putJson('/api/v1/settings/password', [
            'current_password' => 'secret',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertUnauthorized();
    }

    /** @test */
    public function test_user_can_update_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('OldPassword123')]);
        $user->assignRole('dosen');

        $response = $this->actingAs($user)->putJson('/api/v1/settings/password', [
            'current_password' => 'OldPassword123',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertOk()->assertJson(['message' => 'Kata sandi berhasil diperbarui.']);

        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
    }

    /** @test */
    public function test_wrong_current_password_is_rejected(): void
    {
        $user = User::factory()->create(['password' => Hash::make('OldPassword123')]);
        $user->assignRole('dosen');

        $response = $this->actingAs($user)->putJson('/api/v1/settings/password', [
            'current_password' => 'WrongPassword',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['current_password']);

        $this->assertTrue(Hash::check('OldPassword123', $user->fresh()->password));
    }

    /** @test */
    public function test_password_confirmation_must_match(): void
    {
        $user = User::factory()->create(['password' => Hash::make('OldPassword123')]);
        $user->assignRole('dosen');

        $response = $this->actingAs($user)->putJson('/api/v1/settings/password', [
            'current_password' => 'OldPassword123',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'DifferentPassword123!',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['password']);
    }
}
