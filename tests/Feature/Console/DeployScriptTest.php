<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use RuntimeException;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * Guard script deploy produksi (F5-03).
 *
 * Script deploy adalah jalur pemulihan paling penting: kegagalan yang tidak
 * menyalakan kembali situs, atau `git clean/checkout` yang menghapus file
 * produksi, harus gagal di CI sebelum menyentuh server.
 */
class DeployScriptTest extends TestCase
{
    private function script(): string
    {
        return (string) file_get_contents(base_path('update_production.sh'));
    }

    public function test_script_has_valid_bash_syntax(): void
    {
        $process = new Process(['bash', '-n', base_path('update_production.sh')]);
        $process->setTimeout(10);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(trim($process->getErrorOutput()));
        }

        $this->assertTrue($process->isSuccessful());
    }

    public function test_failure_path_restores_maintenance_mode(): void
    {
        $script = $this->script();

        $this->assertStringContainsString('trap on_exit EXIT', $script);
        $this->assertStringContainsString('MAINTENANCE_ON=1', $script);
        $this->assertStringContainsString('php artisan up 2>/dev/null || true', $script);
    }

    public function test_does_not_use_destructive_git_reset_or_clean(): void
    {
        $script = $this->script();

        $this->assertStringNotContainsString('git checkout -- .', $script);
        $this->assertStringNotContainsString('git clean -fd', $script);
        $this->assertStringContainsString('git pull --ff-only origin main', $script);
    }

    public function test_backup_excludes_env_and_smoke_test_covers_key_urls(): void
    {
        $script = $this->script();

        $this->assertStringContainsString('--exclude="./.env"', $script);
        $this->assertStringContainsString('smoke_check "homepage"', $script);
        $this->assertStringContainsString('smoke_check "login"', $script);
        $this->assertStringContainsString('smoke_check "dashboard"', $script);
        $this->assertStringContainsString('smoke_check "research"', $script);
        $this->assertStringContainsString('smoke_check "community-service"', $script);
        $this->assertStringContainsString('smoke_check "health-check"', $script);
    }

    public function test_takes_database_snapshot_before_running_migrations(): void
    {
        $script = $this->script();

        $this->assertStringContainsString('php artisan db:snapshot', $script);

        // Pemulihan harus manual + punya preview, bukan drop-all otomatis.
        $this->assertStringContainsString('app:restore-backup', $script);
        $this->assertStringNotContainsString('DROP TABLE IF EXISTS', $script);

        $snapshotAt = strpos($script, 'php artisan db:snapshot');
        $migrateAt = strpos($script, 'php artisan migrate --force');

        $this->assertNotFalse($snapshotAt);
        $this->assertNotFalse($migrateAt);
        $this->assertLessThan($migrateAt, $snapshotAt);
    }

    public function test_does_not_delete_media_records_by_hardcoded_id(): void
    {
        $script = $this->script();

        // Mode --ids menghapus record tanpa cek orphan; ID 97/99 pernah dipakai
        // di produksi dan tidak boleh terhapus otomatis setiap deploy.
        $this->assertStringNotContainsString('php artisan media:cleanup-orphans', $script);
    }

    public function test_checks_mysqldump_before_entering_maintenance(): void
    {
        $script = $this->script();

        $checkAt = strpos($script, 'command -v mysqldump');
        $downAt = strpos($script, 'php artisan down');

        $this->assertNotFalse($checkAt);
        $this->assertNotFalse($downAt);
        $this->assertLessThan($downAt, $checkAt);
    }
}
