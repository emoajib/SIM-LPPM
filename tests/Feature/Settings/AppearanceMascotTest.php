<?php

namespace Tests\Feature\Settings;

use App\Livewire\Settings\Appearance;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AppearanceMascotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Storage::fake('public');
        Setting::clearCache();
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin lppm');

        return $user;
    }

    public function test_admin_can_upload_mascot_and_it_is_stored(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        session(['active_role' => 'admin lppm']);

        $file = UploadedFile::fake()->image('maskot.png', 200, 200);

        Livewire::test(Appearance::class)
            ->set('mascot', $file)
            ->call('saveMascot')
            ->assertHasNoErrors();

        $setting = Setting::where('key', 'dashboard_mascot')->first();
        $this->assertNotNull($setting);
        $this->assertNotNull($setting->getFirstMedia('mascot'));
    }

    public function test_admin_can_remove_mascot(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        session(['active_role' => 'admin lppm']);

        $file = UploadedFile::fake()->image('maskot.png', 200, 200);
        Livewire::test(Appearance::class)
            ->set('mascot', $file)
            ->call('saveMascot');

        $this->actingAs($admin);
        session(['active_role' => 'admin lppm']);

        Livewire::test(Appearance::class)
            ->call('removeMascot');

        $setting = Setting::where('key', 'dashboard_mascot')->first();
        $this->assertTrue($setting === null || $setting->getFirstMedia('mascot') === null);
    }

    public function test_non_admin_cannot_access_appearance(): void
    {
        $user = User::factory()->create();
        $user->assignRole('dosen');
        $this->actingAs($user);

        $this->get(route('settings.appearance'))
            ->assertForbidden();
    }

    public function test_invalid_file_is_rejected(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        session(['active_role' => 'admin lppm']);

        $file = UploadedFile::fake()->create('bad.txt', 10, 'text/plain');

        Livewire::test(Appearance::class)
            ->set('mascot', $file)
            ->call('saveMascot')
            ->assertHasErrors(['mascot']);
    }
}
