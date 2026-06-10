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
}
