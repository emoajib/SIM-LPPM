<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * W4-H2.3 — peristiwa pengembangan permanen (commit, CI, deploy, error, bug).
 *
 * Retensi GitHub Actions dan file log terbatas; tabel ini menyimpan jejak
 * yang dibutuhkan `history:brief` dan audit regresi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dev_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type', 20)->comment('commit / ci / deploy / error / bug / decision');
            $table->string('area', 80)->nullable()->comment('area kerja, sama dengan history:brief');
            $table->string('title', 191);
            $table->string('reference', 191)->nullable()->comment('SHA commit, run id, atau URL');
            $table->string('bc_code', 10)->nullable();
            $table->string('status', 20)->nullable()->comment('success / failure / info / open');
            $table->json('payload')->nullable()->comment('detail tambahan (tanpa PII berlebih)');
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();

            $table->index('type');
            $table->index('area');
            $table->index('bc_code');
            $table->index('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dev_events');
    }
};
