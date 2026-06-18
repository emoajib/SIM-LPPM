<?php

use App\Enums\ProposalUserRole;
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
        Schema::table('proposal_user', function (Blueprint $table) {
            $table->string('role', 50)->default('anggota')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proposal_user', function (Blueprint $table) {
            $table->string('role', 50)->default('anggota')->change();
        });

        MigrationHelpers::dropCheckConstraint('proposal_user', 'proposal_user_role_check');
        MigrationHelpers::addCheckConstraintToTable(
            'proposal_user',
            'role',
            ProposalUserRole::values(),
            MigrationHelpers::generateConstraintName('proposal_user', 'role')
        );
    }
};
