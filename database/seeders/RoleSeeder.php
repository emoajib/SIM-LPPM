<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    use SeedHelper;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Forget cached permissions at the start
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // sanity check: make sure no duplicate role names are already present
        $this->assertUnique(Role::class, 'name');

        $roles = [
            'superadmin',
            'admin lppm',
            'kepala lppm',
            'dekan',
            'kaprodi',
            'dosen',
            'reviewer',
            'rektor',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web'],
                ['id' => Str::uuid(), 'name' => $roleName, 'guard_name' => 'web']
            );
        }

        // Consolidated Mapping from Migrations
        $mappings = [
            'module_penelitian' => ['dosen', 'kepala lppm', 'admin lppm', 'rektor', 'dekan', 'kaprodi', 'reviewer'],
            'module_pengabdian' => ['dosen', 'kepala lppm', 'admin lppm', 'rektor', 'dekan', 'kaprodi', 'reviewer'],
            'module_rekognisi' => ['dosen'],
            'module_persetujuan_dekan' => ['dekan'],
            'module_persetujuan_kaprodi' => ['kaprodi'],
            'module_persetujuan_awal' => ['kepala lppm'],
            'module_persetujuan_akhir' => ['kepala lppm'],
            'module_reviewer_management' => ['admin lppm'],
            'module_monev' => ['admin lppm'],
            'module_review' => ['reviewer', 'admin lppm'],
            'module_laporan' => ['admin lppm', 'rektor', 'kepala lppm', 'dekan'],
            'module_iku' => ['admin lppm', 'rektor', 'kepala lppm', 'dekan'],
            'module_kelola_pengguna' => ['admin lppm'],
            'module_arsip_data' => ['admin lppm'],
            'module_export_sinta' => ['admin lppm'],
            'module_pengaturan' => ['admin lppm'],
            'module_manual_book' => ['admin lppm', 'superadmin'],
        ];

        foreach ($mappings as $permissionName => $roleNames) {
            $permission = Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
                ['id' => Str::uuid()->toString(), 'name' => $permissionName, 'guard_name' => 'web']
            );

            // Re-forget cache after creating permission to ensure hasPermissionTo works
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            foreach ($roleNames as $roleName) {
                $role = \App\Models\Role::where('name', $roleName)->where('guard_name', 'web')->first();
                if ($role && ! $role->hasPermissionTo($permissionName)) {
                    $role->givePermissionTo($permission);
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertUnique(Role::class, 'name');
    }
}
