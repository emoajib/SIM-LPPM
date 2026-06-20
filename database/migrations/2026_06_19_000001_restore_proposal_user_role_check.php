<?php

use App\Enums\ProposalUserRole;
use Database\Helpers\MigrationHelpers;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Fix existing data case-sensitivity
        DB::table('proposal_user')
            ->whereIn('role', ['Ketua', 'Anggota'])
            ->update(['role' => DB::raw('LOWER(role)')]);

        // Fallback for any other invalid roles to avoid check constraint failure
        DB::table('proposal_user')
            ->whereNotIn('role', ['ketua', 'anggota'])
            ->update(['role' => 'anggota']);

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
