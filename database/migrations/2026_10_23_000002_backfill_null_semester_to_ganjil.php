<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('proposals')
            ->whereNull('semester')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    $created = $row->created_at ? Carbon::parse($row->created_at) : now();
                    $month = (int) $created->format('n');
                    $year = (int) $created->format('Y');

                    $startYear = $row->start_year;
                    if ($startYear === null) {
                        $startYear = $month >= 9 || $month <= 2 ? ($month <= 2 ? $year - 1 : $year) : $year;
                    }

                    DB::table('proposals')->where('id', $row->id)->update([
                        'start_year' => $startYear,
                        'semester' => 'ganjil',
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Irreversible data backfill.
    }
};
