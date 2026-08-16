<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Vetted by AI - Manual Review Required by Senior Engineer/Manager
     */
    public function up(): void
    {
        Schema::table('progress_reports', function (Blueprint $table) {
            $table->text('proposed_title')->nullable()->after('summary_update');
            $table->text('title_change_reason')->nullable()->after('proposed_title');
            $table->string('title_change_status', 20)->nullable()->after('title_change_reason'); // pending, approved, rejected
            $table->dateTime('title_change_reviewed_at')->nullable()->after('title_change_status');
            $table->foreignUuid('title_change_reviewer_id')->nullable()->after('title_change_reviewed_at')->constrained('users')->nullOnDelete();
            $table->text('title_change_review_notes')->nullable()->after('title_change_reviewer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('progress_reports', function (Blueprint $table) {
            $table->dropForeign(['title_change_reviewer_id']);
            $table->dropColumn([
                'proposed_title',
                'title_change_reason',
                'title_change_status',
                'title_change_reviewed_at',
                'title_change_reviewer_id',
                'title_change_review_notes',
            ]);
        });
    }
};
