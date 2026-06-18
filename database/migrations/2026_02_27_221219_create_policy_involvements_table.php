<?php

use App\Enums\PolicyInvolvementLevel;
use App\Enums\PolicyInvolvementStatus;
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
        Schema::create('policy_involvements', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->string('organization');
            $table->string('level', 50)->default('Nasional');
            $table->string('role')->nullable();
            $table->date('date');
            $table->string('status', 50)->default('pending');
            $table->text('description')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignUuid('verified_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        // Add CHECK constraints for enum columns
        MigrationHelpers::addCheckConstraintToTable(
            'policy_involvements',
            'level',
            PolicyInvolvementLevel::values(),
            MigrationHelpers::generateConstraintName('policy_involvements', 'level')
        );

        MigrationHelpers::addCheckConstraintToTable(
            'policy_involvements',
            'status',
            PolicyInvolvementStatus::values(),
            MigrationHelpers::generateConstraintName('policy_involvements', 'status')
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policy_involvements');
    }
};
