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
        Schema::create('science_clusters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->comment('Parent Rumpun Ilmu (Self-reference)')->constrained('science_clusters')->onDelete('cascade');
            $table->smallInteger('level')->comment('Level 1, 2, or 3');
            $table->string('name')->comment('Nama Rumpun Ilmu');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('science_clusters');
    }
};
