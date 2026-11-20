<?php

// Vetted by SOSIOMEN - Manual Review Required by Senior Engineer/Manager

namespace Tests\Feature\Api\Settings;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProfileApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /** @test */
    public function test_view_profile(): void
    {
        $user = User::factory()->create();
        $user->assignRole('dosen');

        $response = $this->actingAs($user)
            ->getJson('/api/v1/settings/profile');

        $response->assertOk()
            ->assertJsonStructure([
                'user' => ['name', 'email', 'username', 'whatsapp_number', 'photo_url'],
                'identity',
            ]);
    }

    /** @test */
    public function test_view_profile_photo_url_is_string_or_null_not_attribute_object(): void
    {
        $user = User::factory()->create();
        $user->assignRole('dosen');

        $response = $this->actingAs($user)
            ->getJson('/api/v1/settings/profile');

        $response->assertOk();
        $photoUrl = $response->json('user.photo_url');
        $this->assertTrue($photoUrl === null || is_string($photoUrl), 'photo_url harus string atau null, bukan objek Attribute.');
    }

    /** @test */
    public function test_guest_cannot_view_profile(): void
    {
        $response = $this->getJson('/api/v1/settings/profile');
        $response->assertUnauthorized();
    }

    /** @test */
    public function test_update_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@test.com',
        ]);
        $user->assignRole('dosen');

        $payload = [
            'user' => [
                'name' => 'New Name',
                'email' => 'new@test.com',
                'whatsapp_number' => '628123456789',
            ],
            'identity' => [
                'identity_id' => '1234567890',
                'type' => 'dosen',
                'title_prefix' => 'Dr.',
                'title_suffix' => 'M.Kom',
                'sinta_id' => '123456',
                'scopus_id' => '7001234567',
                'google_scholar_id' => 'abc123',
                'wos_id' => 'A-1234-2020',
                'gender' => 'L',
                'address' => 'Jl. Test No. 123',
                'birthdate' => '1980-01-01',
                'birthplace' => 'Jakarta',
                'last_education' => 'S3',
                'functional_position' => 'Lektor Kepala',
            ],
        ];

        $response = $this->actingAs($user)
            ->putJson('/api/v1/settings/profile', $payload);

        $response->assertOk()
            ->assertJson(['message' => 'Profil diperbarui.']);

        $user->refresh();
        $this->assertEquals('New Name', $user->name);
        $this->assertEquals('new@test.com', $user->email);
        $this->assertEquals('628123456789', $user->whatsapp_number);

        $identity = $user->identity;
        $this->assertNotNull($identity);
        $this->assertEquals('1234567890', $identity->identity_id);
        $this->assertEquals('Dr.', $identity->title_prefix);
        $this->assertEquals('M.Kom', $identity->title_suffix);
        $this->assertEquals('123456', $identity->sinta_id);
    }

    /** @test */
    public function test_cannot_update_to_duplicate_email(): void
    {
        $user1 = User::factory()->create(['email' => 'user1@test.com']);
        $user1->assignRole('dosen');

        $user2 = User::factory()->create(['email' => 'user2@test.com']);
        $user2->assignRole('dosen');

        $response = $this->actingAs($user1)
            ->putJson('/api/v1/settings/profile', [
                'user' => ['name' => 'User 1', 'email' => 'user2@test.com'],
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['user.email']);
    }

    /** @test */
    public function test_whatsapp_number_must_be_valid_format(): void
    {
        $user = User::factory()->create();
        $user->assignRole('dosen');

        $response = $this->actingAs($user)
            ->putJson('/api/v1/settings/profile', [
                'user' => ['name' => 'Test', 'email' => 'test@test.com', 'whatsapp_number' => '08123456789'],
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['user.whatsapp_number']);
    }

    /** @test */
    public function test_user_can_upload_photo(): void
    {
        $user = User::factory()->create();
        $user->assignRole('dosen');

        $file = UploadedFile::fake()->image('avatar.jpg', 200, 200);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/settings/profile/photo', [
                'photo' => $file,
            ]);

        $response->assertOk()
            ->assertJsonStructure(['message', 'photo_url']);

        $user->refresh();
        $this->assertTrue($user->hasMedia('avatar'));
    }

    /** @test */
    public function test_user_can_delete_photo(): void
    {
        $user = User::factory()->create();
        $user->assignRole('dosen');

        // First upload a photo
        $file = UploadedFile::fake()->image('avatar.jpg', 200, 200);
        $user->addMedia($file->getRealPath())
            ->usingFileName('avatar.jpg')
            ->toMediaCollection('avatar');

        $this->assertTrue($user->hasMedia('avatar'));

        $response = $this->actingAs($user)
            ->deleteJson('/api/v1/settings/profile/photo');

        $response->assertOk()
            ->assertJson(['message' => 'Foto profil dihapus.']);

        $user->refresh();
        $this->assertFalse($user->hasMedia('avatar'));
    }

    /** @test */
    public function test_identity_faculty_must_exist(): void
    {
        $user = User::factory()->create();
        $user->assignRole('dosen');

        $response = $this->actingAs($user)
            ->putJson('/api/v1/settings/profile', [
                'identity' => ['faculty_id' => 99999],
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['identity.faculty_id']);
    }

    /** @test */
    public function test_identity_study_program_must_exist(): void
    {
        $user = User::factory()->create();
        $user->assignRole('dosen');

        $response = $this->actingAs($user)
            ->putJson('/api/v1/settings/profile', [
                'identity' => ['study_program_id' => 99999],
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['identity.study_program_id']);
    }
}
