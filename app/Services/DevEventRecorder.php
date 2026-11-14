<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DevEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Mencatat peristiwa pengembangan ke tabel `dev_events` (W4-H2.3).
 *
 * Dipakai oleh `bug:capture` sekarang, dan nanti oleh hook deploy / CI.
 * Sama seperti ErrorSignatureRecorder: kegagalan pencatatan tidak boleh
 * menggagalkan proses pemanggil.
 */
class DevEventRecorder
{
    private static ?bool $tableExists = null;

    /**
     * @param  array<string, mixed>  $attributes  area, reference, bc_code, status, payload, occurred_at
     */
    public function record(string $type, string $title, array $attributes = []): ?DevEvent
    {
        if (! config('observability.dev_events_enabled', true)) {
            return null;
        }

        if (! $this->tableExists()) {
            return null;
        }

        try {
            return DevEvent::create(array_merge([
                'type' => $type,
                'title' => mb_substr($title, 0, 191),
                'status' => 'info',
                'occurred_at' => now(),
            ], $attributes));
        } catch (Throwable $failure) {
            Log::debug('Gagal mencatat dev event', [
                'type' => $type,
                'title' => $title,
                'failure' => $failure->getMessage(),
            ]);

            return null;
        }
    }

    private function tableExists(): bool
    {
        if (self::$tableExists === true) {
            return true;
        }

        try {
            self::$tableExists = Schema::hasTable('dev_events');
        } catch (Throwable) {
            return false;
        }

        return self::$tableExists;
    }
}
