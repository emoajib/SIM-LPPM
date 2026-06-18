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
        // Using raw SQL to ensure the column type and nullability are correctly updated
        // as Doctrine DBAL often struggles with modifying ENUMs to Strings or changing nullability of ENUMs.

        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            Schema::table('mandatory_outputs', function (Blueprint $table) {
                $table->string('status_type')->nullable()->change();
                $table->string('author_status')->nullable()->change();
            });

            Schema::table('additional_outputs', function (Blueprint $table) {
                $table->string('status')->nullable()->change();
            });
        } elseif ($driver === 'mysql') {
            DB::statement('ALTER TABLE mandatory_outputs MODIFY COLUMN status_type VARCHAR(255) NULL');
            DB::statement('ALTER TABLE mandatory_outputs MODIFY COLUMN author_status VARCHAR(255) NULL');

            DB::statement('ALTER TABLE additional_outputs MODIFY COLUMN status VARCHAR(255) NULL');
        } elseif ($driver === 'pgsql') {
            // PostgreSQL: column already handles nullability via base migration CHECK constraint
            // No action needed - the CHECK constraint in base migration already allows NULL
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverting is complex because we don't know if data violates the enum constraints.
        // We will leave it as is for this hotfix.
    }
};
