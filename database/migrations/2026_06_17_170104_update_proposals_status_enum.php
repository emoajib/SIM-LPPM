<?php

use App\Enums\ProposalStatus;
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
        MigrationHelpers::dropCheckConstraint('proposals', 'proposals_status_check');

        Schema::table('proposals', function (Blueprint $table) {
            $table->string('status', 50)->default('draft')->comment('Status Proposal')->change();
        });

        MigrationHelpers::addCheckConstraintToTable(
            'proposals',
            'status',
            ProposalStatus::values(),
            MigrationHelpers::generateConstraintName('proposals', 'status')
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to old enum without revision_submitted (previous state)
        $oldValues = array_values(
            array_diff(ProposalStatus::values(), [ProposalStatus::REVISION_SUBMITTED->value])
        );

        MigrationHelpers::dropCheckConstraint('proposals', 'proposals_status_check');

        Schema::table('proposals', function (Blueprint $table) {
            $table->string('status', 50)->default('draft')->change();
        });

        MigrationHelpers::addCheckConstraintToTable(
            'proposals',
            'status',
            $oldValues,
            MigrationHelpers::generateConstraintName('proposals', 'status')
        );
    }
};
