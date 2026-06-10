<?php

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
        // Add indexes to proposals table
        // We use raw SQL to support ALGORITHM=INPLACE LOCK=NONE for MySQL/MariaDB (zero downtime)
        // If using other DBs like PostgreSQL, this might need fallback or specific syntax

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE proposals ADD INDEX idx_proposals_status (status), ALGORITHM=INPLACE, LOCK=NONE');
            DB::statement('ALTER TABLE proposals ADD INDEX idx_proposals_created_at (created_at), ALGORITHM=INPLACE, LOCK=NONE');
            DB::statement('ALTER TABLE proposals ADD INDEX idx_proposals_submitter_status (submitter_id, status), ALGORITHM=INPLACE, LOCK=NONE');
            DB::statement('ALTER TABLE proposal_user ADD INDEX idx_proposal_user_status (status), ALGORITHM=INPLACE, LOCK=NONE');
        } else {
            Schema::table('proposals', function (Blueprint $table) {
                $table->index('status', 'idx_proposals_status');
                $table->index('created_at', 'idx_proposals_created_at');
                $table->index(['submitter_id', 'status'], 'idx_proposals_submitter_status');
            });

            Schema::table('proposal_user', function (Blueprint $table) {
                $table->index('status', 'idx_proposal_user_status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropIndex('idx_proposals_status');
            $table->dropIndex('idx_proposals_created_at');
            $table->dropIndex('idx_proposals_submitter_status');
        });

        Schema::table('proposal_user', function (Blueprint $table) {
            $table->dropIndex('idx_proposal_user_status');
        });
    }
};
