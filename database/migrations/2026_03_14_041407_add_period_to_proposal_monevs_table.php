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
        // 1. Add columns as nullable first
        Schema::table('proposal_monevs', function (Blueprint $table) {
            if (! Schema::hasColumn('proposal_monevs', 'academic_year')) {
                $table->string('academic_year')->nullable()->after('proposal_id');
            }
            if (! Schema::hasColumn('proposal_monevs', 'semester')) {
                $table->enum('semester', ['ganjil', 'genap'])->nullable()->after('academic_year');
            }
        });

        // 2. Update existing records with fallback to proposal data
        DB::statement("
            UPDATE proposal_monevs
            SET academic_year = (
                SELECT start_year
                FROM proposals
                WHERE proposals.id = proposal_monevs.proposal_id
            ),
                semester = COALESCE((
                    SELECT semester
                    FROM proposals
                    WHERE proposals.id = proposal_monevs.proposal_id
                ), 'ganjil')
            WHERE academic_year IS NULL
        ");

        // 3. Set columns to NOT NULL after data is populated
        $driver = DB::getDriverName();
        if ($driver !== 'sqlite') {
            if ($driver === 'mysql') {
                Schema::table('proposal_monevs', function (Blueprint $table) {
                    $table->string('academic_year')->nullable(false)->change();
                    $table->enum('semester', ['ganjil', 'genap'])->nullable(false)->change();
                });
            } elseif ($driver === 'pgsql') {
                // PostgreSQL: column already handles NOT NULL via base migration CHECK constraint
                // No action needed - the CHECK constraint in base migration already enforces NOT NULL
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proposal_monevs', function (Blueprint $table) {
            $table->dropColumn(['academic_year', 'semester']);
        });
    }
};
