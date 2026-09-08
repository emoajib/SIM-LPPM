<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('progress_reports', function (Blueprint $table) {
            // Catatan penolakan dari Dekan atau Kepala LPPM
            $table->text('rejection_notes')->nullable()->after('status');
            // Siapa yang menolak (UUID user)
            $table->string('rejected_by')->nullable()->after('rejection_notes');
            // Kapan ditolak
            $table->timestamp('rejected_at')->nullable()->after('rejected_by');
        });
    }

    public function down(): void
    {
        Schema::table('progress_reports', function (Blueprint $table) {
            $table->dropColumn(['rejection_notes', 'rejected_by', 'rejected_at']);
        });
    }
};
