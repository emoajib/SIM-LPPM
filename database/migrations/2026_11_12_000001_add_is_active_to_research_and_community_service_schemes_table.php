<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('research_schemes', 'is_active')) {
            Schema::table('research_schemes', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('description');
            });
        }
        if (! Schema::hasColumn('community_service_schemes', 'is_active')) {
            Schema::table('community_service_schemes', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('strata');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('research_schemes', 'is_active')) {
            Schema::table('research_schemes', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
        if (Schema::hasColumn('community_service_schemes', 'is_active')) {
            Schema::table('community_service_schemes', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }
};
