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
        // PostgreSQL: uses CHECK constraints in base migration, skip for PostgreSQL
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            Schema::table('progress_reports', function (Blueprint $table) {
                $table->enum('status', ['DRAFT', 'SUBMITTED', 'approved_by_dekan', 'APPROVED', 'REJECTED'])
                    ->default('DRAFT')
                    ->comment('Status laporan')
                    ->change();
            });
        } elseif ($driver === 'pgsql') {
            // Drop old CHECK constraint, add new one
            DB::statement('ALTER TABLE progress_reports DROP CONSTRAINT IF EXISTS progress_reports_status_check');
            DB::statement("ALTER TABLE progress_reports ADD CONSTRAINT progress_reports_status_check CHECK (status IN ('DRAFT', 'SUBMITTED', 'approved_by_dekan', 'APPROVED', 'REJECTED'))");
        } elseif ($driver === 'sqlite') {
            // SQLite: recreate table via Blueprint (Laravel handles)
            Schema::table('progress_reports', function (Blueprint $table) {
                $table->enum('status', ['DRAFT', 'SUBMITTED', 'approved_by_dekan', 'APPROVED', 'REJECTED'])
                    ->default('DRAFT')
                    ->comment('Status laporan')
                    ->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot easily revert
    }
};
