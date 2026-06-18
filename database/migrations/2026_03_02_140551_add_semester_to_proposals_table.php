<?php

use Database\Helpers\MigrationHelpers;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->string('semester', 50)->nullable()->after('start_year');
        });

        MigrationHelpers::addCheckConstraintToTable(
            'proposals',
            'semester',
            ['ganjil', 'genap'],
            MigrationHelpers::generateConstraintName('proposals', 'semester')
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        MigrationHelpers::dropCheckConstraint('proposals', 'proposals_semester_check');

        Schema::table('proposals', function (Blueprint $table) {
            $table->dropColumn('semester');
        });
    }
};
