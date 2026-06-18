<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Update status_type enum to match BIMA 2025/2026 status values:
     * - draft: Masih dalam penyusunan
     * - submitted: Sudah disubmit ke jurnal
     * - under_review: Sedang direview
     * - accepted: Diterima
     * - published: Sudah terbit
     * - rejected: Ditolak
     */
    public function up(): void
    {
        // For MariaDB/MySQL, we need to use raw SQL to modify enum
        // PostgreSQL & SQLite use CHECK constraints in base migration
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE mandatory_outputs MODIFY COLUMN status_type ENUM('draft', 'submitted', 'under_review', 'accepted', 'published', 'rejected') NULL COMMENT 'Publication status (BIMA 2025/2026)'");
        } elseif ($driver === 'pgsql') {
            // Drop old CHECK constraint, add new one
            DB::statement('ALTER TABLE mandatory_outputs DROP CONSTRAINT IF EXISTS mandatory_outputs_status_type_check');
            DB::statement("ALTER TABLE mandatory_outputs ADD CONSTRAINT mandatory_outputs_status_type_check CHECK (status_type IN ('draft', 'submitted', 'under_review', 'accepted', 'published', 'rejected'))");
        } elseif ($driver === 'sqlite') {
            // SQLite: recreate table via Blueprint (Laravel handles)
            Schema::table('mandatory_outputs', function (Blueprint $table) {
                $table->enum('status_type', ['draft', 'submitted', 'under_review', 'accepted', 'published', 'rejected'])->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum values
        // PostgreSQL & SQLite - handled by base migration
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE mandatory_outputs MODIFY COLUMN status_type ENUM('published', 'accepted', 'under_review', 'rejected') NOT NULL COMMENT 'Publication status'");
        } elseif ($driver === 'pgsql') {
            // Drop old CHECK constraint, add new one
            DB::statement('ALTER TABLE mandatory_outputs DROP CONSTRAINT IF EXISTS mandatory_outputs_status_type_check');
            DB::statement("ALTER TABLE mandatory_outputs ADD CONSTRAINT mandatory_outputs_status_type_check CHECK (status_type IN ('published', 'accepted', 'under_review', 'rejected'))");
        } elseif ($driver === 'sqlite') {
            // SQLite: recreate table via Blueprint (Laravel handles)
            Schema::table('mandatory_outputs', function (Blueprint $table) {
                $table->enum('status_type', ['published', 'accepted', 'under_review', 'rejected'])->nullable(false)->change();
            });
        }
    }
};
