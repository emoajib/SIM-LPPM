<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * W4-H2.2 — agregasi kelas error (bukan satu baris per kejadian).
 *
 * Satu baris = satu fingerprint; pengulangan menambah `occurrences`.
 * Tabel ini tidak menggantikan log, tetapi mempertahankan jejak error
 * setelah file log dirotasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('error_signatures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('fingerprint', 64)->unique()->comment('sha256 kelas error ternormalisasi');
            $table->string('exception_class', 191);
            $table->text('message')->nullable();
            $table->string('file', 500)->nullable();
            $table->unsignedInteger('line')->nullable();
            $table->string('module', 60)->nullable()->comment('research / community-service / admin / auth');
            $table->string('bc_code', 10)->nullable()->comment('kaitan registry docs/regressions');
            $table->string('status', 20)->default('open')->comment('open / investigating / fixed / ignored');
            $table->unsignedInteger('occurrences')->default(1);
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->string('commit_fix', 40)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'last_seen_at']);
            $table->index('module');
            $table->index('bc_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('error_signatures');
    }
};
