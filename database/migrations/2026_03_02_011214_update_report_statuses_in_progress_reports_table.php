<?php

use App\Enums\ReportStatus;
use Database\Helpers\MigrationHelpers;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Normalize the progress_reports.status column from UPPERCASE ('DRAFT', 'SUBMITTED',
     * 'APPROVED', 'REJECTED') to lowercase ('draft', 'submitted', 'approved', 'rejected'),
     * matching the casing used by the ReportStatus enum. The value 'approved_by_dekan'
     * was already lowercase and is kept unchanged.
     */
    public function up(): void
    {
        // === Data migration: normalize UPPERCASE values to lowercase ===
        $oldValues = ['DRAFT', 'SUBMITTED', 'approved_by_dekan', 'APPROVED', 'REJECTED'];
        $newValues = ['draft', 'submitted', 'approved_by_dekan', 'approved', 'rejected'];

        foreach (array_combine($oldValues, $newValues) as $old => $new) {
            if ($old !== $new) {
                DB::statement('UPDATE progress_reports SET status = ? WHERE status = ?', [$new, $old]);
            }
        }

        // === Replace CHECK constraint with lowercase values ===
        MigrationHelpers::dropCheckConstraint('progress_reports', MigrationHelpers::generateConstraintName('progress_reports', 'status'));

        MigrationHelpers::addCheckConstraintToTable(
            'progress_reports',
            'status',
            ReportStatus::values(),
            MigrationHelpers::generateConstraintName('progress_reports', 'status')
        );
    }

    /**
     * Reverse the migrations.
     *
     * Restore UPPERCASE values and the original CHECK constraint.
     *
     * Urutan yang benar: drop constraint DULU, baru UPDATE data, lalu add constraint lama.
     * Ini memastikan UPDATE tidak terjadi saat constraint lowercase masih aktif
     * (meski secara teknis UPDATE ke UPPERCASE tidak melanggar constraint lowercase,
     * urutan ini lebih aman dan eksplisit sebagai best practice).
     */
    public function down(): void
    {
        // === Step 1: Drop constraint lowercase yang aktif TERLEBIH DAHULU ===
        MigrationHelpers::dropCheckConstraint('progress_reports', MigrationHelpers::generateConstraintName('progress_reports', 'status'));

        // === Step 2: Restore data ke UPPERCASE ===
        // Catatan: 'approved_by_dekan' TIDAK diubah karena nilai ini sudah lowercase
        // bahkan di versi lama (sebelum migration ini). Ia tetap 'approved_by_dekan'.
        $restoreMap = [
            'draft' => 'DRAFT',
            'submitted' => 'SUBMITTED',
            'approved' => 'APPROVED',
            'rejected' => 'REJECTED',
        ];
        foreach ($restoreMap as $current => $original) {
            DB::statement('UPDATE progress_reports SET status = ? WHERE status = ?', [$original, $current]);
        }

        // === Step 3: Restore old CHECK constraint dengan nilai UPPERCASE + approved_by_dekan (lowercase) ===
        MigrationHelpers::addCheckConstraintToTable(
            'progress_reports',
            'status',
            ['DRAFT', 'SUBMITTED', 'approved_by_dekan', 'APPROVED', 'REJECTED'],
            MigrationHelpers::generateConstraintName('progress_reports', 'status')
        );
    }
};
