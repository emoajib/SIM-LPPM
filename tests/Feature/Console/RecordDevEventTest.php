<?php

namespace Tests\Feature\Console;

use App\Models\DevEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * W4-H2.5 — `dev:record` dipakai hook deploy.
 *
 * Deploy tidak boleh gagal hanya karena pencatatan gagal, tetapi event sukses
 * wajib tercatat agar jejak deploy tidak bergantung pada log GitHub.
 */
class RecordDevEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_records_a_deploy_event(): void
    {
        $this->artisan('dev:record', [
            'type' => DevEvent::TYPE_DEPLOY,
            'title' => 'Deploy abc1234',
            '--area' => 'produksi',
            '--ref' => 'abc1234',
            '--status' => 'success',
        ])->assertExitCode(0);

        $this->assertDatabaseHas('dev_events', [
            'type' => DevEvent::TYPE_DEPLOY,
            'area' => 'produksi',
            'reference' => 'abc1234',
            'status' => 'success',
        ]);
    }

    public function test_requires_type_and_title(): void
    {
        $this->artisan('dev:record', ['type' => 'deploy', 'title' => ''])->assertExitCode(1);
    }

    public function test_exits_zero_when_recording_is_disabled(): void
    {
        config(['observability.dev_events_enabled' => false]);

        $this->artisan('dev:record', [
            'type' => DevEvent::TYPE_DEPLOY,
            'title' => 'Deploy saat observability mati',
        ])->assertExitCode(0);

        expect(DevEvent::query()->count())->toBe(0);
    }
}
