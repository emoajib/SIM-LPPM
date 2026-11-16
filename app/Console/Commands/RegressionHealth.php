<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\RegressionRegistry;
use Illuminate\Console\Command;

/**
 * Status kesehatan registry regresi (W4-H3.3).
 *
 * Menampilkan setiap BC, apakah punya test penjaga, dan commit terakhir yang
 * menyentuh entrinya. Read-only — tidak mengubah file atau database.
 */
class RegressionHealth extends Command
{
    protected $signature = 'regression:health {--json : Tampilkan JSON agar mudah dibaca tooling}';

    protected $description = 'Status BC × test penjaga × commit terakhir';

    public function handle(RegressionRegistry $registry): int
    {
        $guards = $registry->guards();
        $entries = [];

        foreach ($registry->entries() as $code => $path) {
            $guardFiles = $guards[$code] ?? [];
            $lastCommit = $registry->lastCommit($code);

            $entries[] = [
                'code' => $code,
                'title' => $registry->title($code),
                'guarded' => $guardFiles !== [],
                'tests' => $guardFiles,
                'last_commit' => $lastCommit,
                'path' => $path,
            ];
        }

        $missing = array_values(array_filter(
            $entries,
            static fn (array $entry): bool => $entry['guarded'] === false
        ));

        $summary = [
            'total' => count($entries),
            'guarded' => count($entries) - count($missing),
            'missing' => count($missing),
            'missing_codes' => array_column($missing, 'code'),
        ];

        if ($this->option('json')) {
            $json = json_encode([
                'generated_at' => now()->toIso8601String(),
                'summary' => $summary,
                'entries' => $entries,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            if ($json === false) {
                $this->error('Gagal menyandikan JSON.');

                return self::FAILURE;
            }

            $this->line($json);

            return self::SUCCESS;
        }

        $this->info('=== Regression Health ===');
        $this->line(sprintf(
            'Registry: %d kelas | Penjaga: %d | Tanpa penjaga: %d',
            $summary['total'],
            $summary['guarded'],
            $summary['missing'],
        ));
        $this->newLine();

        $rows = [];
        foreach ($entries as $entry) {
            $last = $entry['last_commit'];
            $testSummary = $entry['tests'] === []
                ? '-'
                : $entry['tests'][0].(count($entry['tests']) > 1 ? ' +'.(count($entry['tests']) - 1) : '');
            $rows[] = [
                $entry['code'],
                $entry['guarded'] ? 'OK' : 'MISSING',
                $entry['title'] ?? '-',
                $testSummary,
                $last === null ? '-' : $last['date'].' '.$last['hash'].' '.$last['subject'],
            ];
        }

        $this->table(['BC', 'Penjaga', 'Judul', 'Test', 'Commit terakhir'], $rows);

        if ($summary['missing'] > 0) {
            $this->warn('BC tanpa penjaga: '.implode(', ', $summary['missing_codes']));
        }

        return self::SUCCESS;
    }
}
