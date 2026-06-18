<?php

use App\Enums\ProgressReportStatus;
use App\Enums\ReportStatus;
use Database\Helpers\MigrationHelpers;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Update the status column to include all ReportStatus enum values
        MigrationHelpers::dropCheckConstraint('progress_reports', 'progress_reports_status_check');

        Schema::table('progress_reports', function (Blueprint $table) {
            $table->string('status', 50)->default('draft')->comment('Status laporan')->change();
        });

        MigrationHelpers::addCheckConstraintToTable(
            'progress_reports',
            'status',
            ReportStatus::values(),
            MigrationHelpers::generateConstraintName('progress_reports', 'status')
        );
    }

    public function down(): void
    {
        MigrationHelpers::dropCheckConstraint('progress_reports', 'progress_reports_status_check');

        Schema::table('progress_reports', function (Blueprint $table) {
            $table->string('status', 50)->default('draft')->comment('Status laporan')->change();
        });

        MigrationHelpers::addCheckConstraintToTable(
            'progress_reports',
            'status',
            ProgressReportStatus::values(),
            MigrationHelpers::generateConstraintName('progress_reports', 'status')
        );
    }
};
