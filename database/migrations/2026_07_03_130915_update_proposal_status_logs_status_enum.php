<?php

use App\Enums\ProposalStatus;
use Database\Helpers\MigrationHelpers;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    private const TABLE = 'proposal_status_logs';

    /**
     * Run the migrations.
     *
     * Fixes: SQLSTATE[01000]: Warning: 1265 Data truncated for column 'status_before'
     *
     * Root cause: when the migration file 2025_11_09_071120_create_proposal_status_logs_table.php
     * was refactored from ->enum() to ->string()+CHECK, existing databases that already ran
     * the original migration never got the column type updated. The MySQL ENUM column
     * still only allows the old set of statuses (without 'revision_submitted').
     *
     * This migration forces the columns to VARCHAR(50) and recreates CHECK constraints
     * with the current ProposalStatus::values() which includes revision_submitted.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();
        $table = self::TABLE;

        // 1. Drop old CHECK constraints (idempotent)
        MigrationHelpers::dropCheckConstraint(
            $table,
            MigrationHelpers::generateConstraintName($table, 'status_before')
        );
        MigrationHelpers::dropCheckConstraint(
            $table,
            MigrationHelpers::generateConstraintName($table, 'status_after')
        );

        // 2. Re-create columns as VARCHAR(50) to drop any residual ENUM type (MySQL)
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE `{$table}` MODIFY COLUMN `status_before` VARCHAR(50) NOT NULL");
            DB::statement("ALTER TABLE `{$table}` MODIFY COLUMN `status_after` VARCHAR(50) NOT NULL");
        }

        // 3. Sanitize any invalid values before re-adding CHECK constraint
        $validValues = ProposalStatus::values();
        foreach (['status_before', 'status_after'] as $column) {
            $invalidCount = DB::table($table)
                ->whereNotIn($column, $validValues)
                ->count();

            if ($invalidCount > 0) {
                Log::warning("Found {$invalidCount} rows with invalid {$column} values, resetting to default");
                DB::table($table)
                    ->whereNotIn($column, $validValues)
                    ->update([$column => ProposalStatus::DRAFT->value]);
            }
        }

        // 4. Re-add CHECK constraints with current ProposalStatus values (includes revision_submitted)
        MigrationHelpers::addCheckConstraintToTable(
            $table,
            'status_before',
            ProposalStatus::values(),
            MigrationHelpers::generateConstraintName($table, 'status_before')
        );

        MigrationHelpers::addCheckConstraintToTable(
            $table,
            'status_after',
            ProposalStatus::values(),
            MigrationHelpers::generateConstraintName($table, 'status_after')
        );
    }

    /**
     * Reverse the migrations.
     *
     * Restores the previous CHECK constraints (without revision_submitted).
     * Also migrates any 'revision_submitted' rows to 'revision_needed' before
     * dropping the constraint to prevent CHECK validation failure.
     */
    public function down(): void
    {
        $table = self::TABLE;

        $fallbackStatus = ProposalStatus::REVISION_NEEDED->value;
        $oldValues = array_values(
            array_diff(ProposalStatus::values(), [ProposalStatus::REVISION_SUBMITTED->value])
        );

        foreach (['status_before', 'status_after'] as $column) {
            $affected = DB::table($table)
                ->where($column, ProposalStatus::REVISION_SUBMITTED->value)
                ->update([$column => $fallbackStatus]);

            if ($affected > 0) {
                Log::info("Migrated {$affected} {$column} rows from revision_submitted to {$fallbackStatus}");
            }
        }

        MigrationHelpers::dropCheckConstraint(
            $table,
            MigrationHelpers::generateConstraintName($table, 'status_before')
        );
        MigrationHelpers::dropCheckConstraint(
            $table,
            MigrationHelpers::generateConstraintName($table, 'status_after')
        );

        MigrationHelpers::addCheckConstraintToTable(
            $table,
            'status_before',
            $oldValues,
            MigrationHelpers::generateConstraintName($table, 'status_before')
        );

        MigrationHelpers::addCheckConstraintToTable(
            $table,
            'status_after',
            $oldValues,
            MigrationHelpers::generateConstraintName($table, 'status_after')
        );
    }
};
