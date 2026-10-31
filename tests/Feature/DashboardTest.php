<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    // Vetted by AI - Manual Review Required by Senior Engineer/Manager
    public function test_dashboard_screen_can_be_rendered(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::role('superadmin')->first();
        if (! $user) {
            $user = User::factory()->create();
            $user->assignRole('superadmin');
        }

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
    }

    // Vetted by AI - Manual Review Required by Senior Engineer/Manager
    public function test_modern_dashboard_does_not_render_hardcoded_mock_metrics(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::role('superadmin')->first();
        if (! $user) {
            $user = User::factory()->create();
            $user->assignRole('superadmin');
        }

        $response = $this->actingAs($user)
            ->withSession(['sim_lppm_app_theme' => 'modern'])
            ->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Data kemajuan belum tersedia');

        // Nilai mock lama tidak boleh muncul lagi.
        $response->assertDontSee('Tingkat Kelolosan: 70.4%');
        $response->assertDontSee('54 Proposal');
        $response->assertDontSee('Rp 1.080 Jt');
        $response->assertDontSee('72%');
        $response->assertDontSee('85%');
    }
}
