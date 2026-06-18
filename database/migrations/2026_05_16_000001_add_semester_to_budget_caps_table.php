<?php

use Database\Helpers\MigrationHelpers;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds semester support to budget_caps table.
     */
    public function up(): void
    {
        Schema::table('budget_caps', function (Blueprint $table) {
            // Remove the unique constraint on year to allow multiple semesters per year
            $table->dropUnique(['year']);

            // Add semester column (ganjil/genap) aligned with proposals table
            $table->string('semester', 50)->default('ganjil')->after('year');

            // Add new unique constraint for year and semester combination
            $table->unique(['year', 'semester']);
        });

        MigrationHelpers::addCheckConstraintToTable(
            'budget_caps',
            'semester',
            ['ganjil', 'genap'],
            MigrationHelpers::generateConstraintName('budget_caps', 'semester')
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        MigrationHelpers::dropCheckConstraint('budget_caps', 'budget_caps_semester_check');

        Schema::table('budget_caps', function (Blueprint $table) {
            $table->dropUnique(['year', 'semester']);
            $table->dropColumn('semester');
            $table->year('year')->unique()->change();
        });
    }
};
