<?php

namespace Tests\Feature;

use App\Livewire\Settings\MasterData;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FakeSdgTest extends TestCase
{
    use RefreshDatabase;

    public function test_fake_sdg_page_loads()
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('admin lppm');

        $this->actingAs($user);
        session(['active_role' => 'admin lppm']);
        Livewire::test(MasterData::class, ['group' => 'academic-content', 'activeTab' => 'sdgs'])
            ->assertStatus(200);
    }
}
