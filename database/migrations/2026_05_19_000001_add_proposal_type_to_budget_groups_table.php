<?php

use App\Enums\BudgetGroupPercentageType;
use App\Enums\BudgetGroupProposalType;
use Database\Helpers\MigrationHelpers;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_groups', function (Blueprint $table) {
            $table->string('proposal_type', 50)
                ->nullable()
                ->after('percentage')
                ->comment('Tipe proposal: research, community_service, atau null (keduanya)');

            $table->string('percentage_type', 50)
                ->nullable()
                ->after('proposal_type')
                ->comment('Tipe batasan persentase: min (minimal) atau max (maksimal)');

            $table->boolean('is_active')
                ->default(true)
                ->after('percentage_type')
                ->comment('Status aktif/nonaktif kelompok anggaran');
        });

        MigrationHelpers::addCheckConstraintToTable(
            'budget_groups',
            'proposal_type',
            BudgetGroupProposalType::values(),
            MigrationHelpers::generateConstraintName('budget_groups', 'proposal_type')
        );

        MigrationHelpers::addCheckConstraintToTable(
            'budget_groups',
            'percentage_type',
            BudgetGroupPercentageType::values(),
            MigrationHelpers::generateConstraintName('budget_groups', 'percentage_type')
        );
    }

    public function down(): void
    {
        MigrationHelpers::dropCheckConstraint('budget_groups', 'budget_groups_proposal_type_check');
        MigrationHelpers::dropCheckConstraint('budget_groups', 'budget_groups_percentage_type_check');

        Schema::table('budget_groups', function (Blueprint $table) {
            $table->dropColumn(['proposal_type', 'percentage_type', 'is_active']);
        });
    }
};
