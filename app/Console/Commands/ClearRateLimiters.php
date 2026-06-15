<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearRateLimiters extends Command
{
    protected $signature = 'rate-limiter:clear {--force : skip confirmation}';

    protected $description = 'Clear all rate limiter cache entries (login locks, etc.)';

    public function handle(): int
    {
        if (! $this->option('force')) {
            $this->warn('This will clear all rate limiters (login locks, etc.).');
            $this->warn('Users currently locked out will be able to try again immediately.');
            if (! $this->confirm('Continue?')) {
                return Command::SUCCESS;
            }
        }

        Cache::flush();

        $this->info('All rate limiters cleared successfully.');

        return Command::SUCCESS;
    }
}
