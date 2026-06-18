<?php

use App\Enums\ReviewStatus;
use Database\Helpers\MigrationHelpers;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration does THREE things:
     *   A) Data migration: 'reviewing' → 'pending' (temporary safety step)
     *   B) Schema changes: add round, assigned_at, deadline_at, started_at, completed_at columns + indexes
     *   C) Replace CHECK constraint on status to match ReviewStatus enum values
     */
    public function up(): void
    {
        // === PART A: Data migration — normalize legacy status ===
        DB::table('proposal_reviewer')
            ->where('status', 'reviewing')
            ->update(['status' => 'pending']);

        // === PART B: Schema changes — add columns + indexes ===
        Schema::table('proposal_reviewer', function (Blueprint $table) {
            // Add round tracking for revision cycles
            $table->integer('round')->unsigned()->default(1)->after('recommendation')
                ->comment('Review round/cycle number');

            // Add timestamp tracking
            $table->timestamp('assigned_at')->nullable()->after('round')
                ->comment('When reviewer was assigned');
            $table->timestamp('deadline_at')->nullable()->after('assigned_at')
                ->comment('Review deadline');
            $table->timestamp('started_at')->nullable()->after('deadline_at')
                ->comment('When reviewer started reviewing');
            $table->timestamp('completed_at')->nullable()->after('started_at')
                ->comment('When review was completed');

            // Add index for deadline queries
            $table->index(['deadline_at']);
            $table->index(['round']);
        });

        // === PART C: Replace CHECK constraint with ReviewStatus enum values ===
        MigrationHelpers::dropCheckConstraint('proposal_reviewer', MigrationHelpers::generateConstraintName('proposal_reviewer', 'status'));

        MigrationHelpers::addCheckConstraintToTable(
            'proposal_reviewer',
            'status',
            ReviewStatus::values(),
            MigrationHelpers::generateConstraintName('proposal_reviewer', 'status')
        );

        // Set assigned_at to created_at for existing records
        DB::table('proposal_reviewer')
            ->whereNull('assigned_at')
            ->update(['assigned_at' => DB::raw('created_at')]);

        // Set completed_at to updated_at for completed reviews
        DB::table('proposal_reviewer')
            ->where('status', 'completed')
            ->whereNull('completed_at')
            ->update(['completed_at' => DB::raw('updated_at')]);
    }

    /**
     * Reverse the migrations.
     *
     * Urutan rollback yang BENAR:
     *   1. Bersihkan nilai status yang tidak ada di old constraint
     *   2. Drop constraint saat ini (ReviewStatus: pending, in_progress, completed, re_review_requested)
     *   3. Restore old constraint (pending, reviewing, completed) — 'reviewing' valid secara constraint
     *      meski tidak ada data-nya (sudah di-UPDATE ke 'pending' di up())
     *   4. Drop indexes & columns (round, timestamp columns)
     *
     * SQLite note: Migration ini tidak menangani SQLite karena
     * '2026_02_18_132723_fix_sqlite_proposal_reviewer_enum.php' sudah menangani
     * table-recreation untuk SQLite secara terpisah. Rollback di SQLite tidak
     * didukung dan diasumsikan tidak dijalankan di production.
     *
     * Vetted by AI - Manual Review Required by Senior Engineer/Manager
     */
    public function down(): void
    {
        // === Step 1: Bersihkan nilai yang tidak ada di old constraint ===
        // in_progress & re_review_requested tidak ada di old constraint lama,
        // sehingga harus di-map ke 'pending' agar constraint lama bisa diterapkan.
        DB::table('proposal_reviewer')
            ->whereIn('status', ['in_progress', 're_review_requested'])
            ->update(['status' => 'pending']);

        // === Step 2 & 3: Ganti constraint ===
        // Drop constraint saat ini (ReviewStatus), kemudian restore old constraint.
        // 'reviewing' dimasukkan kembali ke constraint meski tidak ada data-nya —
        // ini merepresentasikan state schema sebelum migration ini dijalankan.
        MigrationHelpers::dropCheckConstraint('proposal_reviewer', MigrationHelpers::generateConstraintName('proposal_reviewer', 'status'));

        MigrationHelpers::addCheckConstraintToTable(
            'proposal_reviewer',
            'status',
            ['pending', 'reviewing', 'completed'],
            MigrationHelpers::generateConstraintName('proposal_reviewer', 'status')
        );

        // === Step 4: Drop indexes & columns dari Part B ===
        // dropIndex harus dilakukan SEBELUM dropColumn agar tidak ada orphan index.
        // Index name otomatis: proposal_reviewer_deadline_at_index, proposal_reviewer_round_index
        Schema::table('proposal_reviewer', function (Blueprint $table) {
            $table->dropIndex(['deadline_at']);
            $table->dropIndex(['round']);
            $table->dropColumn(['round', 'assigned_at', 'deadline_at', 'started_at', 'completed_at']);
        });
    }
};
