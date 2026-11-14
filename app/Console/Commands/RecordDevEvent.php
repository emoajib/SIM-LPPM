<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\DevEvent;
use App\Services\DevEventRecorder;
use Illuminate\Console\Command;

/**
 * Pencatat generik peristiwa pengembangan ke `dev_events` (W4-H2.5).
 *
 * Dipakai oleh script deploy (`update_production.sh`) dan pemakaian manual.
 * Sengaja tidak pernah gagal keras: bila tabel belum ada, perintah tetap exit 0
 * agar deploy tidak terganggu.
 */
class RecordDevEvent extends Command
{
    protected $signature = 'dev:record
        {type : commit / ci / deploy / error / bug / decision}
        {title : Ringkasan singkat}
        {--area= : Area kerja (mis. produksi)}
        {--ref= : SHA commit, run id, atau URL}
        {--bc= : Kode BC terkait}
        {--status=info : success / failure / info / open}';

    protected $description = 'Catat satu peristiwa ke tabel dev_events';

    public function handle(DevEventRecorder $recorder): int
    {
        $type = trim((string) $this->argument('type'));
        $title = trim((string) $this->argument('title'));

        if ($type === '' || $title === '') {
            $this->error('Type dan title wajib diisi.');

            return self::FAILURE;
        }

        $event = $recorder->record($type, $title, array_filter([
            'area' => trim((string) $this->option('area')) ?: null,
            'reference' => trim((string) $this->option('ref')) ?: null,
            'bc_code' => trim((string) $this->option('bc')) ?: null,
            'status' => trim((string) $this->option('status')) ?: 'info',
        ]));

        if ($event instanceof DevEvent) {
            $this->info('Dev event dicatat: '.$event->id);

            return self::SUCCESS;
        }

        $this->warn('Dev event tidak dicatat (tabel dev_events belum ada atau pencatatan dimatikan).');

        return self::SUCCESS;
    }
}
