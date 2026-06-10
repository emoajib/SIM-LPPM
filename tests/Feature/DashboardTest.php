<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    public function test_dashboard_screen_can_be_rendered(): void
    {
        $user = User::role('superadmin')->first();
        if (!$user) {
            $user = User::factory()->create();
            $user->assignRole('superadmin');
        }
        
        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
    }
}
