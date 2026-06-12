<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TempDashboardHtmlTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_html()
    {
        $this->seed(RoleSeeder::class);
        $admin = User::whereHas('roles', function ($q) {
            $q->where('name', 'admin lppm');
        })->first();
        if (! $admin) {
            $admin = User::factory()->create();
            $admin->assignRole('admin lppm');
        }
        $response = $this->actingAs($admin)->get('/dashboard');
        $html = $response->content();

        // Find the x-init for the trend chart
        preg_match('/x-init="\$nextTick\(\(\) => \$data\.renderTrendChart\((.*?)\)\)"/s', $html, $matches);

        echo "INIT SCRIPT:\n";
        echo $matches[1] ?? 'NOT FOUND';
        echo "\n\n";
    }
}
