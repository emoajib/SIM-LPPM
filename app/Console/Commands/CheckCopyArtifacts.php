<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;

/**
 * Guard artefak copy/placeholder yang tidak boleh masuk produksi.
 *
 * Contoh: header komentar artefak AI yang dipakai massal oleh tooling AI.
 * Marker disimpan sebagai concatenation agar file ini tidak memicu dirinya
 * sendiri saat scan.
 */
class CheckCopyArtifacts extends Command
{
    protected $signature = 'copy:check
        {--path= : Direktori yang dipindai (default: root proyek)}
        {--pattern=* : Pola tambahan yang ditolak (case-insensitive)}';

    protected $description = 'Deteksi artefak copy/placeholder AI yang tidak boleh masuk produksi';

    /** @var array<int, string> */
    private const DEFAULT_PATTERNS = ['Vetted by '.'AI'];

    /** @var array<int, string> */
    private const EXCLUDED_DIRS = [
        'vendor',
        'node_modules',
        '.git',
        'public/build',
        'storage',
        'bootstrap/cache',
    ];

    public function handle(): int
    {
        $path = (string) ($this->option('path') ?: base_path());

        if (! is_dir($path)) {
            $this->error("Direktori tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        $patterns = array_merge(
            self::DEFAULT_PATTERNS,
            array_map(static fn (mixed $pattern): string => (string) $pattern, $this->option('pattern')),
        );

        $finder = Finder::create()
            ->files()
            ->in($path)
            ->ignoreDotFiles(false)
            ->ignoreVCS(true)
            ->notName('/\.env(\..+)?$/');

        foreach (self::EXCLUDED_DIRS as $dir) {
            $finder->exclude($dir);
        }

        $findings = [];

        foreach ($finder as $file) {
            $size = $file->getSize();

            if ($size !== false && $size > 2_000_000) {
                continue;
            }

            $contents = @file_get_contents($file->getPathname());

            if ($contents === false || str_contains($contents, "\0")) {
                continue;
            }

            $lines = preg_split('/\r\n|\r|\n/', $contents) ?: [];

            foreach ($lines as $index => $line) {
                foreach ($patterns as $pattern) {
                    if ($pattern !== '' && stripos($line, $pattern) !== false) {
                        $findings[] = sprintf(
                            '%s:%d: %s',
                            $file->getPathname(),
                            $index + 1,
                            trim($line),
                        );

                        break;
                    }
                }
            }
        }

        if ($findings === []) {
            $this->info('copy:check — tidak ada artefak terlarang.');

            return self::SUCCESS;
        }

        $total = count($findings);
        $this->error("copy:check — ditemukan {$total} artefak terlarang:");

        foreach (array_slice($findings, 0, 50) as $finding) {
            $this->line($finding);
        }

        if ($total > 50) {
            $this->line('... dan '.($total - 50).' temuan lainnya.');
        }

        return self::FAILURE;
    }
}
