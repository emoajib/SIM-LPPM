<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Vetted by AI - Manual Review Required by Senior Engineer/Manager
     */
    public function up(): void
    {
        Schema::table('identities', function (Blueprint $table) {
            $table->enum('gender', ['L', 'P'])->nullable()->after('type')->comment('Jenis Kelamin: L = Laki-laki, P = Perempuan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('identities', function (Blueprint $table) {
            $table->dropColumn('gender');
        });
    }
};
