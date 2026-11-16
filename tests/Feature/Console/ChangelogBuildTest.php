<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * W4-H3.2 — changelog dibangun dari conventional commit.
 *
 * Changelog manual mudah basi. Test ini memastikan pengelompokan
 * feat/fix/perf/security dan tautan SHA tetap benar.
 */
class ChangelogBuildTest extends TestCase
{
    private string $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo = sys_get_temp_dir().'/changelog-build-'.bin2hex(random_bytes(6));
        mkdir($this->repo, 0777, true);

        $this->git($this->repo, ['init', '-q']);
        $this->git($this->repo, ['config', 'user.email', 'test@example.com']);
        $this->git($this->repo, ['config', 'user.name', 'Test']);
        $this->git($this->repo, ['config', 'commit.gpgsign', 'false']);
        $this->git($this->repo, ['remote', 'add', 'origin', 'https://github.com/emoajib/SIM-LPPM.git']);

        $this->commit($this->repo, 'feat(modern): tambah filter status');
        $this->commit($this->repo, 'fix(auth): perbaiki akses kaprodi');
        $this->commit($this->repo, 'perf(db): kurangi query dashboard');
        $this->commit($this->repo, 'security(policy): tutup IDOR proposal');
        $this->commit($this->repo, 'docs: perbarui readme');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->repo);

        parent::tearDown();
    }

    public function test_builds_grouped_changelog_with_sha_links(): void
    {
        $this->artisan('changelog:build', [
            '--repo-path' => $this->repo,
            '--output' => 'CHANGELOG.md',
        ])->assertExitCode(0);

        $contents = File::get($this->repo.'/CHANGELOG.md');

        $this->assertStringContainsString('## Fitur', $contents);
        $this->assertStringContainsString('## Perbaikan', $contents);
        $this->assertStringContainsString('## Performa', $contents);
        $this->assertStringContainsString('## Keamanan', $contents);
        $this->assertStringContainsString('## Lainnya', $contents);
        $this->assertStringContainsString('tambah filter status', $contents);
        $this->assertStringContainsString('perbaiki akses kaprodi', $contents);
        $this->assertStringContainsString('https://github.com/emoajib/SIM-LPPM/commit/', $contents);
    }

    public function test_dry_run_does_not_write_file(): void
    {
        $exitCode = Artisan::call('changelog:build', [
            '--repo-path' => $this->repo,
            '--output' => 'CHANGELOG.md',
            '--dry-run' => true,
        ]);

        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('## Fitur', $output);
        $this->assertStringContainsString('## Keamanan', $output);
        $this->assertStringContainsString('## Lainnya', $output);
        $this->assertFileDoesNotExist($this->repo.'/CHANGELOG.md');
    }

    public function test_rejects_path_that_is_not_a_git_repository(): void
    {
        $this->artisan('changelog:build', [
            '--repo-path' => $this->repo.'/missing',
        ])->assertExitCode(1);
    }

    private function commit(string $directory, string $message): void
    {
        $file = $directory.'/'.bin2hex(random_bytes(4)).'.txt';
        file_put_contents($file, $message);

        $this->git($directory, ['add', '.']);
        $this->git($directory, ['commit', '-q', '-m', $message]);
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
