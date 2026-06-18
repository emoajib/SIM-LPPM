<?php

use App\Enums\StrataCategory;
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
        Schema::create('strata', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category', 50);
            $table->timestamps();
        });

        // Add CHECK constraint for enum column
        MigrationHelpers::addCheckConstraintToTable(
            'strata',
            'category',
            StrataCategory::values(),
            MigrationHelpers::generateConstraintName('strata', 'category')
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('strata');
    }
};
