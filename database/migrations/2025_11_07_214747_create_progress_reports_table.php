<?php

use App\Enums\ReportingPeriod;
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
        Schema::create('progress_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('proposal_id')->comment('Proposal')->constrained()->onDelete('cascade');
            $table->text('summary_update')->nullable()->comment('Updated summary');
            $table->integer('reporting_year')->comment('Tahun pelaporan');
            $table->string('reporting_period', 50)->comment('Periode pelaporan');
            $table->string('status', 50)->default('draft')->comment('Status laporan');
            $table->foreignUuid('submitted_by')->nullable()->comment('User who submitted')->constrained('users');
            $table->timestamp('submitted_at')->nullable()->comment('Submission timestamp');
            $table->timestamps();

            $table->index(['proposal_id', 'reporting_year']);
        });

        // Add CHECK constraints for enum columns
        MigrationHelpers::addCheckConstraintToTable(
            'progress_reports',
            'reporting_period',
            ReportingPeriod::values(),
            MigrationHelpers::generateConstraintName('progress_reports', 'reporting_period')
        );

        MigrationHelpers::addCheckConstraintToTable(
            'progress_reports',
            'status',
            ['draft', 'submitted', 'approved'], // ProgressReportStatus values (hardcoded — enum removed)
            MigrationHelpers::generateConstraintName('progress_reports', 'status')
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('progress_reports');
    }
};
