<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\DevEvent;
use App\Services\DevEventRecorder;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

/**
 * Tarik commit + status workflow GitHub ke `dev_events` (W4-H2.4).
 *
 * Alasan: retensi log GitHub Actions terbatas dan riwayat git bisa hilang bila
 * repo dipindah. Dengan tabel permanen, `history:brief` tetap bisa menelusuri
 * konteks lama.
 *
 * Token: `GITHUB_TOKEN` (atau `gh auth token`). Tanpa token, API publik dibatasi
 * 60 permintaan/jam per IP.
 */
class HistorySync extends Command
{
    protected $signature = 'history:sync
        {--days=7 : Rentang commit yang ditarik (hari)}
        {--limit=50 : Maksimal commit}
        {--runs=20 : Maksimal workflow run}
        {--dry-run : Tampilkan rencana tanpa menulis}';

    protected $description = 'Sinkronkan commit & status CI GitHub ke tabel dev_events';

    public function handle(DevEventRecorder $recorder): int
    {
        $repo = $this->repository();
        if ($repo === null) {
            $this->error('Tidak bisa menentukan repository GitHub. Set GITHUB_REPO=owner/repo.');

            return self::FAILURE;
        }

        $token = $this->token();
        if ($token === null) {
            $this->error('GITHUB_TOKEN belum diset. Buat token (scope: repo) atau jalankan `gh auth login`.');

            return self::FAILURE;
        }

        $days = max(1, (int) $this->option('days'));
        $since = now()->subDays($days)->toIso8601ZuluString();

        $this->info("=== History Sync: {$repo} ({$days} hari terakhir) ===");

        $commitCount = $this->syncCommits($repo, $token, $since, $recorder);
        $runCount = $this->syncWorkflowRuns($repo, $token, $recorder);

        $this->info("Selesai: {$commitCount} commit, {$runCount} workflow run.");

        return self::SUCCESS;
    }

    private function syncCommits(string $repo, string $token, string $since, DevEventRecorder $recorder): int
    {
        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(20)
            ->get("https://api.github.com/repos/{$repo}/commits", [
                'since' => $since,
                'per_page' => max(1, min(100, (int) $this->option('limit'))),
            ]);

        if (! $response->successful()) {
            $this->error('Gagal mengambil commit: HTTP '.$response->status());

            return 0;
        }

        $saved = 0;
        $dryRun = (bool) $this->option('dry-run');

        foreach ($response->json() ?: [] as $commit) {
            $sha = $commit['sha'] ?? null;
            if (! is_string($sha) || $sha === '') {
                continue;
            }

            $message = (string) ($commit['commit']['message'] ?? '');
            $title = $this->firstLine($message);
            $area = $this->inferArea($sha);

            if ($this->exists('commit', $sha)) {
                continue;
            }

            if ($dryRun) {
                $this->line('  [commit] '.substr($sha, 0, 8).' '.$title);

                $saved++;

                continue;
            }

            $recorder->record(DevEvent::TYPE_COMMIT, $title, [
                'area' => $area,
                'reference' => $sha,
                'status' => 'info',
                'occurred_at' => isset($commit['commit']['author']['date'])
                    ? Carbon::parse($commit['commit']['author']['date'])
                    : now(),
                'payload' => [
                    'message' => mb_substr($message, 0, 500),
                    'url' => $commit['html_url'] ?? null,
                ],
            ]);

            $saved++;
        }

        return $saved;
    }

    private function syncWorkflowRuns(string $repo, string $token, DevEventRecorder $recorder): int
    {
        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(20)
            ->get("https://api.github.com/repos/{$repo}/actions/runs", [
                'per_page' => max(1, min(100, (int) $this->option('runs'))),
            ]);

        if (! $response->successful()) {
            $this->warn('Gagal mengambil workflow run: HTTP '.$response->status());

            return 0;
        }

        $saved = 0;
        $dryRun = (bool) $this->option('dry-run');

        foreach ($response->json()['workflow_runs'] ?? [] as $run) {
            $id = $run['id'] ?? null;
            if ($id === null) {
                continue;
            }

            if ($this->exists('ci', (string) $id)) {
                continue;
            }

            $title = (string) ($run['name'] ?? 'Workflow');

            if ($dryRun) {
                $this->line('  [ci] '.$title.' — '.($run['conclusion'] ?? $run['status'] ?? '?'));

                $saved++;

                continue;
            }

            $recorder->record(DevEvent::TYPE_CI, $title, [
                'area' => 'ci',
                'reference' => (string) $id,
                'status' => $run['conclusion'] ?? $run['status'] ?? null,
                'occurred_at' => isset($run['created_at']) ? Carbon::parse($run['created_at']) : now(),
                'payload' => [
                    'url' => $run['html_url'] ?? null,
                    'head_sha' => $run['head_sha'] ?? null,
                    'event' => $run['event'] ?? null,
                ],
            ]);

            $saved++;
        }

        return $saved;
    }

    private function exists(string $type, string $reference): bool
    {
        return DevEvent::query()
            ->where('type', $type)
            ->where('reference', $reference)
            ->exists();
    }

    private function firstLine(string $message): string
    {
        $line = strtok($message, "\n") ?: $message;

        return Str::limit(trim($line), 191, '');
    }

    /**
     * Tebak area kerja dari file yang diubah pada commit tersebut.
     */
    private function inferArea(string $sha): ?string
    {
        $process = new Process(['git', 'show', '--name-only', '--pretty=format:', $sha], base_path());
        $process->setTimeout(10);
        $process->run();

        if (! $process->isSuccessful()) {
            return null;
        }

        $files = mb_strtolower($process->getOutput());
        $map = [
            'modern' => ['resources/js/modern', 'config/modern-pages', 'modern'],
            'final-report' => ['final-report', 'progress_report'],
            'amandemen' => ['amendment', 'budget_caps'],
            'daily-note' => ['daily-note', 'daily_note'],
            'letter' => ['letter', 'surat'],
            'dashboard' => ['dashboard'],
            'iku' => ['iku', 'verifikasi-luaran'],
            'deploy' => ['update_production.sh', '.github/workflows'],
        ];

        foreach ($map as $area => $needles) {
            foreach ($needles as $needle) {
                if (str_contains($files, $needle)) {
                    return $area;
                }
            }
        }

        return null;
    }

    private function repository(): ?string
    {
        $configured = trim((string) config('observability.history_sync.repo', ''));
        if ($configured !== '') {
            return str_starts_with($configured, 'http') ? $this->parseRepoUrl($configured) : $configured;
        }

        $process = new Process(['git', 'remote', 'get-url', 'origin'], base_path());
        $process->setTimeout(10);
        $process->run();

        if (! $process->isSuccessful()) {
            return null;
        }

        return $this->parseRepoUrl(trim($process->getOutput()));
    }

    private function parseRepoUrl(string $url): ?string
    {
        if (preg_match('#github\.com[:/]([^/]+/[^/.]+)(?:\.git)?$#', $url, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    private function token(): ?string
    {
        $configured = trim((string) config('observability.history_sync.token', ''));
        if ($configured !== '') {
            return $configured;
        }

        if (! config('observability.history_sync.use_gh_cli', true)) {
            return null;
        }

        $process = new Process(['gh', 'auth', 'token']);
        $process->setTimeout(10);
        $process->run();

        $token = trim($process->getOutput());

        return $process->isSuccessful() && $token !== '' ? $token : null;
    }
}
