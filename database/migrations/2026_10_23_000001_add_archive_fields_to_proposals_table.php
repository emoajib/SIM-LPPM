<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->boolean('is_archive_only')->default(false)->index();
            $table->string('import_batch_id')->nullable()->index();
            $table->timestamp('imported_at')->nullable();
            $table->char('archive_fingerprint', 64)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropUnique(['archive_fingerprint']);
            $table->dropIndex(['is_archive_only']);
            $table->dropIndex(['import_batch_id']);
            $table->dropColumn(['is_archive_only', 'import_batch_id', 'imported_at', 'archive_fingerprint']);
        });
    }
};
