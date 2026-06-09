<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $reviewerRole = Role::where('name', 'reviewer')->where('guard_name', 'web')->first();

        if (! $reviewerRole) {
            return;
        }

        // Find users who have a reviewer_profile but no role assigned
        $orphanUsers = DB::table('users')
            ->whereIn('id', function ($query) {
                $query->select('user_id')->from('reviewer_profiles');
            })
            ->whereNotIn('id', function ($query) {
                $query->select('model_uuid')
                    ->from('model_has_roles')
                    ->where('model_type', 'App\\Models\\User');
            })
            ->pluck('id');

        foreach ($orphanUsers as $userId) {
            DB::table('model_has_roles')->insert([
                'role_id' => $reviewerRole->id,
                'model_type' => 'App\\Models\\User',
                'model_uuid' => $userId,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nothing to reverse - backfill is idempotent
    }
};
