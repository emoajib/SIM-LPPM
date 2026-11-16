<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\DevEvent;
use App\Models\ErrorSignature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * W4-H3.2/H3.3 — digest mingguan harus menampilkan empat sinyal.
 *
 * Tanpa rekap otomatis, error baru/CI gagal/fix tanpa test mudah hilang di
 * antara log dan riwayat chat.
 */
class DigestWeeklyTest extends TestCase
{
    use RefreshDatabase;

    private string $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo = sys_get_temp_dir().'/digest-weekly-'.bin2hex(random_bytes(6));
        mkdir($this->repo, 0777, true);

        $this->git($this->repo, ['init', '-q']);
        $this->git($this->repo, ['config', 'user.email', 'test@example.com']);
        $this->git($this->repo, ['config', 'user.name', 'Test']);
        $this->git($this->repo, ['config', 'commit.gpgsign', 'false']);

        $this->commit($this->repo, 'fix(auth): perbaiki guard', 'No-Regression-Test: perubahan hanya komentar.');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->repo);

        parent::tearDown();
    }

    public function test_dry_run_contains_error_ci_fix_and_missing_guard_sections(): void
    {
        ErrorSignature::create([
            'fingerprint' => str_repeat('a', 64),
            'exception_class' => 'RuntimeException',
            'message' => 'boom',
            'file' => 'app/Http/Controllers/TestController.php',
            'line' => 42,
            'module' => 'modern',
            'bc_code' => 'BC-06',
            'status' => ErrorSignature::STATUS_OPEN,
            'occurrences' => 2,
            'first_seen_at' => now()->subDay(),
            'last_seen_at' => now()->subHour(),
        ]);

        DevEvent::create([
            'type' => DevEvent::TYPE_CI,
            'area' => 'ci',
            'title' => 'CI — Test & Code Quality',
            'reference' => '12345',
            'status' => 'failure',
            'occurred_at' => now()->subHour(),
            'payload' => ['url' => 'https://github.com/emoajib/SIM-LPPM/actions/runs/12345'],
        ]);

        $exitCode = Artisan::call('digest:weekly', [
            '--repo-path' => $this->repo,
            '--weeks' => 1,
            '--dry-run' => true,
        ]);

        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('RuntimeException', $output);
        $this->assertStringContainsString('CI — Test & Code Quality', $output);
        $this->assertStringContainsString('perbaiki guard', $output);
        $this->assertStringContainsString('No-Regression-Test', $output);
        $this->assertStringContainsString('BC tanpa penjaga', $output);
        $this->assertStringContainsString('BC-05', $output);
        $this->assertDatabaseMissing('dev_events', ['type' => DevEvent::TYPE_DIGEST]);
    }

    public function test_writes_markdown_and_records_digest_event(): void
    {
        $outputPath = $this->repo.'/digest.md';

        $exitCode = Artisan::call('digest:weekly', [
            '--repo-path' => $this->repo,
            '--weeks' => 1,
            '--output' => $outputPath,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertFileExists($outputPath);

        $contents = File::get($outputPath);
        $this->assertStringContainsString('## Ringkasan', $contents);
        $this->assertStringContainsString('## BC tanpa penjaga', $contents);

        $this->assertDatabaseHas('dev_events', [
            'type' => DevEvent::TYPE_DIGEST,
            'area' => 'observability',
            'status' => 'info',
        ]);
    }

    public function test_rejects_zero_weeks(): void
    {
        $this->artisan('digest:weekly', ['--weeks' => 0])
            ->assertExitCode(1);
    }

    private function commit(string $directory, string $subject, string $body = ''): void
    {
        $file = $directory.'/'.bin2hex(random_bytes(4)).'.txt';
        file_put_contents($file, $subject);

        $this->git($directory, ['add', '.']);

        $arguments = ['commit', '-q', '-m', $subject];
        if ($body !== '') {
            $arguments = array_merge($arguments, ['-m', $body]);
        }

        $this->git($directory, $arguments);
    }

    /**
     * @param  array<int, string>  $arguments
     */
    private function git(string $directory, array $arguments): void
    {
        $process = new Process(array_merge(['git'], $arguments), $directory);
        $process->setTimeout(20);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('Git gagal: '.trim($process->getErrorOutput()));
        }
    }
}
