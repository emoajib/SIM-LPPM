<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DashboardMascotRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Storage::fake('public');
        Setting::clearCache();
    }

    public function test_dashboard_renders_default_svg_when_no_mascot_uploaded(): void
    {
        $admin = User::factory()->create(['name' => 'Admin LPPM']);
        $admin->assignRole('admin lppm');

        session(['active_role' => 'admin lppm']);
        session(['sim_lppm_app_theme' => 'modern']);
        session(['sim_lppm_dashboard_theme' => 'modern']);
        $this->actingAs($admin);

        $response = $this->get('/dashboard');
        $response->assertOk();
        $response->assertSee('M10,20 L12,23', false);
    }

    public function test_dashboard_renders_uploaded_mascot_image(): void
    {
        $admin = User::factory()->create(['name' => 'Admin LPPM']);
        $admin->assignRole('admin lppm');

        // Simpan media langsung ke Setting
        $setting = Setting::firstOrCreate(
            ['key' => 'dashboard_mascot'],
            ['value' => 'mascot', 'type' => 'image']
        );
        $file = UploadedFile::fake()->image('maskot.png', 200, 200);
        $setting->addMedia($file->getRealPath())
            ->usingName($file->getClientOriginalName())
            ->setFileName($file->getClientOriginalName())
            ->toMediaCollection('mascot');

        session(['active_role' => 'admin lppm']);
        session(['sim_lppm_app_theme' => 'modern']);
        session(['sim_lppm_dashboard_theme' => 'modern']);
        $this->actingAs($admin);

        $response = $this->get('/dashboard');
        $response->assertOk();
        $response->assertDontSee('M10,20 L12,23', false);
    }

    public function test_non_admin_does_not_see_mascot_shortcut_button(): void
    {
        $user = User::factory()->create(['name' => 'Dosen Pengusul']);
        $user->assignRole('dosen');

        session(['active_role' => 'dosen']);
        session(['sim_lppm_app_theme' => 'modern']);
        session(['sim_lppm_dashboard_theme' => 'modern']);
        $this->actingAs($user);

        $response = $this->get('/dashboard');
        $response->assertOk();
        $response->assertDontSee('Atur Maskot', false);
    }
}
