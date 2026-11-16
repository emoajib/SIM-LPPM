<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\DevEvent;
use App\Models\ErrorSignature;
use App\Services\DevEventRecorder;
use App\Support\RegressionRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

/**
 * Rekap mingguan engineering (W4-H3.2/H3.3).
 *
 * Menarik empat sinyal yang mudah hilang di antara log/CI:
 * error baru, workflow gagal, perbaikan tanpa test, dan BC tanpa penjaga.
 * Output markdown + satu baris `dev_events` (type digest) agar bisa ditelusuri.
 */
class DigestWeekly extends Command
{
    protected $signature = 'digest:weekly
        {--weeks=1 : Rentang rekap dalam minggu}
        {--output= : Path markdown (default: storage/app/digests/weekly-YYYY-Www.md)}
        {--repo-path= : Path repo git (default: root proyek)}
        {--dry-run : Cetak markdown, jangan tulis file/DB}
        {--no-record : Jangan catat dev_events}';

    protected $description = 'Rekap mingguan error baru, CI gagal, fix tanpa test, dan BC tanpa penjaga';

    public function handle(DevEventRecorder $recorder, RegressionRegistry $registry): int
    {
        $weeks = (int) $this->option('weeks');
        if ($weeks < 1) {
            $this->error('Jumlah minggu harus >= 1.');

            return self::FAILURE;
        }

        $since = now()->subWeeks($weeks)->startOfDay();
        $until = now();

        $errors = ErrorSignature::query()
            ->needsAttention()
            ->where('first_seen_at', '>=', $since)
            ->orderByDesc('last_seen_at')
            ->get();

        $ciFailures = DevEvent::query()
            ->ofType(DevEvent::TYPE_CI)
            ->where('status', 'failure')
            ->where('occurred_at', '>=', $since)
            ->orderByDesc('occurred_at')
            ->get();

        $fixes = $this->fixesWithoutTest($since);
        $missing = $registry->missingInTests();

        $markdown = $this->render($since, $until, $errors, $ciFailures, $fixes, $missing, $registry);

        if ($this->option('dry-run')) {
            $this->line($markdown);

            return self::SUCCESS;
        }

        $outputOption = trim((string) $this->option('output'));
        $outputPath = $outputOption !== ''
            ? (str_starts_with($outputOption, '/') ? $outputOption : base_path($outputOption))
            : storage_path('app/digests/weekly-'.now()->format('o-W').'.md');

        File::ensureDirectoryExists(dirname($outputPath));
        File::put($outputPath, $markdown);

        $recorded = null;
        if (! $this->option('no-record')) {
            $recorded = $recorder->record(DevEvent::TYPE_DIGEST, 'Weekly digest '.now()->format('o-W'), [
                'area' => 'observability',
                'status' => 'info',
                'occurred_at' => $until,
                'payload' => [
                    'period_from' => $since->toIso8601String(),
                    'period_until' => $until->toIso8601String(),
                    'errors' => $errors->count(),
                    'ci_failures' => $ciFailures->count(),
                    'fixes_without_test' => count($fixes),
                    'missing_guards' => $missing,
                    'output' => $outputPath,
                ],
            ]);
        }

        $this->info('Digest mingguan ditulis ke '.$outputPath);
        $this->line(sprintf(
            'Error baru: %d | CI gagal: %d | Fix tanpa test: %d | BC tanpa penjaga: %d',
            $errors->count(),
            $ciFailures->count(),
            count($fixes),
            count($missing),
        ));

        if ($recorded === null) {
            $this->line('Dev event: (tidak tercatat)');
        } else {
            $this->line('Dev event: '.$recorded->id);
        }

        return self::SUCCESS;
    }

