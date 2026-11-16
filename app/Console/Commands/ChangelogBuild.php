<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

/**
 * Bangun docs/CHANGELOG.md dari conventional commit (W4-H3.2).
 *
 * Tujuan: changelog tidak lagi disusun manual. Commit feat/fix/perf/security
 * dikelompokkan otomatis dan ditautkan ke SHA di GitHub.
 *
 * Pemakaian:
 *   php artisan changelog:build
 *   php artisan changelog:build --since=v1.0.0 --output=docs/CHANGELOG.md
 *   php artisan changelog:build --limit=50 --dry-run
 */
class ChangelogBuild extends Command
{
    protected $signature = 'changelog:build
        {--since= : Batas bawah: tag, SHA, atau tanggal YYYY-MM-DD}
        {--limit=200 : Maksimal commit yang dibaca}
        {--output=docs/CHANGELOG.md : Path output relatif terhadap repo}
        {--repo-path= : Path repo git (default: root proyek)}
        {--dry-run : Cetak markdown, jangan tulis file}';

    protected $description = 'Bangun docs/CHANGELOG.md dari conventional commit';

    /** @var array<string, string> */
    private const GROUP_LABELS = [
        'feat' => 'Fitur',
        'fix' => 'Perbaikan',
        'perf' => 'Performa',
        'security' => 'Keamanan',
        'other' => 'Lainnya',
    ];

    public function handle(): int
    {
        $repoPath = $this->repositoryPath();

        if (! is_dir($repoPath) || ! File::exists($repoPath.'/.git')) {
            $this->error("Repo git tidak ditemukan: {$repoPath}");

            return self::FAILURE;
        }

        $commits = $this->commits($repoPath);

        if ($commits === null) {
            return self::FAILURE;
        }

        $markdown = $this->render($repoPath, $commits);

        if ($this->option('dry-run')) {
            $this->line($markdown);
            $this->info('DRY RUN — file tidak ditulis.');

            return self::SUCCESS;
        }

        $outputOption = trim((string) $this->option('output')) ?: 'docs/CHANGELOG.md';
        $outputPath = str_starts_with($outputOption, '/')
            ? $outputOption
            : rtrim($repoPath, '/').'/'.ltrim($outputOption, '/');

        File::ensureDirectoryExists(dirname($outputPath));
        File::put($outputPath, $markdown);

        $relative = str_starts_with($outputPath, $repoPath.'/')
            ? substr($outputPath, strlen($repoPath) + 1)
            : $outputPath;

        $this->info("Changelog ditulis ke {$relative} (".count($commits).' commit).');

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{sha: string, short: string, date: string, subject: string, type: string, scope: string|null, breaking: bool}>|null
     */
    private function commits(string $repoPath): ?array
    {
        $limit = max(1, (int) $this->option('limit'));
        $command = [
            'git', '-C', $repoPath, 'log',
            '--no-merges',
            '--date=short',
            '--pretty=format:%H%x1f%h%x1f%ad%x1f%s%x1e',
        ];

        $since = trim((string) $this->option('since'));
        if ($since !== '') {
            $command[] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $since) === 1
                ? '--since='.$since
                : $since.'..HEAD';
        }

        $command[] = '-n';
        $command[] = (string) $limit;

        $process = new Process($command, $repoPath);
        $process->setTimeout(20);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error('Gagal membaca git log: '.trim($process->getErrorOutput()));

            return null;
        }

        $commits = [];
        foreach (explode("\x1e", trim($process->getOutput())) as $record) {
            $record = trim($record);
            if ($record === '') {
                continue;
            }

            $parts = explode("\x1f", $record, 4);
            if (count($parts) < 4) {
                continue;
            }

            [$sha, $short, $date, $subject] = $parts;
            $parsed = $this->parseSubject($subject);

            $commits[] = [
                'sha' => $sha,
                'short' => $short,
                'date' => $date,
                'subject' => $subject,
                'type' => $parsed['type'],
                'scope' => $parsed['scope'],
                'breaking' => $parsed['breaking'],
            ];
        }

        return $commits;
    }

    /**
     * @return array{type: string, scope: string|null, breaking: bool}
     */
    private function parseSubject(string $subject): array
    {
        $subject = trim($subject);

        if (preg_match('/^([a-z]+)(?:\(([^)]+)\))?(!)?:\s*(.+)$/i', $subject, $matches) !== 1) {
            return ['type' => 'other', 'scope' => null, 'breaking' => false];
        }

        return [
            'type' => strtolower($matches[1]),
            'scope' => $matches[2] !== '' ? $matches[2] : null,
            'breaking' => $matches[3] === '!',
        ];
    }

    /**
     * @param  array<int, array{sha: string, short: string, date: string, subject: string, type: string, scope: string|null, breaking: bool}>  $commits
     */
    private function render(string $repoPath, array $commits): string
    {
        $lines = [];
        $lines[] = '# Changelog';
        $lines[] = '';
        $lines[] = '> Dihasilkan otomatis oleh `php artisan changelog:build` pada '.now()->format('Y-m-d').'.';
        $lines[] = '> Sumber: conventional commit. Jangan edit manual — jalankan ulang command.';
        $lines[] = '';

        $since = trim((string) $this->option('since'));
        if ($since !== '') {
            $lines[] = "> Rentang: `{$since}..HEAD`";
            $lines[] = '';
        }

        if ($commits === []) {
            $lines[] = '_Tidak ada commit yang cocok._';
            $lines[] = '';

            return implode("\n", $lines);
        }

        $repoUrl = $this->repositoryUrl($repoPath);
        $groups = [];

        foreach ($commits as $commit) {
            $type = isset(self::GROUP_LABELS[$commit['type']]) ? $commit['type'] : 'other';
            $groups[$type][] = $commit;
        }

        foreach (self::GROUP_LABELS as $type => $label) {
            if (($groups[$type] ?? []) === []) {
                continue;
            }

            $lines[] = '## '.$label;
            $lines[] = '';

            foreach ($groups[$type] as $commit) {
                $scope = $commit['scope'] !== null ? '**'.$commit['scope'].'** ' : '';
                $link = $repoUrl !== null
                    ? '['.$commit['short'].']('.$repoUrl.'/commit/'.$commit['sha'].')'
                    : $commit['short'];
                $breaking = $commit['breaking'] ? ' **(breaking)**' : '';

                $lines[] = '- '.$scope.$link.' — '.$commit['subject'].$breaking;
            }

            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    private function repositoryPath(): string
    {
        $configured = trim((string) $this->option('repo-path'));

        return $configured !== '' ? rtrim($configured, '/') : base_path();
    }

    private function repositoryUrl(string $repoPath): ?string
    {
        $configured = trim((string) config('observability.history_sync.repo', ''));
        if ($configured !== '') {
            return str_starts_with($configured, 'http')
                ? rtrim($configured, '/')
                : 'https://github.com/'.ltrim($configured, '/');
        }

        $process = new Process(['git', '-C', $repoPath, 'remote', 'get-url', 'origin'], $repoPath);
        $process->setTimeout(10);
        $process->run();

        if (! $process->isSuccessful()) {
            return null;
        }

        $url = trim($process->getOutput());

        if (preg_match('#github\.com[:/]([^/]+/[^/.]+)(?:\.git)?$#', $url, $matches) === 1) {
            return 'https://github.com/'.$matches[1];
        }

        return null;
    }
}
