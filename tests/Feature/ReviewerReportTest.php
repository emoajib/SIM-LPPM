<?php

// Vetted by AI - Manual Review Required by Senior Engineer/Manager

namespace Tests\Feature;

use App\Livewire\Reports\ReviewerReport;
use App\Models\User;
use Database\Seeders\InstitutionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ReviewerReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear permission cache
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

        // Seed roles and institutions
        $this->seed(RoleSeeder::class);
        $this->seed(InstitutionSeeder::class);

        // Ensure permission exists
        Permission::firstOrCreate(['name' => 'module_laporan']);
    }

    public function test_unauthorized_roles_cannot_access_reviewer_report()
    {
        // 1. Dosen cannot access
        $dosen = User::factory()->create();
        $dosen->assignRole('dosen');
        $dosen->givePermissionTo('module_laporan');

        $this->actingAs($dosen);
        Livewire::test(ReviewerReport::class)->assertStatus(403);

        // 2. Reviewer cannot access
        $reviewer = User::factory()->create();
        $reviewer->assignRole('reviewer');
        $reviewer->givePermissionTo('module_laporan');

        $this->actingAs($reviewer);
        Livewire::test(ReviewerReport::class)->assertStatus(403);
    }

    public function test_authorized_roles_can_access_reviewer_report()
    {
        // 1. Admin LPPM
        $admin = User::factory()->create();
        $admin->assignRole('admin lppm');
        $admin->givePermissionTo('module_laporan');

        $this->actingAs($admin);
        Livewire::test(ReviewerReport::class)->assertStatus(200);

        // 2. Kepala LPPM
        $kepala = User::factory()->create();
        $kepala->assignRole('kepala lppm');
        $kepala->givePermissionTo('module_laporan');

        $this->actingAs($kepala);
        Livewire::test(ReviewerReport::class)->assertStatus(200);

        // 3. Rektor
        $rektor = User::factory()->create();
        $rektor->assignRole('rektor');
        $rektor->givePermissionTo('module_laporan');

        $this->actingAs($rektor);
        Livewire::test(ReviewerReport::class)->assertStatus(200);

        // 4. Superadmin
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');
        $superadmin->givePermissionTo('module_laporan');

        $this->actingAs($superadmin);
        Livewire::test(ReviewerReport::class)->assertStatus(200);
    }

    public function test_reviewer_report_rendering_and_components()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin lppm');
        $admin->givePermissionTo('module_laporan');

        $this->actingAs($admin);

        $component = Livewire::test(ReviewerReport::class);
        $component->assertStatus(200);

        $component->set('activeTab', 'workload');
        $component->assertStatus(200);

        $component->set('activeTab', 'scoring');
        $component->assertStatus(200);
    }

    public function test_reviewer_report_exports()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin lppm');
        $admin->givePermissionTo('module_laporan');

        $this->actingAs($admin);

        // Test PDF export route
        $responsePdf = $this->get(route('reports.reviewer.pdf', ['period' => date('Y')]));
        $responsePdf->assertStatus(200);
        $responsePdf->assertHeader('Content-Type', 'application/pdf');

        // Test Excel export route
        $responseExcel = $this->get(route('reports.reviewer.excel', ['period' => date('Y')]));
        $responseExcel->assertStatus(200);
        $responseExcel->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
