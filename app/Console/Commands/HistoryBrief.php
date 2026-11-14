<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

/**
 * Ringkasan riwayat untuk satu area kerja (W4-H1.5).
 *
 * Tujuan: sebelum mengubah sesuatu, agent/engineer bisa langsung tahu
 * (1) commit terakhir yang menyentuh area itu, (2) kelas bug (BC) terkait,
 * dan (3) test penjaganya — tanpa menjelajah ulang seluruh repo.
 *
 * Pemakaian: php artisan history:brief modern
 *            php artisan history:brief final-report
 */
class HistoryBrief extends Command
{
    protected $signature = 'history:brief {area : Kata kunci area (mis. modern, final-report, amandemen)} {--limit=15 : Jumlah commit yang ditampilkan}';

    protected $description = 'Ringkasan commit, kelas bug (BC), dan test untuk satu area';

    public function handle(): int
    {
        $area = trim((string) $this->argument('area'));
        if ($area === '') {
            $this->error('Area tidak boleh kosong.');

            return self::FAILURE;
        }

        $limit = max(1, (int) $this->option('limit'));
        $needle = mb_strtolower($area);

        $this->info("=== Riwayat area: {$area} ===");

        $this->renderCommits($area, $limit);
        $this->renderBugClasses($needle);
        $this->renderTests($needle);

        return self::SUCCESS;
    }

    private function renderCommits(string $area, int $limit): void
    {
        $paths = $this->matchingPaths($area);

        $command = ['git', 'log', '--oneline', '-n', (string) $limit, '--grep='.$area, '-i'];
        if ($paths !== []) {
            // Gabungkan: commit yang menyentuh file terkait (lebih relevan).
            $command = array_merge(['git', 'log', '--oneline', '-n', (string) $limit], ['--'], $paths);
        }

        $process = new Process($command, base_path());
        $process->run();

        $this->newLine();
        $this->line($paths === []
            ? '<comment>Commit (berdasarkan pesan, file terkait tidak ditemukan):</comment>'
            : '<comment>Commit yang menyentuh file terkait:</comment>');

        $output = trim($process->getOutput());
        if ($output === '') {
            $this->line('  (tidak ada)');
        } else {
            foreach (explode("\n", $output) as $line) {
                $this->line('  '.$line);
            }
        }

        if ($paths !== []) {
            $this->line('  File terkait: '.implode(', ', array_slice($paths, 0, 8)).(count($paths) > 8 ? ', …' : ''));
        }
    }

    private function renderBugClasses(string $needle): void
    {
        $dir = base_path('docs/regressions');
        $this->newLine();
        $this->line('<comment>Kelas bug (docs/regressions) yang menyebut area ini:</comment>');

        if (! File::isDirectory($dir)) {
            $this->line('  (registry belum ada)');

            return;
        }

        $found = false;
        foreach (File::glob($dir.'/BC-*.md') as $file) {
            $content = mb_strtolower((string) File::get($file));
            if (! str_contains($content, $needle) && ! str_contains(mb_strtolower(basename($file)), $needle)) {
                continue;
            }
            $found = true;
            $this->line('  - '.basename($file));
        }

        if (! $found) {
            $this->line('  (tidak ada)');
        }
    }

    private function renderTests(string $needle): void
    {
        $this->newLine();
        $this->line('<comment>Test penjaga:</comment>');

        $files = array_values(array_filter(
            $this->trackedFiles(),
            function (string $path) use ($needle) {
                if (! str_starts_with($path, 'tests/')) {
                    return false;
                }

                if (str_contains(mb_strtolower($path), $needle)) {
                    return true;
                }

                $full = base_path($path);

                return is_file($full) && str_contains(mb_strtolower((string) File::get($full)), $needle);
            }
        ));

        if ($files === []) {
            $this->line('  (tidak ada test yang namanya mengandung area ini — pertimbangkan menambah)');

            return;
        }

        foreach ($files as $file) {
            $this->line('  - '.$file);
        }
    }

    /**
     * File yang dilacak git dan namanya mengandung area.
     *
     * @return array<int, string>
     */
    private function matchingPaths(string $area): array
    {
        $needle = mb_strtolower($area);

        $matches = array_values(array_filter(
            $this->trackedFiles(),
            fn (string $path) => str_contains(mb_strtolower($path), $needle) && ! str_starts_with($path, 'docs/')
        ));

        return array_slice($matches, 0, 20);
    }

    /**
     * @return array<int, string>
     */
    private function trackedFiles(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $process = new Process(['git', 'ls-files'], base_path());
        $process->run();

        $cache = array_values(array_filter(explode("\n", trim($process->getOutput()))));

        return $cache;
    }
}
