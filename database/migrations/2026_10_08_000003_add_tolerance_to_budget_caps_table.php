<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_caps', function (Blueprint $table) {
            $table->decimal('amendment_tolerance_percent', 5, 2)->default(10.00)->after('enforce_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('budget_caps', function (Blueprint $table) {
            $table->dropColumn('amendment_tolerance_percent');
        });
    }
};
