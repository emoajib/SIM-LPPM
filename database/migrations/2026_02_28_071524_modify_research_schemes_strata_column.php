<?php

use App\Enums\ResearchSchemeStrata;
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
        Schema::table('research_schemes', function (Blueprint $table) {
            $table->string('strata', 50)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('research_schemes', function (Blueprint $table) {
            $table->string('strata', 50)->change();
        });

        MigrationHelpers::dropCheckConstraint('research_schemes', 'research_schemes_strata_check');
        MigrationHelpers::addCheckConstraintToTable(
            'research_schemes',
            'strata',
            ResearchSchemeStrata::values(),
            MigrationHelpers::generateConstraintName('research_schemes', 'strata')
        );
    }
};