    /**
     * Commit dengan marker `No-Regression-Test` di body.
     *
     * @return array<int, array{hash: string, subject: string, reason: string}>
     */
    private function fixesWithoutTest(Carbon $since): array
    {
        $repoPath = trim((string) $this->option('repo-path')) ?: base_path();

        if (! is_dir($repoPath) || ! File::exists($repoPath.'/.git')) {
            return [];
        }

        $process = new Process([
            'git', '-C', $repoPath, 'log',
            '--since='.$since->format('Y-m-d\TH:i:sP'),
            '--pretty=format:%H%x1f%h%x1f%ad%x1f%s%x1f%b%x1e',
            '--date=short',
        ], $repoPath);

        $process->setTimeout(20);
        $process->run();

        if (! $process->isSuccessful()) {
            return [];
        }

        $fixes = [];

        foreach (explode("\x1e", trim($process->getOutput())) as $record) {
            $record = trim($record);
            if ($record === '') {
                continue;
            }

            $parts = explode("\x1f", $record, 5);
            if (count($parts) < 5 || stripos($parts[4], 'No-Regression-Test') === false) {
                continue;
            }

            $reason = '(alasan tidak dicantumkan)';
            if (preg_match('/No-Regression-Test:\s*(.+)/i', $parts[4], $matches) === 1) {
                $reason = trim($matches[1]);
            }

            $fixes[] = [
                'hash' => $parts[1],
                'subject' => $parts[3],
                'reason' => $reason,
            ];
        }

        return $fixes;
    }

    /**
     * @param  Collection<int, ErrorSignature>  $errors
     * @param  Collection<int, DevEvent>  $ciFailures
     * @param  array<int, array{hash: string, subject: string, reason: string}>  $fixes
     * @param  array<int, string>  $missing
     */
    private function render(
        Carbon $since,
        Carbon $until,
        Collection $errors,
        Collection $ciFailures,
        array $fixes,
        array $missing,
        RegressionRegistry $registry
    ): string {
        $lines = [];
        $lines[] = '# Weekly Engineering Digest';
        $lines[] = '';
        $lines[] = '> Periode: '.$since->format('Y-m-d').' s.d. '.$until->format('Y-m-d');
        $lines[] = '> Dihasilkan otomatis oleh `php artisan digest:weekly`.';
        $lines[] = '';
        $lines[] = '## Ringkasan';
        $lines[] = '';
        $lines[] = '- Error baru: '.$errors->count();
        $lines[] = '- CI gagal: '.$ciFailures->count();
        $lines[] = '- Fix tanpa test: '.count($fixes);
        $lines[] = '- BC tanpa penjaga: '.count($missing);
        $lines[] = '';

        $lines[] = '## Error baru';
        $lines[] = '';
        if ($errors->isEmpty()) {
            $lines[] = '_Tidak ada._';
        } else {
            foreach ($errors as $error) {
                $lines[] = '### '.$error->exception_class;
                $lines[] = '';
                $lines[] = '- Status: '.$error->status;
                $lines[] = '- Modul: '.($error->module ?? '-');
                $lines[] = '- Kemunculan: '.$error->occurrences;
                $lines[] = '- Terakhir: '.($error->last_seen_at?->format('Y-m-d H:i') ?? '-');
                $lines[] = '- Lokasi: '.($error->file ?? '-').($error->line !== null ? ':'.$error->line : '');
                $lines[] = '- BC: '.($error->bc_code ?? '-');
                $lines[] = '';
            }
        }
        $lines[] = '';

        $lines[] = '## CI gagal';
        $lines[] = '';
        if ($ciFailures->isEmpty()) {
            $lines[] = '_Tidak ada._';
        } else {
            foreach ($ciFailures as $failure) {
                $payload = $failure->payload ?? [];
                $url = $payload['url'] ?? null;
                $label = $failure->title;
                if (is_string($url) && $url !== '') {
                    $label = '['.$label.']('.$url.')';
                }
                $lines[] = '- '.$label.' — '.$failure->reference.' — '.$failure->occurred_at?->format('Y-m-d H:i');
            }
        }
        $lines[] = '';

        $lines[] = '## Fix tanpa test';
        $lines[] = '';
        if ($fixes === []) {
            $lines[] = '_Tidak ada._';
        } else {
            foreach ($fixes as $fix) {
                $lines[] = '- '.$fix['hash'].' — '.$fix['subject'].' — No-Regression-Test: '.$fix['reason'];
            }
        }
        $lines[] = '';

        $lines[] = '## BC tanpa penjaga';
        $lines[] = '';
        if ($missing === []) {
            $lines[] = '_Tidak ada._';
        } else {
            foreach ($missing as $code) {
                $lines[] = '- '.$code.' — '.($registry->title($code) ?? '-');
            }
        }
        $lines[] = '';

        return implode("\n", $lines);
    }
}
