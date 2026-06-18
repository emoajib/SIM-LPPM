<?php

use App\Enums\ProposalStatus;
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
        Schema::create('proposal_status_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('proposal_id');
            $table->uuid('user_id');
            $table->string('status_before', 50);
            $table->string('status_after', 50);
            $table->text('body')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('at');
            $table->timestamps();

            $table->foreign('proposal_id')
                ->references('id')
                ->on('proposals')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->index(['proposal_id', 'user_id']);
            $table->index('at');
        });

        // Add CHECK constraints for enum columns
        MigrationHelpers::addCheckConstraintToTable(
            'proposal_status_logs',
            'status_before',
            ProposalStatus::values(),
            MigrationHelpers::generateConstraintName('proposal_status_logs', 'status_before')
        );

        MigrationHelpers::addCheckConstraintToTable(
            'proposal_status_logs',
            'status_after',
            ProposalStatus::values(),
            MigrationHelpers::generateConstraintName('proposal_status_logs', 'status_after')
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposal_status_logs');
    }
};
