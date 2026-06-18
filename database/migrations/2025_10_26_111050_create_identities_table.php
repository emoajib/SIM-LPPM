<?php

use App\Enums\IdentityType;
use Database\Helpers\MigrationHelpers;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('identities', function (Blueprint $table) {
            $table->id();
            $table->string('identity_id')->unique()->comment('NIDN / NIM');
            $table->foreignUuid('user_id')->comment('User')->constrained('users')->onDelete('cascade');
            $table->string('sinta_id')->nullable()->comment('ID SINTA');
            $table->string('type', 50)->comment('Tipe User');
            $table->string('address')->nullable()->comment('Alamat');
            $table->date('birthdate')->nullable()->comment('Tanggal Lahir');
            $table->string('birthplace')->nullable()->comment('Tempat Lahir');
            $table->foreignId('institution_id')->nullable()->comment('Institusi')->constrained('institutions')->onDelete('set null');
            $table->foreignId('study_program_id')->nullable()->comment('Program Studi')->constrained('study_programs')->onDelete('set null');
            $table->string('profile_picture')->nullable()->comment('Foto Profil');
            $table->foreignId('faculty_id')->nullable()->comment('Fakultas')->constrained('faculties')->onDelete('set null');
            $table->timestamps();
        });

        // Add CHECK constraint for enum column
        MigrationHelpers::addCheckConstraintToTable(
            'identities',
            'type',
            IdentityType::values(),
            MigrationHelpers::generateConstraintName('identities', 'type')
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('identities');
    }
};
