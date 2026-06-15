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
        if (! Schema::hasColumn('letters', 'team_source')) {
            Schema::table('letters', function (Blueprint $table) {
                $table->enum('team_source', ['proposal', 'manual'])->default('proposal')->after('source');
            });
        }

        Schema::table('letters', function (Blueprint $table) {
            $table->index(['source', 'reference_type', 'reference_id']);
            $table->index(['source', 'team_source']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('letters', function (Blueprint $table) {
            $table->dropIndex(['source', 'reference_type', 'reference_id']);
            $table->dropIndex(['source', 'team_source']);
        });

        if (Schema::hasColumn('letters', 'team_source')) {
            Schema::table('letters', function (Blueprint $table) {
                $table->dropColumn('team_source');
            });
        }
    }
};
