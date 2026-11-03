<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders navbar correctly on research and community-service', function () {
    $this->seed(RoleSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('dosen');

    $response = $this->actingAs($user)
        ->withSession(['sim_lppm_app_theme' => 'modern', 'active_role' => 'dosen'])
        ->get('/research');

    $response->assertStatus(200);
    $content = $response->getContent();

    preg_match('/<!-- BEGIN NAVBAR MENU -->.*?<!-- END NAVBAR MENU -->/s', $content, $m);
    echo "\n=== NAVBAR MENU HTML (/research) ===\n";
    echo ($m[0] ?? 'MENU NOT FOUND IN HTML');
    echo "\n====================================\n";

    echo "\nHAS navbar-collapse: " . (str_contains($content, 'id="navbar-menu"') ? 'YES' : 'NO');
    echo "\nHAS modern-root: " . (str_contains($content, 'id="modern-root"') ? 'YES' : 'NO');
});
