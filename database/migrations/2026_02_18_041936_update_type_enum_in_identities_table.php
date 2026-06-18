<?php

use App\Enums\IdentityType;
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
        MigrationHelpers::dropCheckConstraint('identities', 'identities_type_check');

        Schema::table('identities', function (Blueprint $table) {
            $table->string('type', 50)->comment('Tipe User')->change();
        });

        MigrationHelpers::addCheckConstraintToTable(
            'identities',
            'type',
            IdentityType::values(),
            MigrationHelpers::generateConstraintName('identities', 'type')
        );
    }

    public function down(): void
    {
        // Cannot easily revert without data migration that could lose 'reviewer'/'tendik' data
        // Best-effort revert: restore original CHECK constraint with old values
        MigrationHelpers::dropCheckConstraint('identities', 'identities_type_check');

        Schema::table('identities', function (Blueprint $table) {
            $table->string('type', 50)->change();
        });

        MigrationHelpers::addCheckConstraintToTable(
            'identities',
            'type',
            ['dosen', 'mahasiswa'],
            MigrationHelpers::generateConstraintName('identities', 'type')
        );
    }
};
