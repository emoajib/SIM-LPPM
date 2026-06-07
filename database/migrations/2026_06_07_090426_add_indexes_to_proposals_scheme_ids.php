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
        Schema::table('proposals', function (Blueprint $table) {
            // Individual indexes for scheme foreign keys
            $table->index('research_scheme_id');
            $table->index('community_service_scheme_id');

            // Composite indexes for common filter combinations
            $table->index(['detailable_type', 'research_scheme_id', 'start_year'], 'idx_proposals_type_research_scheme_year');
            $table->index(['detailable_type', 'community_service_scheme_id', 'start_year'], 'idx_proposals_type_cs_scheme_year');
            $table->index(['start_year', 'status', 'detailable_type'], 'idx_proposals_year_status_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropIndex(['research_scheme_id']);
            $table->dropIndex(['community_service_scheme_id']);
            $table->dropIndex('idx_proposals_type_research_scheme_year');
            $table->dropIndex('idx_proposals_type_cs_scheme_year');
            $table->dropIndex('idx_proposals_year_status_type');
        });
    }
};
