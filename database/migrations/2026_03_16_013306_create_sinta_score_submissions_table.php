<?php

use App\Enums\SintaScoreSubmissionStatus;
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
        Schema::create('sinta_score_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('identity_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');

            $table->float('sinta_score_v3_overall')->nullable();
            $table->float('sinta_score_v3_3yr')->nullable();
            $table->integer('scopus_h_index')->nullable();
            $table->integer('gs_h_index')->nullable();
            $table->integer('wos_h_index')->nullable();

            $table->string('status', 50)->default('pending');
            $table->foreignUuid('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_notes')->nullable();
            $table->text('submission_notes')->nullable();
            $table->text('rejected_reason')->nullable();

            $table->timestamps();
        });

        // Add CHECK constraint for enum column
        MigrationHelpers::addCheckConstraintToTable(
            'sinta_score_submissions',
            'status',
            SintaScoreSubmissionStatus::values(),
            MigrationHelpers::generateConstraintName('sinta_score_submissions', 'status')
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sinta_score_submissions');
    }
};
