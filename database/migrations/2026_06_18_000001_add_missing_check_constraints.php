<?php

use App\Enums\InstitutionalReportStatus;
use App\Enums\KaprodiStatus;
use App\Enums\SignatureMode;
use Database\Helpers\MigrationHelpers;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        MigrationHelpers::addCheckConstraintToTable(
            'institutional_reports',
            'status',
            InstitutionalReportStatus::values(),
            MigrationHelpers::generateConstraintName('institutional_reports', 'status')
        );

        MigrationHelpers::addCheckConstraintToTable(
            'kaprodi_approvals',
            'status',
            KaprodiStatus::values(),
            MigrationHelpers::generateConstraintName('kaprodi_approvals', 'status')
        );

        MigrationHelpers::addCheckConstraintToTable(
            'document_signatures',
            'mode',
            SignatureMode::values(),
            MigrationHelpers::generateConstraintName('document_signatures', 'mode')
        );
    }

    public function down(): void
    {
        MigrationHelpers::dropCheckConstraint('institutional_reports', 'status');
        MigrationHelpers::dropCheckConstraint('kaprodi_approvals', 'status');
        MigrationHelpers::dropCheckConstraint('document_signatures', 'mode');
    }
};
