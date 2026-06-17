<?php

use App\Enums\ProposalStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // MySQL/MariaDB only: Update enum values
        // Note: SQLite does not support ALTER TABLE MODIFY. The enum is already correct
        // from the base migration which uses ProposalStatus::values().
        if (DB::getDriverName() !== 'sqlite') {
            $enumValues = implode("','", ProposalStatus::values());
            DB::statement("ALTER TABLE proposals MODIFY status ENUM('$enumValues') DEFAULT 'draft' COMMENT 'Status Proposal'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to old enum without revision_submitted (previous state)
        if (DB::getDriverName() !== 'sqlite') {
            $oldValues = array_diff(ProposalStatus::values(), [ProposalStatus::REVISION_SUBMITTED->value]);
            $enumValues = implode("','", $oldValues);
            DB::statement("ALTER TABLE proposals MODIFY status ENUM('$enumValues') DEFAULT 'draft' COMMENT 'Status Proposal'");
        }
    }
};
