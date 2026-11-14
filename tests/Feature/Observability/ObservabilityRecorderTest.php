<?php

namespace Tests\Feature\Observability;

use App\Models\DevEvent;
use App\Models\ErrorSignature;
use App\Services\DevEventRecorder;
use App\Services\ErrorSignatureRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

/**
 * W4-H2.2/H2.3 — recorder error & dev event.
 *
 * Fokus mitigasi: dedupe (satu baris per kelas error), tidak melempar exception,
 * dan tidak mencatat error 4xx yang merupakan perilaku normal.
 */
class ObservabilityRecorderTest extends TestCase
{
    use RefreshDatabase;

    private ErrorSignatureRecorder $errors;

    private DevEventRecorder $events;

    protected function setUp(): void
    {
        parent::setUp();

        config(['observability.error_signatures.allow_in_tests' => true]);

        $this->errors = app(ErrorSignatureRecorder::class);
        $this->events = app(DevEventRecorder::class);
    }

    public function test_records_a_new_error_signature(): void
    {
        $signature = $this->errors->record(new \RuntimeException('Koneksi database gagal'));

        expect($signature)->not->toBeNull()
            ->and($signature->status)->toBe(ErrorSignature::STATUS_OPEN)
            ->and($signature->occurrences)->toBe(1)
            ->and(ErrorSignature::query()->count())->toBe(1);
    }

    public function test_repeated_errors_increment_occurrences_instead_of_creating_rows(): void
    {
        $exception = new \RuntimeException('Koneksi database gagal');

        $this->errors->record($exception);
        $this->errors->record($exception);

        expect(ErrorSignature::query()->count())->toBe(1)
            ->and(ErrorSignature::query()->value('occurrences'))->toBe(2);
    }

    public function test_different_line_produces_different_signature(): void
    {
        $this->errors->record(new \RuntimeException('Gagal', 0, null));
        $this->errors->record(new \RuntimeException('Gagal'));

        // Kedua exception dibuat di baris berbeda pada file test ini.
        expect(ErrorSignature::query()->count())->toBe(2);
    }

    public function test_http_404_is_not_recorded(): void
    {
        $this->errors->record(new NotFoundHttpException('Not found'));

        expect(ErrorSignature::query()->count())->toBe(0);
    }

    public function test_recorder_is_disabled_by_config(): void
    {
        config(['observability.error_signatures.enabled' => false]);

        $signature = $this->errors->record(new \RuntimeException('Tidak dicatat'));

        expect($signature)->toBeNull()
            ->and(ErrorSignature::query()->count())->toBe(0);
    }

    public function test_fingerprint_is_stable_despite_uuid_in_message(): void
    {
        $make = fn () => new \RuntimeException('Proposal '.(string) Str::uuid().' tidak ditemukan');

        $this->errors->record($make());
        $this->errors->record($make());

        expect(ErrorSignature::query()->count())->toBe(1)
            ->and(ErrorSignature::query()->value('occurrences'))->toBe(2);
    }

    public function test_dev_event_is_recorded_with_defaults(): void
    {
        $event = $this->events->record(DevEvent::TYPE_DEPLOY, 'Deploy 2026-11-15', [
            'area' => 'produksi',
            'reference' => 'abc1234',
            'status' => 'success',
        ]);

        expect($event)->not->toBeNull()
            ->and($event->status)->toBe('success')
            ->and($event->occurred_at)->not->toBeNull();

        $this->assertDatabaseHas('dev_events', [
            'type' => DevEvent::TYPE_DEPLOY,
            'area' => 'produksi',
        ]);
    }

    public function test_dev_event_recorder_is_disabled_by_config(): void
    {
        config(['observability.dev_events_enabled' => false]);

        expect($this->events->record(DevEvent::TYPE_BUG, 'Tidak dicatat'))->toBeNull()
            ->and(DevEvent::query()->count())->toBe(0);
    }
}
