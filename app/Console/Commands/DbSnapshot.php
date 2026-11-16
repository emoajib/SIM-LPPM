<?php

/**
 * Vetted by SOSIOMEN - Manual Review Required by Senior Engineer/Manager
 */

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\MysqlDumpService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

/**
 * Snapshot database sebelum operasi berisiko (F8).
 *
 * Dipanggil `update_production.sh` sebelum `php artisan migrate --force`
 * supaya selalu ada titik pulih bila migrasi produksi gagal.
 *
 * Snapshot TIDAK otomatis di-restore: pemulihan lewat
 * `php artisan app:restore-backup --sql=<file>`, yang punya preview dan
 * konfirmasi. Menghapus semua tabel lalu restore buta seperti versi
 * `update_production_fixed.sh` berisiko meninggalkan database kosong bila
 * restore gagal.
 */
class DbSnapshot extends Command
{
    protected $signature = 'db:snapshot
        {--path= : Direktori tujuan (default storage/app/backup)}
        {--label=snapshot : Awalan nama file}
        {--keep=3 : Jumlah snapshot terbaru yang disimpan}
        {--dry-run : Tampilkan rencana tanpa menulis file}';

    protected $description = 'Buat snapshot database (SQL + gzip) untuk titik pulih';

    public function handle(MysqlDumpService $dumper): int
    {
        $dir = rtrim((string) ($this->option('path') ?: storage_path('app/backup')), '/');
        $label = strtolower((string) preg_replace('/[^A-Za-z0-9_-]/', '-', (string) $this->option('label')));
        $label = $label !== '' ? $label : 'snapshot';
        $keep = max(1, (int) $this->option('keep'));

        if ($this->option('dry-run')) {
            $this->line('Mode dry-run — tidak ada file yang ditulis.');
            $this->line("Direktori : {$dir}");
            $this->line('Nama file : '.basename("{$label}-".now()->format('Ymd_His').'.sql.gz'));
            $this->line('Retensi   : '.$keep.' snapshot terbaru');

            foreach ($this->oldSnapshots($dir, $label, $keep) as $file) {
                $this->line('Akan dihapus: '.basename($file));
            }

            return self::SUCCESS;
        }

        if (! File::isDirectory($dir) && ! File::makeDirectory($dir, 0700, true)) {
            $this->error("Direktori snapshot tidak bisa dibuat: {$dir}");

            return self::FAILURE;
        }

        $stamp = now()->format('Ymd_His');
        $sqlPath = "{$dir}/{$label}-{$stamp}.sql";
        $gzPath = $sqlPath.'.gz';

        [$ok, $error] = $dumper->dumpTo($sqlPath);

        if (! $ok) {
            $this->error("Snapshot database gagal: {$error}");

            return self::FAILURE;
        }

        $gzip = null;

        try {
            $gzip = Process::run(['gzip', '-f', $sqlPath]);
        } catch (\Throwable $e) {
            @unlink($sqlPath);
            $this->error('Perintah gzip tidak tersedia: '.$e->getMessage());

            return self::FAILURE;
        }

        if (! $gzip->successful() || ! is_file($gzPath)) {
            @unlink($sqlPath);
            $this->error('Snapshot berhasil dibuat, tetapi kompresi gzip gagal.');

            return self::FAILURE;
        }

        $deleted = $this->pruneOldSnapshots($dir, $label, $keep);
        $size = (int) @filesize($gzPath);

        $this->info('Snapshot database dibuat: '.basename($gzPath).' ('.$this->humanSize($size).')');
        $this->line('Retensi: '.count($deleted).' file lama dihapus.');
        $this->line('Pulihkan bila diperlukan:');
        $this->line('  gunzip -c '.basename($gzPath).' > /tmp/restore.sql');
        $this->line('  php artisan app:restore-backup --sql=/tmp/restore.sql --force');

        return self::SUCCESS;
    }

    /**
     * Snapshot milik label ini, terbaru dulu.
     *
     * @return list<string>
     */
    private function oldSnapshots(string $dir, string $label, int $keep): array
    {
        $files = array_merge(
            glob("{$dir}/{$label}-*.sql.gz") ?: [],
            glob("{$dir}/{$label}-*.sql") ?: [],
        );

        usort($files, static fn (string $a, string $b): int => (int) @filemtime($b) <=> (int) @filemtime($a));

        return array_slice($files, $keep);
    }

    /**
     * @return list<string> file yang benar-benar dihapus
     */
    private function pruneOldSnapshots(string $dir, string $label, int $keep): array
    {
        $deleted = [];

        foreach ($this->oldSnapshots($dir, $label, $keep) as $file) {
            if (@unlink($file)) {
                $deleted[] = $file;
            }
        }

        return $deleted;
    }

    private function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $value = (float) $bytes;
        $unit = 0;

        while ($value >= 1024 && $unit < count($units) - 1) {
            $value /= 1024;
            $unit++;
        }

        return sprintf('%.1f %s', $value, $units[$unit]);
    }
}
