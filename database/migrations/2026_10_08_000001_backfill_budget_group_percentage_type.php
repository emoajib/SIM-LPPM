<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Selaraskan percentage_type dengan label UI: TEKNOLOGI = minimal,
     * kelompok lain = maksimal. Hanya mengisi yang masih NULL.
     */
    public function up(): void
    {
        DB::table('budget_groups')
            ->whereNull('percentage_type')
            ->where('code', 'TEKNOLOGI')
            ->update(['percentage_type' => 'min']);

        DB::table('budget_groups')
            ->whereNull('percentage_type')
            ->update(['percentage_type' => 'max']);
    }

    public function down(): void
    {
        // Tidak di-rollback (pengisian bersifat korektif).
    }
};
