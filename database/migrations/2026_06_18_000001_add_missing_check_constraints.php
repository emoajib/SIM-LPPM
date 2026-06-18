<?php

use App\Enums\InstitutionalReportStatus;
use App\Enums\KaprodiStatus;
use App\Enums\SignatureMode;
use Database\Helpers\MigrationHelpers;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
            'proposal_kaprodi_approvals',
            'status',
            KaprodiStatus::values(),
            MigrationHelpers::generateConstraintName('proposal_kaprodi_approvals', 'status')
        );

        if (! Schema::hasColumn('document_signatures', 'mode')) {
            Schema::table('document_signatures', function (Blueprint $table) {
                $table->string('mode', 50)->default('tte')->after('action');
            });
        }

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
        MigrationHelpers::dropCheckConstraint('proposal_kaprodi_approvals', 'status');
        MigrationHelpers::dropCheckConstraint('document_signatures', 'mode');

        if (Schema::hasColumn('document_signatures', 'mode')) {
            Schema::table('document_signatures', function (Blueprint $table) {
                $table->dropColumn('mode');
            });
        }
    }
};
