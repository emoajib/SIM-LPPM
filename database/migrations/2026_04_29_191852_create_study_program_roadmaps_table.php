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
        Schema::create('study_program_roadmaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('faculty_roadmap_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->year('period_start');
            $table->year('period_end');
            $table->text('vision')->nullable();
            $table->json('research_tree')->nullable();
            $table->text('cpl_alignment')->nullable();
            $table->smallInteger('tkt_target_min')->default(1);
            $table->smallInteger('tkt_target_max')->default(9);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('study_program_roadmaps');
    }
};
