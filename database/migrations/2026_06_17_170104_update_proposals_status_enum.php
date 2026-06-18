<?php

use App\Enums\ProposalStatus;
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
        // MySQL/MariaDB only: Update enum values
        // Note: SQLite does not support ALTER TABLE MODIFY. The enum is already correct
        // from the base migration which uses ProposalStatus::values().
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            $enumValues = implode("','", ProposalStatus::values());
            DB::statement("ALTER TABLE proposals MODIFY status ENUM('$enumValues') DEFAULT 'draft' COMMENT 'Status Proposal'");
        } elseif ($driver === 'pgsql') {
            // Drop old CHECK constraint, add new one
            $enumValues = implode("','", ProposalStatus::values());
            DB::statement('ALTER TABLE proposals DROP CONSTRAINT IF EXISTS proposals_status_check');
            DB::statement("ALTER TABLE proposals ADD CONSTRAINT proposals_status_check CHECK (status IN ('$enumValues'))");
        } elseif ($driver === 'sqlite') {
            // SQLite: recreate table via Blueprint (Laravel handles)
            Schema::table('proposals', function (Blueprint $table) {
                $table->enum('status', ProposalStatus::values())->default('draft')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to old enum without revision_submitted (previous state)
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            $oldValues = array_diff(ProposalStatus::values(), [ProposalStatus::REVISION_SUBMITTED->value]);
            $enumValues = implode("','", $oldValues);
            DB::statement("ALTER TABLE proposals MODIFY status ENUM('$enumValues') DEFAULT 'draft' COMMENT 'Status Proposal'");
        } elseif ($driver === 'pgsql') {
            // Drop old CHECK constraint, add new one
            $oldValues = array_diff(ProposalStatus::values(), [ProposalStatus::REVISION_SUBMITTED->value]);
            $enumValues = implode("','", $oldValues);
            DB::statement('ALTER TABLE proposals DROP CONSTRAINT IF EXISTS proposals_status_check');
            DB::statement("ALTER TABLE proposals ADD CONSTRAINT proposals_status_check CHECK (status IN ('$enumValues'))");
        } elseif ($driver === 'sqlite') {
            // SQLite: recreate table via Blueprint (Laravel handles)
            $oldValues = array_diff(ProposalStatus::values(), [ProposalStatus::REVISION_SUBMITTED->value]);
            Schema::table('proposals', function (Blueprint $table) {
                $table->enum('status', $oldValues)->default('draft')->change();
            });
        }
    }
};
