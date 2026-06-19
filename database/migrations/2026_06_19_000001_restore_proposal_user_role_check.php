<?php

use App\Enums\ProposalUserRole;
use Database\Helpers\MigrationHelpers;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Drop first to ensure idempotent re-creation
        MigrationHelpers::dropCheckConstraint(
            'proposal_user',
            MigrationHelpers::generateConstraintName('proposal_user', 'role')
        );

        MigrationHelpers::addCheckConstraintToTable(
            'proposal_user',
            'role',
            ProposalUserRole::values(),
            MigrationHelpers::generateConstraintName('proposal_user', 'role')
        );
    }

    public function down(): void
    {
        MigrationHelpers::dropCheckConstraint(
            'proposal_user',
            MigrationHelpers::generateConstraintName('proposal_user', 'role')
        );
    }
};
