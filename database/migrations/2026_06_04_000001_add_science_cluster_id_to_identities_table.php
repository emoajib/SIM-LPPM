<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('identities', function (Blueprint $table) {
            $table->foreignId('science_cluster_id')
                ->nullable()
                ->after('study_program_id')
                ->constrained('science_clusters')
                ->nullOnDelete()
                ->comment('Rumpun Ilmu Dosen');
        });
    }

    public function down(): void
    {
        Schema::table('identities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('science_cluster_id');
        });
    }
};
