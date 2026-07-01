<?php

namespace App\Console\Commands;

use App\Models\TktIndicator;
use App\Models\TktLevel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PatchSoftwareTkt extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'patch:software-tkt';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Patch missing Software TKT levels (4-9)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Patching Software TKT Levels...');

        $levels = [
            [
                'level' => 4,
                'description' => 'Validasi komponen/sub-sistem dalam lingkungan laboratorium (Alpha)',
                'indicators' => [
                    ['code' => '4.1', 'indicator' => 'Komponen software dasar (front-end & back-end) telah diintegrasikan'],
                    ['code' => '4.2', 'indicator' => 'Prototipe versi Alpha siap diuji coba secara internal'],
                ],
            ],
            [
                'level' => 5,
                'description' => 'Validasi komponen/sub-sistem dalam lingkungan yang relevan',
                'indicators' => [
                    ['code' => '5.1', 'indicator' => 'Prototipe diuji coba pada environment simulasi (Staging)'],
                    ['code' => '5.2', 'indicator' => 'Fungsionalitas inti terbukti berjalan stabil'],
                ],
            ],
            [
                'level' => 6,
                'description' => 'Demonstrasi model atau prototipe sistem/sub-sistem dalam lingkungan yang relevan (Beta)',
                'indicators' => [
                    ['code' => '6.1', 'indicator' => 'Versi Beta software siap didemonstrasikan'],
                    ['code' => '6.2', 'indicator' => 'Interaksi UI/UX dan aliran data berjalan lancar tanpa error kritikal'],
                ],
            ],
            [
                'level' => 7,
                'description' => 'Demonstrasi prototipe sistem dalam lingkungan operasional',
                'indicators' => [
                    ['code' => '7.1', 'indicator' => 'Software diujicobakan pada target pengguna akhir terbatas (Beta Tester)'],
                    ['code' => '7.2', 'indicator' => 'Bug dan umpan balik awal pengguna sudah dianalisa dan ditangani'],
                ],
            ],
            [
                'level' => 8,
                'description' => 'Sistem telah lengkap dan memenuhi syarat (qualified)',
                'indicators' => [
                    ['code' => '8.1', 'indicator' => 'Versi Release Candidate (RC) siap dirilis ke publik'],
                    ['code' => '8.2', 'indicator' => 'Software lulus audit keamanan, performa (load testing), dan kelayakan mutu'],
                ],
            ],
            [
                'level' => 9,
                'description' => 'Sistem benar-benar teruji/terbukti melalui keberhasilan pengoperasian',
                'indicators' => [
                    ['code' => '9.1', 'indicator' => 'Software beroperasi penuh di lingkungan Production dengan lancar'],
                    ['code' => '9.2', 'indicator' => 'Dokumentasi teknis, user manual, dan SLA pemeliharaan telah diterbitkan'],
                ],
            ],
        ];

        DB::transaction(function () use ($levels) {
            foreach ($levels as $levelData) {
                // Upsert Level
                $level = TktLevel::firstOrCreate(
                    ['type' => 'Software', 'level' => $levelData['level']],
                    ['description' => $levelData['description']]
                );

                // Update description if it already existed but differed
                if ($level->description !== $levelData['description']) {
                    $level->update(['description' => $levelData['description']]);
                }

                // Upsert Indicators
                foreach ($levelData['indicators'] as $indicatorData) {
                    $indicator = TktIndicator::firstOrCreate(
                        ['tkt_level_id' => $level->id, 'code' => $indicatorData['code']],
                        ['indicator' => $indicatorData['indicator']]
                    );

                    if ($indicator->indicator !== $indicatorData['indicator']) {
                        $indicator->update(['indicator' => $indicatorData['indicator']]);
                    }
                }
            }
        });

        $this->info('Software TKT Levels (4-9) patched successfully!');
    }
}
