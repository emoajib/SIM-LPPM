<?php

namespace App\Console\Commands;

use App\Services\DatabaseRestoreService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class SyncProductionData extends Command
{
    protected $signature = 'app:sync-production {--force : Skip confirmation} {--sql-only : Hanya download SQL, jangan auto-import}';

    protected $description = 'Sinkronisasi data dari server produksi ke localhost (Database & Storage)';

    public function handle(DatabaseRestoreService $dbRestore): int
    {
        if (config('app.env') !== 'local' && ! $this->option('force')) {
            $this->error('Command ini hanya boleh dijalankan di lingkungan LOCAL!');

            return 1;
        }

        $remoteUser = config('sync.remote_user');
        $remoteHost = config('sync.remote_host');
        $remotePath = config('sync.remote_path');
        $remoteDbUser = config('sync.remote_db_user');
        $remoteDbPass = config('sync.remote_db_password');
        $remoteDb = config('sync.remote_db');

        if (empty($remoteUser) || empty($remoteHost)) {
            $this->error('Konfigurasi server remote belum lengkap. Set SYNC_REMOTE_USER dan SYNC_REMOTE_HOST di .env');

            return 1;
        }

        $this->info("Memulai sinkronisasi dari $remoteHost...");

        // 1. Dump Remote Database
        $this->comment('1/4 Membuat dump database di server remote...');
        $dumpFile = base_path('prod_dump_temp.sql');

        $auth = '';
        if (! empty($remoteDbPass)) {
            $auth = "-p'{$remoteDbPass}'";
        }

        $dumpCmd = sprintf(
            'ssh %s@%s "mysqldump --complete-insert -u %s %s --skip-lock-tables --routines --triggers --events %s > %s/prod_dump_temp.sql"',
            escapeshellarg($remoteUser),
            escapeshellarg($remoteHost),
            escapeshellarg($remoteDbUser ?: $remoteUser),
            $auth,
            escapeshellarg($remoteDb),
            escapeshellarg($remotePath)
        );

        $result = Process::run($dumpCmd);

        if ($result->failed()) {
            $this->error('Gagal membuat dump di server: '.$result->errorOutput());

            return 1;
        }

        // 2. Download Dump
        $this->comment('2/4 Mendownload file dump...');
        $scpCmd = sprintf(
            'scp %s@%s:%s/prod_dump_temp.sql %s',
            escapeshellarg($remoteUser),
            escapeshellarg($remoteHost),
            escapeshellarg($remotePath),
            escapeshellarg($dumpFile)
        );

        $scpResult = Process::run($scpCmd);

        if ($scpResult->failed()) {
            $this->error('Gagal mendownload file dump: '.$scpResult->errorOutput());

            return 1;
        }

        // 3. Import to Local (via DatabaseRestoreService)
        if ($this->option('sql-only')) {
            $this->comment('3/4 SQL-only mode — file tersimpan di: '.$dumpFile);
            $this->info('Gunakan php artisan app:restore-backup --sql='.$dumpFile.' untuk import');
        } else {
            $this->comment('3/4 Mengimport data ke database lokal (via DatabaseRestoreService)...');
            $result = $dbRestore->restore($dumpFile, true);

            if ($result['success']) {
                $this->info($result['message']);
                if (! empty($result['backup_path']) && file_exists($result['backup_path'])) {
                    $this->line('Backup pra-restore: '.$result['backup_path']);
                }
            } else {
                $this->error('Gagal import database: '.$result['message']);

                return 1;
            }
        }

        // 4. Sync Files
        $this->comment('4/4 Sinkronisasi file storage (rsync)...');
        $rsyncCmd = sprintf(
            'rsync -avz -e ssh %s@%s:%s/storage/app/public/ %s',
            escapeshellarg($remoteUser),
            escapeshellarg($remoteHost),
            escapeshellarg($remotePath),
            escapeshellarg(storage_path('app/public/'))
        );

        $rsyncResult = Process::run($rsyncCmd);

        if ($rsyncResult->failed()) {
            $this->warn('Rsync storage gagal (non-fatal): '.$rsyncResult->errorOutput());
        }

        // Cleanup remote temp file
        Process::run(sprintf(
            'ssh %s@%s "rm -f %s/prod_dump_temp.sql"',
            escapeshellarg($remoteUser),
            escapeshellarg($remoteHost),
            escapeshellarg($remotePath)
        ));

        $this->info('Sinkronisasi SELESAI!');

        return 0;
    }
}
