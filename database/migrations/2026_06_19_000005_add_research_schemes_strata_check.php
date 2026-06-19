<?php

use App\Enums\ResearchSchemeStrata;
use Database\Helpers\MigrationHelpers;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        MigrationHelpers::addCheckConstraintToTable(
            'research_schemes',
            'strata',
            ResearchSchemeStrata::values(),
            MigrationHelpers::generateConstraintName('research_schemes', 'strata')
        );

        MigrationHelpers::addCheckConstraintToTable(
            'community_service_schemes',
            'strata',
            ResearchSchemeStrata::values(),
            MigrationHelpers::generateConstraintName('community_service_schemes', 'strata')
        );
    }

    public function down(): void
    {
        MigrationHelpers::dropCheckConstraint(
            'research_schemes',
            MigrationHelpers::generateConstraintName('research_schemes', 'strata')
        );

        MigrationHelpers::dropCheckConstraint(
            'community_service_schemes',
            MigrationHelpers::generateConstraintName('community_service_schemes', 'strata')
        );
    }
};
