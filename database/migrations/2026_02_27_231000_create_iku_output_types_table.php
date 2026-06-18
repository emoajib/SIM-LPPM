<?php

use App\Enums\IkuOutputTypeGroup;
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
        Schema::create('iku_output_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('group', 50);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Add CHECK constraint for enum column
        MigrationHelpers::addCheckConstraintToTable(
            'iku_output_types',
            'group',
            IkuOutputTypeGroup::values(),
            MigrationHelpers::generateConstraintName('iku_output_types', 'group')
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('iku_output_types');
    }
};
