<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('identities', function (Blueprint $table) {
            $table->integer('scopus_h_index')->nullable()->change();
            $table->integer('gs_h_index')->nullable()->change();
            $table->integer('wos_h_index')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('identities', function (Blueprint $table) {
            $table->integer('scopus_h_index')->default(0)->change();
            $table->integer('gs_h_index')->default(0)->change();
            $table->integer('wos_h_index')->default(0)->change();
        });
    }
};
