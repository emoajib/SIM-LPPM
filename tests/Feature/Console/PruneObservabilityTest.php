<?php

namespace Tests\Feature\Console;

use App\Models\DevEvent;
use App\Models\ErrorSignature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * W4-H2.2/H2.3 — mitigasi pertumbuhan tabel observability.
 *
 * Tanpa retensi, tabel ini akan tumbuh tanpa batas (mirip laravel.log 363 MB).
 */
class PruneObservabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_prunes_old_dev_events_only(): void
    {
        $old = DevEvent::create([
            'type' => DevEvent::TYPE_BUG,
            'title' => 'Bug lama',
            'occurred_at' => now()->subDays(400),
        ]);

        $recent = DevEvent::create([
            'type' => DevEvent::TYPE_BUG,
            'title' => 'Bug baru',
            'occurred_at' => now()->subDays(10),
        ]);

        $this->artisan('observability:prune', ['--days' => 365])->assertExitCode(0);

        $this->assertDatabaseMissing('dev_events', ['id' => $old->id]);
        $this->assertDatabaseHas('dev_events', ['id' => $recent->id]);
    }

    public function test_prunes_only_resolved_error_signatures(): void
    {
        $fixed = ErrorSignature::create([
            'fingerprint' => str_repeat('a', 64),
            'exception_class' => \RuntimeException::class,
            'status' => ErrorSignature::STATUS_FIXED,
            'occurrences' => 3,
            'last_seen_at' => now()->subDays(400),
        ]);

        $open = ErrorSignature::create([
            'fingerprint' => str_repeat('b', 64),
            'exception_class' => \RuntimeException::class,
            'status' => ErrorSignature::STATUS_OPEN,
            'occurrences' => 1,
            'last_seen_at' => now()->subDays(400),
        ]);

        $this->artisan('observability:prune', ['--error-days' => 180])->assertExitCode(0);

        $this->assertDatabaseMissing('error_signatures', ['id' => $fixed->id]);
        $this->assertDatabaseHas('error_signatures', ['id' => $open->id]);
    }

    public function test_dry_run_does_not_delete(): void
    {
        $event = DevEvent::create([
            'type' => DevEvent::TYPE_BUG,
            'title' => 'Bug lama',
            'occurred_at' => now()->subDays(400),
        ]);

        $this->artisan('observability:prune', ['--days' => 365, '--dry-run' => true])->assertExitCode(0);

        $this->assertDatabaseHas('dev_events', ['id' => $event->id]);
    }
}
