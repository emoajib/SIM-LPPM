<?php

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * W4-H2.6 — rotasi/retensi log.
 *
 * Kejadian nyata: laravel.log 363 MB, browser.log 46 MB. Tanpa prune, disk
 * produksi bisa penuh dan aplikasi berhenti menulis log.
 */
class PruneLogsTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir().'/sim-lppm-logs-'.uniqid('', true);
        File::ensureDirectoryExists($this->dir);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);

        parent::tearDown();
    }

    public function test_deletes_old_daily_logs(): void
    {
        $old = $this->dir.'/laravel-2020-01-01.log';
        $new = $this->dir.'/laravel-'.now()->format('Y-m-d').'.log';

        File::put($old, 'lama');
        File::put($new, 'baru');

        touch($old, now()->subDays(400)->getTimestamp());

        $this->artisan('logs:prune', [
            '--days' => 14,
            '--max-size' => 50,
            '--path' => $this->dir,
        ])->assertExitCode(0);

        expect(File::exists($old))->toBeFalse()
            ->and(File::exists($new))->toBeTrue();
    }

    public function test_rotates_oversized_active_log_keeping_tail(): void
    {
        $file = $this->dir.'/laravel.log';
        File::put($file, str_repeat('A', 1024 * 1024)."\n".str_repeat('B', 1024 * 1024));

        $this->artisan('logs:prune', [
            '--max-size' => 1,
            '--tail-bytes' => 512,
            '--path' => $this->dir,
        ])->assertExitCode(0);

        $contents = File::get($file);

        expect(strlen($contents))->toBeLessThanOrEqual(512)
            ->and($contents)->toContain('B');
    }

    public function test_dry_run_does_not_modify_files(): void
    {
        $file = $this->dir.'/laravel.log';
        $contents = str_repeat('C', 2048);
        File::put($file, $contents);

        $this->artisan('logs:prune', [
            '--max-size' => 1,
            '--tail-bytes' => 512,
            '--path' => $this->dir,
            '--dry-run' => true,
        ])->assertExitCode(0);

        expect(File::get($file))->toBe($contents);
    }
}
