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
        Schema::create('letters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('letter_number')->nullable()->unique();
            $table->foreignId('letter_type_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            
            // Polymorphic relation to Proposal or ProposalReviewer
            $table->string('reference_type');
            $table->uuid('reference_id');
            $table->index(['reference_type', 'reference_id']);

            $table->string('signature_mode'); // tte, manual
            $table->string('status'); // draft, pending_verification, pending_approval, ready_to_print, published, rejected
            
            $table->json('metadata')->nullable();
            $table->json('team_snapshot')->nullable();
            $table->string('file_path')->nullable();
            $table->boolean('is_stamped')->default(false);
            
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('letters');
    }
};
