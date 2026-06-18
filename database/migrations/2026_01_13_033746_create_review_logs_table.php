<?php

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
        Schema::create('review_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_reviewer_id')
                ->comment('Reference to the reviewer assignment')
                ->constrained('proposal_reviewer')
                ->onDelete('cascade');
            $table->foreignUuid('proposal_id')
                ->comment('Proposal being reviewed')
                ->constrained('proposals')
                ->onDelete('cascade');
            $table->foreignUuid('user_id')
                ->comment('Reviewer user')
                ->constrained('users')
                ->onDelete('cascade');
            $table->integer('round')->unsigned()
                ->default(1)
                ->comment('Review round/cycle number');
            $table->text('review_notes')
                ->nullable()
                ->comment('Reviewer feedback and comments');
            $table->enum('recommendation', ['approved', 'rejected', 'revision_needed'])
                ->nullable()
                ->comment('Reviewer recommendation');
            $table->timestamp('started_at')
                ->nullable()
                ->comment('When reviewer started this round');
            $table->timestamp('completed_at')
                ->nullable()
                ->comment('When review was completed');
            $table->timestamps();

            // Indexes for common queries
            $table->index(['proposal_id', 'round']);
            $table->index(['user_id', 'round']);
            $table->index(['proposal_reviewer_id', 'round']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_logs');
    }
};
