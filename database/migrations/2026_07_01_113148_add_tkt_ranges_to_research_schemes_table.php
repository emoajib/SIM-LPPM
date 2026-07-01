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
        Schema::table('research_schemes', function (Blueprint $table) {
            $table->unsignedTinyInteger('min_tkt')->nullable()->after('strata');
            $table->unsignedTinyInteger('max_tkt')->nullable()->after('min_tkt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('research_schemes', function (Blueprint $table) {
            $table->dropColumn(['min_tkt', 'max_tkt']);
        });
    }
};
