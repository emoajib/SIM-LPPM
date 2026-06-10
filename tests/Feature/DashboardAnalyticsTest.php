<?php

// Vetted by AI - Manual Review Required by Senior Engineer/Manager

namespace Tests\Feature;

use App\Livewire\Dashboard\AdminDashboard;
use App\Livewire\Dashboard\ExecDashboard;
use App\Livewire\Dashboard\KepalaLppmDashboard;
use App\Models\StudyProgram;
use App\Models\User;
use Database\Seeders\InstitutionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminLppm;

    protected User $kepalaLppm;

    protected User $rektor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(InstitutionSeeder::class);

        $this->adminLppm = User::factory()->create(['name' => 'Admin LPPM']);
        $this->adminLppm->assignRole('admin lppm');

        $this->kepalaLppm = User::factory()->create(['name' => 'Kepala LPPM']);
        $this->kepalaLppm->assignRole('kepala lppm');

        $this->rektor = User::factory()->create(['name' => 'Rektor']);
        $this->rektor->assignRole('rektor');
    }

    public function test_admin_dashboard_loads_analytics()
    {
        $this->actingAs($this->adminLppm);
        Session::put('active_role', 'admin lppm');

        $component = Livewire::test(AdminDashboard::class);
        $component->assertStatus(200);
        $this->assertTrue(is_array($component->get('focusAreasChartData')));
        $this->assertTrue(is_array($component->get('facultyPerformanceChartData')));
    }

    public function test_kepala_lppm_dashboard_loads_analytics()
    {
        $this->actingAs($this->kepalaLppm);
        Session::put('active_role', 'kepala lppm');

        $component = Livewire::test(KepalaLppmDashboard::class);
        $component->assertStatus(200);
        $this->assertTrue(is_array($component->get('focusAreasChartData')));
        $this->assertTrue(is_array($component->get('facultyPerformanceChartData')));
    }

    public function test_exec_dashboard_loads_analytics()
    {
        $this->actingAs($this->rektor);
        Session::put('active_role', 'rektor');

        $component = Livewire::test(ExecDashboard::class);
        $component->assertStatus(200);
        $this->assertTrue(is_array($component->get('focusAreasChartData')));
        $this->assertTrue(is_array($component->get('facultyPerformanceChartData')));
    }

    public function test_kaprodi_dashboard_loads_analytics()
    {
        // Vetted by AI - Manual Review Required by Senior Engineer/Manager
        $kaprodi = User::factory()->create(['name' => 'Kaprodi']);
        $kaprodi->assignRole('kaprodi');

        $studyProgram = StudyProgram::factory()->create(['kaprodi_user_id' => $kaprodi->id]);

        $this->actingAs($kaprodi);
        Session::put('active_role', 'kaprodi');

        $component = Livewire::test(ExecDashboard::class);
        $component->assertStatus(200);
        $this->assertTrue(is_array($component->get('focusAreasChartData')));
        $this->assertTrue(is_array($component->get('facultyPerformanceChartData')));
    }
}
