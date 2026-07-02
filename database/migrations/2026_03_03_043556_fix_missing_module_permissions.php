<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Re-sync all module permissions and ensure all roles have what they need
        $mappings = [
            'module_penelitian' => ['dosen', 'kepala lppm', 'admin lppm', 'rektor', 'dekan', 'reviewer', 'superadmin'],
            'module_pengabdian' => ['dosen', 'kepala lppm', 'admin lppm', 'rektor', 'dekan', 'reviewer', 'superadmin'],
            'module_rekognisi' => ['dosen', 'superadmin'],
            'module_persetujuan_dekan' => ['dekan', 'superadmin'],
            'module_persetujuan_awal' => ['kepala lppm', 'superadmin'],
            'module_persetujuan_akhir' => ['kepala lppm', 'superadmin'],
            'module_reviewer_management' => ['admin lppm', 'superadmin'],
            'module_monev' => ['admin lppm', 'superadmin'],
            'module_review' => ['reviewer', 'admin lppm', 'superadmin'],
            'module_laporan' => ['admin lppm', 'rektor', 'kepala lppm', 'superadmin'],
            'module_iku' => ['admin lppm', 'rektor', 'kepala lppm', 'dekan', 'superadmin'],
            'module_kelola_pengguna' => ['admin lppm', 'superadmin'],
            'module_arsip_data' => ['admin lppm', 'superadmin'],
            'module_export_sinta' => ['admin lppm', 'superadmin'],
            'module_pengaturan' => ['admin lppm', 'superadmin'],
            'module_persetujuan_kaprodi' => ['kaprodi', 'superadmin'],
            'module_manual_book' => ['admin lppm', 'superadmin'],
        ];

        foreach ($mappings as $permissionName => $roles) {
            // Ensure permission exists
            $permission = DB::table('permissions')
                ->where('name', $permissionName)
                ->where('guard_name', 'web')
                ->first();

            if (! $permission) {
                $permissionId = Str::uuid()->toString();
                DB::table('permissions')->insert([
                    'id' => $permissionId,
                    'name' => $permissionName,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $permissionId = $permission->id;
            }

            // Sync roles
            foreach ($roles as $roleName) {
                $role = DB::table('roles')->where('name', $roleName)->where('guard_name', 'web')->first();

                if ($role) {
                    $exists = DB::table('role_has_permissions')
                        ->where('permission_id', $permissionId)
                        ->where('role_id', $role->id)
                        ->exists();

                    if (! $exists) {
                        DB::table('role_has_permissions')->insert([
                            'permission_id' => $permissionId,
                            'role_id' => $role->id,
                        ]);
                    }
                }
            }
        }

        // Clear Spatie permission cache
        try {
            if (app()->bound(PermissionRegistrar::class)) {
                app(PermissionRegistrar::class)->forgetCachedPermissions();
            }
        } catch (Throwable $e) {
            // Silently fail if cache clearing fails
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to undo this specific fix
    }
};
