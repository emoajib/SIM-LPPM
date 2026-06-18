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
        // PostgreSQL & SQLite: use raw SQL for enum changes
        // MySQL: handled by Doctrine DBAL
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            Schema::table('identities', function (Blueprint $table) {
                $table->enum('type', ['dosen', 'mahasiswa', 'reviewer', 'tendik'])->comment('Tipe User')->change();
            });
        } elseif ($driver === 'pgsql') {
            // Drop old CHECK constraint, add new one
            DB::statement('ALTER TABLE identities DROP CONSTRAINT IF EXISTS identities_type_check');
            DB::statement("ALTER TABLE identities ADD CONSTRAINT identities_type_check CHECK (type IN ('dosen', 'mahasiswa', 'reviewer', 'tendik'))");
        } elseif ($driver === 'sqlite') {
            // SQLite: recreate table via Blueprint (Laravel handles)
            Schema::table('identities', function (Blueprint $table) {
                $table->enum('type', ['dosen', 'mahasiswa', 'reviewer', 'tendik'])->comment('Tipe User')->change();
            });
        }
    }

    public function down(): void
    {
        // Cannot easily revert without data migration
    }
};
