<?php

use App\Enums\MonevReviewSemester;
use App\Enums\MonevReviewStatus;
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
        Schema::create('monev_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('proposal_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('reviewer_id')->constrained('users')->cascadeOnDelete();

            // Evaluation data
            $table->float('score')->default(0);
            $table->string('status', 50)->nullable();
            $table->text('notes')->nullable();
            $table->json('borang_data')->nullable(); // Store digital form criteria/scores

            // Period tracking
            $table->string('academic_year'); // e.g., "2025/2026"
            $table->string('semester', 50);

            // Reporting chain
            $table->timestamp('finalized_by_lppm_at')->nullable();
            $table->timestamp('approved_by_kepala_at')->nullable();
            $table->timestamp('reported_to_rektor_at')->nullable();

            $table->timestamps();
        });

        // Add CHECK constraints for enum columns
        MigrationHelpers::addCheckConstraintToTable(
            'monev_reviews',
            'status',
            MonevReviewStatus::values(),
            MigrationHelpers::generateConstraintName('monev_reviews', 'status')
        );

        MigrationHelpers::addCheckConstraintToTable(
            'monev_reviews',
            'semester',
            MonevReviewSemester::values(),
            MigrationHelpers::generateConstraintName('monev_reviews', 'semester')
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monev_reviews');
    }
};
