<?php

use App\Enums\ProposalUserRole;
use App\Enums\ProposalUserStatus;
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
        Schema::create('proposal_user', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('proposal_id')->comment('Proposal')->constrained('proposals')->onDelete('cascade');
            $table->foreignUuid('user_id')->comment('Anggota Tim')->constrained('users')->onDelete('cascade');
            $table->string('role', 50)->default('anggota')->comment('Peran dalam Tim');
            $table->string('status', 50)->default('pending')->comment('Status Persetujuan Anggota');
            $table->text('tasks')->nullable()->comment('Bidang Tugas');
            $table->timestamps();
        });

        // Add CHECK constraints for enum columns
        MigrationHelpers::addCheckConstraintToTable(
            'proposal_user',
            'role',
            ProposalUserRole::values(),
            MigrationHelpers::generateConstraintName('proposal_user', 'role')
        );

        MigrationHelpers::addCheckConstraintToTable(
            'proposal_user',
            'status',
            ProposalUserStatus::values(),
            MigrationHelpers::generateConstraintName('proposal_user', 'status')
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposal_user');
    }
};
