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
        } elseif ($driver === 'mysql') {
            // MySQL: recreate table with new schema
            Schema::disableForeignKeyConstraints();

            // 1. Create new table with correct schema
            DB::statement('CREATE TABLE `proposal_reviewer_new` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `proposal_id` varchar(255) NOT NULL,
                `user_id` varchar(255) NOT NULL,
                `status` enum(\'pending\', \'in_progress\', \'completed\', \'re_review_requested\') NOT NULL DEFAULT \'pending\',
                `review_notes` text,
                `recommendation` enum(\'approved\', \'rejected\', \'revision_needed\'),
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                `round` int NOT NULL DEFAULT 1,
                `assigned_at` datetime DEFAULT NULL,
                `deadline_at` datetime DEFAULT NULL,
                `started_at` datetime DEFAULT NULL,
                `completed_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `proposal_reviewer_proposal_id_index` (`proposal_id`),
                KEY `proposal_reviewer_user_id_index` (`user_id`),
                KEY `proposal_reviewer_deadline_at_index` (`deadline_at`),
                KEY `proposal_reviewer_round_index` (`round`),
                CONSTRAINT `proposal_reviewer_proposal_id_foreign` FOREIGN KEY (`proposal_id`) REFERENCES `proposals` (`id`) ON DELETE CASCADE,
                CONSTRAINT `proposal_reviewer_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            // 2. Copy data from old table to new table
            DB::statement('INSERT INTO `proposal_reviewer_new` SELECT * FROM `proposal_reviewer`');

            // 3. Drop old table
            Schema::dropIfExists('proposal_reviewer');

            // 4. Rename new table
            DB::statement('ALTER TABLE `proposal_reviewer_new` RENAME TO `proposal_reviewer`');

            Schema::enableForeignKeyConstraints();
        } elseif ($driver === 'pgsql') {
            // PostgreSQL: recreate table with new schema
            Schema::disableForeignKeyConstraints();

            // 1. Create new table with correct schema
            DB::statement('CREATE TABLE proposal_reviewer_new (
                id serial PRIMARY KEY,
                proposal_id uuid NOT NULL,
                user_id uuid NOT NULL,
                status varchar CHECK (status IN (\'pending\', \'in_progress\', \'completed\', \'re_review_requested\')) NOT NULL DEFAULT \'pending\',
                review_notes text,
                recommendation varchar CHECK (recommendation IN (\'approved\', \'rejected\', \'revision_needed\')),
                created_at timestamp(0) without time zone DEFAULT NULL,
                updated_at timestamp(0) without time zone DEFAULT NULL,
                round integer NOT NULL DEFAULT 1,
                assigned_at timestamp(0) without time zone DEFAULT NULL,
                deadline_at timestamp(0) without time zone DEFAULT NULL,
                started_at timestamp(0) without time zone DEFAULT NULL,
                completed_at timestamp(0) without time zone DEFAULT NULL,
                CONSTRAINT proposal_reviewer_proposal_id_foreign FOREIGN KEY (proposal_id) REFERENCES proposals (id) ON DELETE CASCADE,
                CONSTRAINT proposal_reviewer_user_id_foreign FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            )');

            // 2. Copy data from old table to new table
            DB::statement('INSERT INTO proposal_reviewer_new SELECT * FROM proposal_reviewer');

            // 3. Drop old table
            Schema::dropIfExists('proposal_reviewer');

            // 4. Rename new table
            DB::statement('ALTER TABLE proposal_reviewer_new RENAME TO proposal_reviewer');

            Schema::enableForeignKeyConstraints();
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
        } elseif ($driver === 'mysql') {
            // MySQL: recreate old table
            Schema::disableForeignKeyConstraints();

            // 1. Create old table with original schema
            DB::statement('CREATE TABLE `proposal_reviewer_old` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `proposal_id` varchar(255) NOT NULL,
                `user_id` varchar(255) NOT NULL,
                `status` enum(\'pending\', \'reviewing\', \'completed\') NOT NULL DEFAULT \'pending\',
                `review_notes` text,
                `recommendation` enum(\'approved\', \'rejected\', \'revision_needed\'),
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                `round` int NOT NULL DEFAULT 1,
                `assigned_at` datetime DEFAULT NULL,
                `deadline_at` datetime DEFAULT NULL,
                `started_at` datetime DEFAULT NULL,
                `completed_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `proposal_reviewer_proposal_id_index` (`proposal_id`),
                KEY `proposal_reviewer_user_id_index` (`user_id`),
                KEY `proposal_reviewer_deadline_at_index` (`deadline_at`),
                KEY `proposal_reviewer_round_index` (`round`),
                CONSTRAINT `proposal_reviewer_proposal_id_foreign` FOREIGN KEY (`proposal_id`) REFERENCES `proposals` (`id`) ON DELETE CASCADE,
                CONSTRAINT `proposal_reviewer_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            // 2. Copy data from new table to old table
            DB::statement('INSERT INTO `proposal_reviewer_old` SELECT * FROM `proposal_reviewer`');

            // 3. Drop new table
            Schema::dropIfExists('proposal_reviewer');

            // 4. Rename old table
            DB::statement('ALTER TABLE `proposal_reviewer_old` RENAME TO `proposal_reviewer`');

            Schema::enableForeignKeyConstraints();
        } elseif ($driver === 'pgsql') {
            // PostgreSQL: recreate old table
            Schema::disableForeignKeyConstraints();

            // 1. Create old table with original schema
            DB::statement('CREATE TABLE proposal_reviewer_old (
                id serial PRIMARY KEY,
                proposal_id uuid NOT NULL,
                user_id uuid NOT NULL,
                status varchar CHECK (status IN (\'pending\', \'reviewing\', \'completed\')) NOT NULL DEFAULT \'pending\',
                review_notes text,
                recommendation varchar CHECK (recommendation IN (\'approved\', \'rejected\', \'revision_needed\')),
                created_at timestamp(0) without time zone DEFAULT NULL,
                updated_at timestamp(0) without time zone DEFAULT NULL,
                round integer NOT NULL DEFAULT 1,
                assigned_at timestamp(0) without time zone DEFAULT NULL,
                deadline_at timestamp(0) without time zone DEFAULT NULL,
                started_at timestamp(0) without time zone DEFAULT NULL,
                completed_at timestamp(0) without time zone DEFAULT NULL,
                CONSTRAINT proposal_reviewer_proposal_id_foreign FOREIGN KEY (proposal_id) REFERENCES proposals (id) ON DELETE CASCADE,
                CONSTRAINT proposal_reviewer_user_id_foreign FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            )');

            // 2. Copy data from new table to old table
            DB::statement('INSERT INTO proposal_reviewer_old SELECT * FROM proposal_reviewer');

            // 3. Drop new table
            Schema::dropIfExists('proposal_reviewer');

            // 4. Rename old table
            DB::statement('ALTER TABLE proposal_reviewer_old RENAME TO proposal_reviewer');

            Schema::enableForeignKeyConstraints();
        }
    }
};
