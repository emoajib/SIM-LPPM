<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\RateLimiter;

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

        $driver = config('cache.default');

        if ($driver === 'file') {
            $cachePath = storage_path('framework/cache/data');
            if (File::isDirectory($cachePath)) {
                $files = File::files($cachePath);
                $count = 0;
                foreach ($files as $file) {
                    if (str_starts_with($file->getFilename(), 'laravel5')) {
                        File::delete($file->getPathname());
                        $count++;
                    }
                }
                $this->info("Cleared {$count} rate limiter cache files.");
            }
        } elseif ($driver === 'redis') {
            $prefix = config('cache.prefix', 'laravel').'_'.'laravel5';
            Cache::store('redis')->connection()->del(
                Cache::store('redis')->connection()->keys("{$prefix}*")
            );
            $this->info('Cleared rate limiters from Redis.');
        } else {
            // Fallback: flush entire cache (safe during maintenance)
            Cache::flush();
            $this->info('Cleared entire cache (fallback for '.$driver.' driver).');
        }

        $this->info('All rate limiters cleared successfully.');
        return Command::SUCCESS;
    }
}
