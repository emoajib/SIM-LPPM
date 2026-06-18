<?php

use App\Enums\ReviewRecommendation;
use App\Enums\ReviewStatus;
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
        Schema::create('proposal_reviewer', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('proposal_id')->comment('Proposal')->constrained('proposals')->onDelete('cascade');
            $table->foreignUuid('user_id')->comment('Reviewer')->constrained('users')->onDelete('cascade');
            $table->string('status', 50)->default(ReviewStatus::PENDING->value)->comment('Status Review');
            $table->text('review_notes')->nullable()->comment('Catatan Review');
            $table->string('recommendation', 50)->nullable()->comment('Rekomendasi Reviewer');
            $table->timestamps();

            $table->unique(['proposal_id', 'user_id']);
            $table->index(['proposal_id']);
            $table->index(['user_id']);
        });

        // Add CHECK constraints for enum columns
        MigrationHelpers::addCheckConstraintToTable(
            'proposal_reviewer',
            'status',
            ReviewStatus::values(),
            MigrationHelpers::generateConstraintName('proposal_reviewer', 'status')
        );

        MigrationHelpers::addCheckConstraintToTable(
            'proposal_reviewer',
            'recommendation',
            ReviewRecommendation::values(),
            MigrationHelpers::generateConstraintName('proposal_reviewer', 'recommendation')
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposal_reviewer');
    }
};
