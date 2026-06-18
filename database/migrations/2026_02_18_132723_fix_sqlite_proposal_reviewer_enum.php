<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            // Disable foreign keys to allow dropping table
            DB::statement('PRAGMA foreign_keys=OFF;');

            // 1. Create new table with correct schema
            // status: pending, in_progress, completed, re_review_requested
            DB::statement('CREATE TABLE "proposal_reviewer_new" (
                "id" integer primary key autoincrement not null, 
                "proposal_id" varchar not null, 
                "user_id" varchar not null, 
                "status" varchar check ("status" in (\'pending\', \'in_progress\', \'completed\', \'re_review_requested\')) not null default \'pending\', 
                "review_notes" text, 
                "recommendation" varchar check ("recommendation" in (\'approved\', \'rejected\', \'revision_needed\')), 
                "created_at" datetime, 
                "updated_at" datetime, 
                "round" integer not null default \'1\', 
                "assigned_at" datetime, 
                "deadline_at" datetime, 
                "started_at" datetime, 
                "completed_at" datetime, 
                foreign key("proposal_id") references "proposals"("id") on delete cascade, 
                foreign key("user_id") references "users"("id") on delete cascade
            )');

            // 2. Copy data from old table to new table
            // Be careful with existing 'reviewing' status if any, map it to 'in_progress' or keep as is if validation allows
            // Since we know the old table has 'pending', 'reviewing', 'completed', we map 'reviewing' -> 'in_progress' if needed
            // But wait, the previous migration ALREADY updated 'reviewing' -> 'pending' in Step 706. So data should be safe.
            DB::statement('INSERT INTO "proposal_reviewer_new" SELECT * FROM "proposal_reviewer"');

            // 3. Drop old table
            DB::statement('DROP TABLE "proposal_reviewer"');

            // 4. Rename new table
            DB::statement('ALTER TABLE "proposal_reviewer_new" RENAME TO "proposal_reviewer"');

            // 5. Recreate indexes
            DB::statement('CREATE UNIQUE INDEX "proposal_reviewer_proposal_id_user_id_unique" on "proposal_reviewer" ("proposal_id", "user_id")');
            DB::statement('CREATE INDEX "proposal_reviewer_proposal_id_index" on "proposal_reviewer" ("proposal_id")');
            DB::statement('CREATE INDEX "proposal_reviewer_user_id_index" on "proposal_reviewer" ("user_id")');

            // New indexes from enhance migration
            DB::statement('CREATE INDEX "proposal_reviewer_deadline_at_index" on "proposal_reviewer" ("deadline_at")');
            DB::statement('CREATE INDEX "proposal_reviewer_round_index" on "proposal_reviewer" ("round")');

            // Re-enable foreign keys
            DB::statement('PRAGMA foreign_keys=ON;');
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            // Reverting SQLite schema changes manually is complex and risky,
            // we generally assume forward-fix in dev.
        }
    }
};
