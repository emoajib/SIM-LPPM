<?php

use App\Enums\AdditionalOutputStatusType;
use Database\Helpers\MigrationHelpers;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Update status enum to match BIMA 2025/2026 status values:
     * - draft: Masih dalam penyusunan
     * - submitted: Sudah disubmit ke jurnal
     * - under_review: Sedang direview
     * - accepted: Diterima
     * - published: Sudah terbit
     * - rejected: Ditolak
     */
    public function up(): void
    {
        MigrationHelpers::dropCheckConstraint('additional_outputs', 'additional_outputs_status_check');

        // Data migration: map old values to new ones
        // 'review'    -> 'under_review'
        // 'editing'   -> 'draft'
        // 'published' -> 'published' (stays the same)
        DB::table('additional_outputs')
            ->where('status', 'review')
            ->update(['status' => 'under_review']);

        DB::table('additional_outputs')
            ->where('status', 'editing')
            ->update(['status' => 'draft']);

        Schema::table('additional_outputs', function (Blueprint $table) {
            $table->string('status', 50)->nullable()->comment('Publication status (BIMA 2025/2026)')->change();
        });

        MigrationHelpers::addCheckConstraintToTable(
            'additional_outputs',
            'status',
            AdditionalOutputStatusType::values(),
            MigrationHelpers::generateConstraintName('additional_outputs', 'status')
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        MigrationHelpers::dropCheckConstraint('additional_outputs', 'additional_outputs_status_check');

        // Map new values back to old (best effort — some data fidelity is lost)
        DB::table('additional_outputs')
            ->whereIn('status', ['draft', 'submitted', 'under_review', 'accepted', 'rejected'])
            ->update(['status' => 'review']);

        Schema::table('additional_outputs', function (Blueprint $table) {
            $table->string('status', 50)->nullable(false)->comment('Publication status')->change();
        });

        $oldValues = ['review', 'editing', 'published'];
        MigrationHelpers::addCheckConstraintToTable(
            'additional_outputs',
            'status',
            $oldValues,
            MigrationHelpers::generateConstraintName('additional_outputs', 'status')
        );
    }
};
