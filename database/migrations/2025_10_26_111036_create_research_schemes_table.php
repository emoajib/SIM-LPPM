<?php

use App\Enums\ResearchSchemeStrata;
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
        Schema::create('research_schemes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Nama Skema Penelitian');
            $table->string('strata', 50)->comment('Strata Penelitian/PKM');
            $table->text('description')->nullable()->comment('Deskripsi skema penelitian/pengabdian');
            $table->timestamps();
        });

        // Add CHECK constraint for enum column
        MigrationHelpers::addCheckConstraintToTable(
            'research_schemes',
            'strata',
            ResearchSchemeStrata::values(),
            MigrationHelpers::generateConstraintName('research_schemes', 'strata')
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('research_schemes');
    }
};
