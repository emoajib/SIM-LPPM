<?php

use App\Enums\OutputStatusType;
use Database\Helpers\MigrationHelpers;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Update status_type enum to match BIMA 2025/2026 status values:
     * - draft: Masih dalam penyusunan
     * - submitted: Sudah disubmit ke jurnal
     * - under_review: Sedang direview
     * - accepted: Diterima
     * - published: Sudah terbit
     * - rejected: Ditolak
     */
    public function up(): void
    {
        MigrationHelpers::dropCheckConstraint('mandatory_outputs', 'mandatory_outputs_status_type_check');

        Schema::table('mandatory_outputs', function (Blueprint $table) {
            $table->string('status_type', 50)->nullable()->comment('Publication status (BIMA 2025/2026)')->change();
        });

        MigrationHelpers::addCheckConstraintToTable(
            'mandatory_outputs',
            'status_type',
            OutputStatusType::values(),
            MigrationHelpers::generateConstraintName('mandatory_outputs', 'status_type')
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum values
        MigrationHelpers::dropCheckConstraint('mandatory_outputs', 'mandatory_outputs_status_type_check');

        Schema::table('mandatory_outputs', function (Blueprint $table) {
            $table->string('status_type', 50)->nullable(false)->comment('Publication status')->change();
        });

        $oldValues = ['published', 'accepted', 'under_review', 'rejected'];
        MigrationHelpers::addCheckConstraintToTable(
            'mandatory_outputs',
            'status_type',
            $oldValues,
            MigrationHelpers::generateConstraintName('mandatory_outputs', 'status_type')
        );
    }
};
