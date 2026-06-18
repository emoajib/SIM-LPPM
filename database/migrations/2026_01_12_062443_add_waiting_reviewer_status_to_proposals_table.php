<?php

use Database\Helpers\MigrationHelpers;
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
        // All ProposalStatus values (including waiting_reviewer)
        $allValues = ['draft', 'submitted', 'need_assignment', 'approved', 'waiting_reviewer', 'under_review', 'reviewed', 'revision_needed', 'completed', 'rejected'];

        MigrationHelpers::dropCheckConstraint('proposals', 'proposals_status_check');

        Schema::table('proposals', function (Blueprint $table) {
            $table->string('status', 50)->default('draft')->change();
        });

        MigrationHelpers::addCheckConstraintToTable(
            'proposals',
            'status',
            $allValues,
            MigrationHelpers::generateConstraintName('proposals', 'status')
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First, update any proposals with waiting_reviewer status back to approved
        DB::table('proposals')
            ->where('status', 'waiting_reviewer')
            ->update(['status' => 'approved']);

        // Old values without waiting_reviewer
        $oldValues = ['draft', 'submitted', 'need_assignment', 'approved', 'under_review', 'reviewed', 'revision_needed', 'completed', 'rejected'];

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
