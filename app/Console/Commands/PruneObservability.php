<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\DevEvent;
use App\Models\ErrorSignature;
use Illuminate\Console\Command;

/**
 * Batasi pertumbuhan tabel observability (mitigasi retensi W4-H2.2/H2.3).
 *
 * - `dev_events`: hapus peristiwa lebih lama dari retensi.
 * - `error_signatures`: hapus HANYA yang sudah fixed/ignored (open/investigating
 *   tetap disimpan karena masih butuh perhatian).
 */
class PruneObservability extends Command
{
    protected $signature = 'observability:prune
        {--days= : Jumlah hari dev_events yang disimpan (default dari config)}
        {--error-days= : Jumlah hari error fixed/ignored yang disimpan}
        {--dry-run : Hitung saja, jangan hapus}';

    protected $description = 'Hapus dev_events lama dan error_signatures yang sudah selesai';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('observability.retention.dev_events_days', 365));
        $errorDays = (int) ($this->option('error-days') ?: config('observability.retention.error_signatures_days', 180));

        if ($days < 1 || $errorDays < 1) {
            $this->error('Jumlah hari harus >= 1.');

            return self::FAILURE;
        }

        $eventsCutoff = now()->subDays($days);
        $errorsCutoff = now()->subDays($errorDays);

        $events = DevEvent::query()->where('occurred_at', '<', $eventsCutoff);
        $errors = ErrorSignature::query()
            ->whereIn('status', [ErrorSignature::STATUS_FIXED, ErrorSignature::STATUS_IGNORED])
            ->where('last_seen_at', '<', $errorsCutoff);

        $eventCount = $events->count();
        $errorCount = $errors->count();

        $this->info('=== Observability Prune ===');
        $this->line("dev_events lebih tua dari {$days} hari: {$eventCount}");
        $this->line("error_signatures (fixed/ignored) lebih tua dari {$errorDays} hari: {$errorCount}");

        if ($this->option('dry-run')) {
            $this->line('DRY RUN — tidak ada data dihapus.');

            return self::SUCCESS;
        }

        $events->delete();
        $errors->delete();

        $this->info('Selesai.');

        return self::SUCCESS;
    }
}
