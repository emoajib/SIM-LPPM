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
     * Update status enum to match BIMA 2025/2026 status values:
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
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE additional_outputs MODIFY COLUMN status ENUM('draft', 'submitted', 'under_review', 'accepted', 'published', 'rejected') NULL COMMENT 'Publication status (BIMA 2025/2026)'");
        } elseif ($driver === 'pgsql') {
            // Drop old CHECK constraint, add new one
            DB::statement('ALTER TABLE additional_outputs DROP CONSTRAINT IF EXISTS additional_outputs_status_check');
            DB::statement("ALTER TABLE additional_outputs ADD CONSTRAINT additional_outputs_status_check CHECK (status IN ('draft', 'submitted', 'under_review', 'accepted', 'published', 'rejected'))");
        } elseif ($driver === 'sqlite') {
            // SQLite: recreate table via Blueprint (Laravel handles)
            Schema::table('additional_outputs', function (Blueprint $table) {
                $table->enum('status', ['draft', 'submitted', 'under_review', 'accepted', 'published', 'rejected'])->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum values
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE additional_outputs MODIFY COLUMN status ENUM('review', 'editing', 'published') NOT NULL COMMENT 'Publication status'");
        } elseif ($driver === 'pgsql') {
            // Drop old CHECK constraint, add new one
            DB::statement('ALTER TABLE additional_outputs DROP CONSTRAINT IF EXISTS additional_outputs_status_check');
            DB::statement("ALTER TABLE additional_outputs ADD CONSTRAINT additional_outputs_status_check CHECK (status IN ('review', 'editing', 'published'))");
        } elseif ($driver === 'sqlite') {
            // SQLite: recreate table via Blueprint (Laravel handles)
            Schema::table('additional_outputs', function (Blueprint $table) {
                $table->enum('status', ['review', 'editing', 'published'])->nullable(false)->change();
            });
        }
    }
};
