<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Backfill start_year/semester yang masih NULL dari created_at.
     * Pemetaan: Sep-Des Y => (Y, ganjil); Jan-Feb Y => (Y-1, ganjil); Mar-Agu Y => (Y, genap).
     * Hanya mengisi yang NULL — tidak mengubah baris yang sudah terisi.
     */
    public function up(): void
    {
        DB::table('proposals')
            ->whereNull('start_year')
            ->orWhereNull('semester')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    if ($row->start_year !== null && $row->semester !== null) {
                        continue;
                    }

                    $created = $row->created_at ? Carbon::parse($row->created_at) : now();
                    $month = (int) $created->format('n');
                    $year = (int) $created->format('Y');

                    if ($month >= 9) {
                        $startYear = $year;
                        $semester = 'ganjil';
                    } elseif ($month <= 2) {
                        $startYear = $year - 1;
                        $semester = 'ganjil';
                    } else {
                        $startYear = $year;
                        $semester = 'genap';
                    }

                    DB::table('proposals')->where('id', $row->id)->update([
                        'start_year' => $row->start_year ?? $startYear,
                        'semester' => $row->semester ?? $semester,
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Backfill tidak di-rollback (tidak ada cara aman mengembalikan NULL semula).
    }
};
