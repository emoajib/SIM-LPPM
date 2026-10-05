<?php

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
        Schema::table('proposals', function (Blueprint $table) {
            $table->text('logbook_rejection_notes')->nullable()->comment('Catatan pengembalian LPJ ke dosen oleh Kepala LPPM/Admin LPPM');
            $table->foreignUuid('logbook_rejected_by')->nullable()->comment('Pengembali LPJ')->constrained('users')->onDelete('set null');
            $table->timestamp('logbook_rejected_at')->nullable()->comment('Waktu pengembalian LPJ ke dosen');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropForeign(['logbook_rejected_by']);
            $table->dropColumn(['logbook_rejection_notes', 'logbook_rejected_by', 'logbook_rejected_at']);
        });
    }
};
