<?php

/**
 * Vetted by SOSIOMEN - Manual Review Required by Senior Engineer/Manager
 */

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Services\MysqlDumpService;
use Illuminate\Support\Facades\File;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * F8 — snapshot database sebelum migrasi produksi.
 *
 * Snapshot adalah satu-satunya titik pulih bila `migrate --force` gagal di
 * server. Karena itu perilakunya dikunci: gagal = deploy batal, dan retensi
 * tidak boleh menumpuk file tanpa batas.
 */
class DbSnapshotTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir().'/sim-lppm-snapshot-'.uniqid('', true);
        File::ensureDirectoryExists($this->dir, 0700);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);

        parent::tearDown();
    }

    public function test_creates_gzip_snapshot_and_prunes_old_files(): void
    {
        $oldFiles = [];

        foreach ([1, 2, 3] as $i) {
            $path = $this->dir."/snapshot-202601010{$i}_000000.sql.gz";
            File::put($path, 'dump lama');
            touch($path, now()->subDays(10)->getTimestamp());
            $oldFiles[] = $path;
        }

        $this->mock(MysqlDumpService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('dumpTo')
                ->once()
                ->andReturnUsing(function (string $path): array {
                    File::put($path, "CREATE TABLE snapshot_test (id INT);\n");

                    return [true, ''];
                });
        });

        $this->artisan('db:snapshot', [
            '--path' => $this->dir,
            '--label' => 'snapshot',
            '--keep' => 1,
        ])
            ->expectsOutputToContain('Snapshot database dibuat')
            ->assertSuccessful();

        $snapshots = glob($this->dir.'/snapshot-*.sql.gz') ?: [];

        expect($snapshots)->toHaveCount(1);

        foreach ($oldFiles as $file) {
            expect(File::exists($file))->toBeFalse();
        }
    }

    public function test_dry_run_does_not_write_anything(): void
    {
        $this->mock(MysqlDumpService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('dumpTo');
        });

        $this->artisan('db:snapshot', [
            '--path' => $this->dir,
            '--dry-run' => true,
        ])
            ->expectsOutputToContain('Mode dry-run')
            ->assertSuccessful();

        expect(glob($this->dir.'/*') ?: [])->toBe([]);
    }

    public function test_failure_returns_non_zero_and_reports_reason(): void
    {
        $this->mock(MysqlDumpService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('dumpTo')
                ->once()
                ->andReturn([false, 'access denied for user']);
        });

        $this->artisan('db:snapshot', ['--path' => $this->dir])
            ->expectsOutputToContain('Snapshot database gagal: access denied for user')
            ->assertFailed();
    }

    public function test_label_is_sanitized_for_filename(): void
    {
        $this->mock(MysqlDumpService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('dumpTo')->once()->andReturnUsing(function (string $path): array {
                File::put($path, 'x');

                return [true, ''];
            });
        });

        $this->artisan('db:snapshot', [
            '--path' => $this->dir,
            '--label' => 'pre migrate/../2026',
        ])->assertSuccessful();

        $files = glob($this->dir.'/*.sql.gz') ?: [];

        expect($files)->toHaveCount(1);
        expect(basename($files[0]))->toStartWith('pre-migrate-');
    }
}
