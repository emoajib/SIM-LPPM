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
        Schema::create('research', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('macro_research_group_id')->nullable()->constrained('macro_research_groups')->nullOnDelete();
            $table->integer('final_tkt_target')->nullable()->comment('Target TKT Akhir');
            $table->text('background')->nullable()->comment('Latar Belakang');
            $table->text('state_of_the_art')->nullable()->comment('State of the Art');
            $table->text('methodology')->nullable()->comment('Metodologi');
            $table->string('substance_file')->nullable();
            $table->json('roadmap_data')->nullable()->comment('Data Roadmap');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('research');
    }
};
