<?php

use App\Enums\LetterStatus;
use App\Enums\SignatureMode;
use Database\Helpers\MigrationHelpers;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        MigrationHelpers::addCheckConstraintToTable(
            'letters',
            'status',
            LetterStatus::values(),
            MigrationHelpers::generateConstraintName('letters', 'status')
        );

        MigrationHelpers::addCheckConstraintToTable(
            'letters',
            'signature_mode',
            SignatureMode::values(),
            MigrationHelpers::generateConstraintName('letters', 'signature_mode')
        );
    }

    public function down(): void
    {
        MigrationHelpers::dropCheckConstraint(
            'letters',
            MigrationHelpers::generateConstraintName('letters', 'status')
        );

        MigrationHelpers::dropCheckConstraint(
            'letters',
            MigrationHelpers::generateConstraintName('letters', 'signature_mode')
        );
    }
};
