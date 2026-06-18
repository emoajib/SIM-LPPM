<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add waiting_reviewer status to the enum
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE proposals MODIFY COLUMN status ENUM('draft','submitted','need_assignment','approved','waiting_reviewer','under_review','reviewed','revision_needed','completed','rejected') NOT NULL DEFAULT 'draft'");
        } elseif ($driver === 'pgsql') {
            // Drop old CHECK constraint, add new one
            DB::statement('ALTER TABLE proposals DROP CONSTRAINT IF EXISTS proposals_status_check');
            DB::statement("ALTER TABLE proposals ADD CONSTRAINT proposals_status_check CHECK (status IN ('draft','submitted','need_assignment','approved','waiting_reviewer','under_review','reviewed','revision_needed','completed','rejected'))");
        } elseif ($driver === 'sqlite') {
            // SQLite: recreate table via Blueprint (Laravel handles)
            Schema::table('proposals', function (Blueprint $table) {
                $table->enum('status', ['draft', 'submitted', 'need_assignment', 'approved', 'waiting_reviewer', 'under_review', 'reviewed', 'revision_needed', 'completed', 'rejected'])->default('draft')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First, update any proposals with waiting_reviewer status back to approved
        DB::table('proposals')
            ->where('status', 'waiting_reviewer')
            ->update(['status' => 'approved']);

        // Remove waiting_reviewer from the enum
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE proposals MODIFY COLUMN status ENUM('draft','submitted','need_assignment','approved','under_review','reviewed','revision_needed','completed','rejected') NOT NULL DEFAULT 'draft'");
        } elseif ($driver === 'pgsql') {
            // Drop old CHECK constraint, add new one
            DB::statement('ALTER TABLE proposals DROP CONSTRAINT IF EXISTS proposals_status_check');
            DB::statement("ALTER TABLE proposals ADD CONSTRAINT proposals_status_check CHECK (status IN ('draft','submitted','need_assignment','approved','under_review','reviewed','revision_needed','completed','rejected'))");
        } elseif ($driver === 'sqlite') {
            // SQLite: recreate table via Blueprint (Laravel handles)
            Schema::table('proposals', function (Blueprint $table) {
                $table->enum('status', ['draft', 'submitted', 'need_assignment', 'approved', 'under_review', 'reviewed', 'revision_needed', 'completed', 'rejected'])->default('draft')->change();
            });
        }
    }
};
