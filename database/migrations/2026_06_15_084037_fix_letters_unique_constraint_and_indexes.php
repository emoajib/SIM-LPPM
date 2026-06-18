<?php

use App\Enums\TeamSource;
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
        if (! Schema::hasColumn('letters', 'team_source')) {
            Schema::table('letters', function (Blueprint $table) {
                $table->string('team_source', 50)->default('proposal')->after('source');
            });

            MigrationHelpers::addCheckConstraintToTable(
                'letters',
                'team_source',
                TeamSource::values(),
                MigrationHelpers::generateConstraintName('letters', 'team_source')
            );
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
            MigrationHelpers::dropCheckConstraint('letters', 'letters_team_source_check');

            Schema::table('letters', function (Blueprint $table) {
                $table->dropColumn('team_source');
            });
        }
    }
};
