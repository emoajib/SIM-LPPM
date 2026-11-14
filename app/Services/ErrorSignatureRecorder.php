<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ErrorSignature;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Mencatat kelas error ke tabel `error_signatures` (W4-H2.2).
 *
 * Prinsip: observability TIDAK BOLEH menggagalkan request. Semua kegagalan
 * (DB down, tabel belum dimigrasikan, kolom terlalu panjang) ditelan dan
 * hanya dicatat sebagai debug log.
 */
class ErrorSignatureRecorder
{
    /**
     * Cache positif saja: kalau tabel sudah ada, tidak perlu cek ulang.
     */
    private static ?bool $tableExists = null;

    public function record(Throwable $e, ?string $module = null, ?string $bcCode = null): ?ErrorSignature
    {
        if (! $this->enabled()) {
            return null;
        }

        if ($this->shouldIgnore($e)) {
            return null;
        }

        if (! $this->tableExists()) {
            return null;
        }

        $fingerprint = self::fingerprint($e);
        $now = now();

        try {
            return $this->persist($fingerprint, $e, $module, $bcCode, $now);
        } catch (QueryException $race) {
            // Kemungkinan dua request menulis fingerprint yang sama bersamaan.
            return $this->incrementExisting($fingerprint, $now);
        } catch (Throwable $failure) {
            Log::debug('Gagal mencatat error signature', [
                'exception' => $e::class,
                'failure' => $failure->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Fingerprint stabil: kelas + pesan ternormalisasi + file relatif + baris.
     *
     * Normalisasi mencegah ledakan baris hanya karena pesan berisi UUID/angka
     * yang berbeda di setiap request.
     */
    public static function fingerprint(Throwable $e): string
    {
        return hash('sha256', implode('|', [
            $e::class,
            self::normalizeMessage($e->getMessage()),
            self::relativeFile($e->getFile()),
            (string) $e->getLine(),
        ]));
    }

    public static function normalizeMessage(string $message): string
    {
        $normalized = preg_replace(
            '/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i',
            '{uuid}',
            $message
        ) ?? $message;

        $normalized = preg_replace('/\b\d{4,}\b/', '{n}', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        return mb_substr(trim($normalized), 0, 500);
    }

    public static function relativeFile(string $file): string
    {
        $base = base_path().DIRECTORY_SEPARATOR;

        return str_starts_with($file, $base) ? substr($file, strlen($base)) : $file;
    }

    private function persist(
        string $fingerprint,
        Throwable $e,
        ?string $module,
        ?string $bcCode,
        \DateTimeInterface $now
    ): ErrorSignature {
        /** @var ErrorSignature|null $existing */
        $existing = ErrorSignature::query()->where('fingerprint', $fingerprint)->first();

        if ($existing !== null) {
            $existing->forceFill([
                'occurrences' => $existing->occurrences + 1,
                'last_seen_at' => $now,
                'module' => $module ?? $existing->module,
                'bc_code' => $bcCode ?? $existing->bc_code,
            ])->save();

            return $existing;
        }

        return ErrorSignature::create([
            'fingerprint' => $fingerprint,
            'exception_class' => $e::class,
            'message' => mb_substr($e->getMessage(), 0, 2000),
            'file' => mb_substr(self::relativeFile($e->getFile()), 0, 500),
            'line' => $e->getLine() > 0 ? $e->getLine() : null,
            'module' => $module,
            'bc_code' => $bcCode,
            'status' => ErrorSignature::STATUS_OPEN,
            'occurrences' => 1,
            'first_seen_at' => $now,
            'last_seen_at' => $now,
        ]);
    }

    private function incrementExisting(string $fingerprint, \DateTimeInterface $now): ?ErrorSignature
    {
        try {
            $existing = ErrorSignature::query()->where('fingerprint', $fingerprint)->first();
            if ($existing === null) {
                return null;
            }

            $existing->increment('occurrences', 1, ['last_seen_at' => $now]);

            return $existing->refresh();
        } catch (Throwable) {
            return null;
        }
    }

    private function enabled(): bool
    {
        if (! config('observability.error_signatures.enabled', true)) {
            return false;
        }

        if (app()->runningUnitTests() && ! config('observability.error_signatures.allow_in_tests', false)) {
            return false;
        }

        return true;
    }

    private function shouldIgnore(Throwable $e): bool
    {
        foreach ((array) config('observability.error_signatures.ignore', []) as $class) {
            if (is_string($class) && $e instanceof $class) {
                return true;
            }
        }

        return false;
    }

    private function tableExists(): bool
    {
        if (self::$tableExists === true) {
            return true;
        }

        try {
            self::$tableExists = Schema::hasTable('error_signatures');
        } catch (Throwable) {
            return false;
        }

        return self::$tableExists;
    }
}
