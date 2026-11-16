<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * W4-H3.3 — regression:health adalah read-only status registry.
 *
 * Status harus konsisten dengan `regression:check`: BC di registry tanpa marker
 * test dianggap belum punya penjaga.
 */
class RegressionHealthTest extends TestCase
{
    public function test_returns_json_summary_and_entries(): void
    {
        $exitCode = Artisan::call('regression:health', ['--json' => true]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);

        $payload = json_decode($output, true, 512, JSON_THROW_ON_ERROR);

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('generated_at', $payload);
        $this->assertArrayHasKey('summary', $payload);
        $this->assertArrayHasKey('entries', $payload);
        $this->assertArrayHasKey('total', $payload['summary']);
        $this->assertArrayHasKey('guarded', $payload['summary']);
        $this->assertArrayHasKey('missing', $payload['summary']);
        $this->assertNotEmpty($payload['entries']);
        $this->assertArrayHasKey('code', $payload['entries'][0]);
        $this->assertArrayHasKey('guarded', $payload['entries'][0]);
        $this->assertArrayHasKey('tests', $payload['entries'][0]);
        $this->assertArrayHasKey('last_commit', $payload['entries'][0]);
    }

    public function test_table_output_contains_registry_summary(): void
    {
        $exitCode = Artisan::call('regression:health');
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('=== Regression Health ===', $output);
        $this->assertStringContainsString('Registry:', $output);
        $this->assertStringContainsString('BC-01', $output);
    }
}
