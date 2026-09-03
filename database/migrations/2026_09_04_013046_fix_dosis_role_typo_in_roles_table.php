<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Vetted by AI - Manual Review Required by Senior Engineer/Manager
     */
    public function up(): void
    {
        $dosisRole = DB::table('roles')->where('name', 'dosis')->first();
        if (! $dosisRole) {
            return;
        }

        $dosenRole = DB::table('roles')->where('name', 'dosen')->first();

        if ($dosenRole) {
            // Re-point any user role assignments from 'dosis' to 'dosen'
            $assignments = DB::table('model_has_roles')
                ->where('role_id', $dosisRole->id)
                ->get();

            foreach ($assignments as $assignment) {
                // If user doesn't already have 'dosen', update role_id; otherwise delete redundant assignment
                $alreadyHasDosen = DB::table('model_has_roles')
                    ->where('role_id', $dosenRole->id)
                    ->where('model_type', $assignment->model_type)
                    ->where('model_uuid', $assignment->model_uuid)
                    ->exists();

                if (! $alreadyHasDosen) {
                    DB::table('model_has_roles')
                        ->where('role_id', $dosisRole->id)
                        ->where('model_type', $assignment->model_type)
                        ->where('model_uuid', $assignment->model_uuid)
                        ->update(['role_id' => $dosenRole->id]);
                } else {
                    DB::table('model_has_roles')
                        ->where('role_id', $dosisRole->id)
                        ->where('model_type', $assignment->model_type)
                        ->where('model_uuid', $assignment->model_uuid)
                        ->delete();
                }
            }

            // Also re-point role_has_permissions if any
            if (Schema::hasTable('role_has_permissions')) {
                DB::table('role_has_permissions')
                    ->where('role_id', $dosisRole->id)
                    ->delete();
            }

            // Remove the typo role record
            DB::table('roles')->where('id', $dosisRole->id)->delete();
        } else {
            // If 'dosen' role doesn't exist, simply rename 'dosis' to 'dosen'
            DB::table('roles')->where('id', $dosisRole->id)->update(['name' => 'dosen']);
        }

        // Clear spatie permission cache
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse needed as 'dosis' is a typo
    }
};
