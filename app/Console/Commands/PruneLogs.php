<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Rotasi/retensi file log (W4-H2.6).
 *
 * Kejadian nyata: `storage/logs/laravel.log` 363 MB dan `browser.log` 46 MB.
 * File sebesar itu tidak bisa dibaca manusia maupun agent, dan berisiko
 * menghabiskan kuota disk.
 *
 * Strategi:
 * - Hapus `laravel-YYYY-MM-DD.log` yang lebih tua dari `--days`.
 * - Untuk log aktif (mis. `laravel.log`, `browser.log`), bila melewati
 *   `--max-size`, sisakan ekor `--tail-bytes` byte terakhir (baris utuh).
 */
class PruneLogs extends Command
{
    protected $signature = 'logs:prune
        {--days= : Hari retensi file harian (default dari config)}
        {--max-size= : Batas ukuran log aktif dalam MB (default dari config)}
        {--tail-bytes= : Byte yang disimpan saat rotasi}
        {--path= : Direktori log (default storage/logs)}
        {--dry-run : Hitung saja, jangan ubah file}';

    protected $description = 'Hapus log lama dan rotasi log aktif yang terlalu besar';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('observability.logs.keep_days', 14));
        $maxMb = (int) ($this->option('max-size') ?: config('observability.logs.max_size_mb', 50));
        $tailBytes = (int) ($this->option('tail-bytes') ?: config('observability.logs.tail_bytes', 1048576));
        $dir = rtrim((string) ($this->option('path') ?: storage_path('logs')), '/');

        if ($days < 1 || $maxMb < 1 || $tailBytes < 1) {
            $this->error('Nilai days/max-size/tail-bytes harus >= 1.');

            return self::FAILURE;
        }

        if (! File::isDirectory($dir)) {
            $this->error("Direktori log tidak ada: {$dir}");

            return self::FAILURE;
        }

        $cutoff = now()->subDays($days);
        $deleted = [];
        $rotated = [];

        foreach (File::glob($dir.'/*.log') ?: [] as $file) {
            $name = basename($file);
            $modified = File::lastModified($file);

            // File harian (laravel-2026-11-01.log) yang sudah lewat retensi.
            if (preg_match('/-\d{4}-\d{2}-\d{2}\.log$/', $name) === 1
                && now()->createFromTimestamp($modified)->lt($cutoff)) {
                $deleted[] = $name;

                if (! $this->option('dry-run')) {
                    File::delete($file);
                }

                continue;
            }

            $size = File::size($file);
            if ($size > $maxMb * 1024 * 1024) {
                $rotated[] = $name;

                if (! $this->option('dry-run')) {
                    $this->keepTail($file, $tailBytes);
                }
            }
        }

        $this->info('=== Logs Prune ===');
        $this->line('Dihapus ('.$days.' hari): '.($deleted === [] ? '(tidak ada)' : implode(', ', $deleted)));
        $this->line('Dirotasi (>'.$maxMb.' MB): '.($rotated === [] ? '(tidak ada)' : implode(', ', $rotated)));

        if ($this->option('dry-run')) {
            $this->line('DRY RUN — tidak ada file diubah.');
        }

        return self::SUCCESS;
    }

    /**
     * Sisakan ekor file agar baris tetap utuh (mulai setelah newline pertama).
     */
    private function keepTail(string $path, int $tailBytes): void
    {
        $size = File::size($path);
        $read = min($size, $tailBytes);

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return;
        }

        if ($read < $size) {
            fseek($handle, -$read, SEEK_END);
        }

        $chunk = (string) fread($handle, $read);
        fclose($handle);

        $newline = strpos($chunk, "\n");
        $tail = $newline === false ? $chunk : substr($chunk, $newline + 1);

        File::put($path, $tail);
    }
}
