<?php

namespace App\Jobs;

use App\Models\Letter;
use App\Services\LetterService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateLetterPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(public string $letterId) {}

    public function handle(LetterService $service): void
    {
        $letter = Letter::find($this->letterId);
        if (! $letter) {
            return;
        }
        try {
            $service->generatePdf($letter);
        } catch (\Throwable $e) {
            Log::error('Queue PDF generation failed', [
                'letter_id' => $this->letterId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('GenerateLetterPdfJob permanently failed', [
            'letter_id' => $this->letterId,
            'error' => $e->getMessage(),
        ]);
    }
}
