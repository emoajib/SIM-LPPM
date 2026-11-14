<?php

namespace Tests\Feature\Console;

use App\Models\DevEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * W4-H2.4 — history:sync menarik commit + status CI ke dev_events.
 *
 * Idempotensi penting: penjadwalan mingguan tidak boleh menduplikasi baris.
 */
class HistorySyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'observability.history_sync.repo' => 'emoajib/SIM-LPPM',
            'observability.history_sync.token' => 'test-token',
        ]);
    }

    public function test_imports_commits_and_workflow_runs(): void
    {
        Http::fake([
            'api.github.com/repos/emoajib/SIM-LPPM/commits*' => Http::response([
                [
                    'sha' => 'abcdef1234567890',
                    'commit' => [
                        'message' => "feat(modern): filter paritas\n\nDetail panjang",
                        'author' => ['date' => '2026-11-14T10:00:00Z'],
                    ],
                    'html_url' => 'https://github.com/emoajib/SIM-LPPM/commit/abcdef1234567890',
                ],
            ]),
            'api.github.com/repos/emoajib/SIM-LPPM/actions/runs*' => Http::response([
                'workflow_runs' => [
                    [
                        'id' => 42,
                        'name' => 'CI — Test & Code Quality',
                        'conclusion' => 'success',
                        'created_at' => '2026-11-14T10:05:00Z',
                        'html_url' => 'https://github.com/emoajib/SIM-LPPM/actions/runs/42',
                        'head_sha' => 'abcdef1234567890',
                        'event' => 'push',
                    ],
                ],
            ]),
        ]);

        $this->artisan('history:sync', ['--days' => 7])->assertExitCode(0);

        $this->assertDatabaseHas('dev_events', [
            'type' => DevEvent::TYPE_COMMIT,
            'reference' => 'abcdef1234567890',
        ]);

        $this->assertDatabaseHas('dev_events', [
            'type' => DevEvent::TYPE_CI,
            'reference' => '42',
            'status' => 'success',
        ]);
    }

    public function test_second_sync_does_not_duplicate_rows(): void
    {
        Http::fake([
            'api.github.com/repos/emoajib/SIM-LPPM/commits*' => Http::response([
                [
                    'sha' => 'abcdef1234567890',
                    'commit' => ['message' => 'fix: sesuatu', 'author' => ['date' => '2026-11-14T10:00:00Z']],
                    'html_url' => 'https://github.com/emoajib/SIM-LPPM/commit/abcdef1234567890',
                ],
            ]),
            'api.github.com/repos/emoajib/SIM-LPPM/actions/runs*' => Http::response(['workflow_runs' => []]),
        ]);

        $this->artisan('history:sync')->assertExitCode(0);
        $this->artisan('history:sync')->assertExitCode(0);

        expect(DevEvent::query()->where('type', DevEvent::TYPE_COMMIT)->count())->toBe(1);
    }

    public function test_dry_run_does_not_write(): void
    {
        Http::fake([
            'api.github.com/repos/emoajib/SIM-LPPM/commits*' => Http::response([
                [
                    'sha' => 'abcdef1234567890',
                    'commit' => ['message' => 'feat: dry run', 'author' => ['date' => '2026-11-14T10:00:00Z']],
                    'html_url' => 'https://github.com/emoajib/SIM-LPPM/commit/abcdef1234567890',
                ],
            ]),
            'api.github.com/repos/emoajib/SIM-LPPM/actions/runs*' => Http::response(['workflow_runs' => []]),
        ]);

        $this->artisan('history:sync', ['--dry-run' => true])->assertExitCode(0);

        expect(DevEvent::query()->count())->toBe(0);
    }

    public function test_fails_without_token(): void
    {
        config([
            'observability.history_sync.token' => null,
            'observability.history_sync.use_gh_cli' => false,
        ]);

        // Tanpa token, perintah tidak boleh memanggil API.
        Http::fake();

        $this->artisan('history:sync')->assertExitCode(1);

        Http::assertNothingSent();
    }
}
