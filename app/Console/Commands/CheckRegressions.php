<?php

namespace App\Console\Commands;

use App\Support\RegressionRegistry;
use Illuminate\Console\Command;

class CheckRegressions extends Command
{
    protected $signature = 'regression:check';

    protected $description = 'Verifikasi dua arah: setiap BC-xx di docs/regressions/ punya test penjaga, dan setiap marker "Regression: BC-xx" di test merujuk ke registry.';

    public function handle(RegressionRegistry $registry): int
    {
        $this->info('=== Regression Check ===');

        $docCodes = $registry->codes();
        $testFiles = $registry->guards();
        $testCodes = array_keys($testFiles);

        // 1. BC di test yang tidak ada di registry
        $missingInDocs = array_diff($testCodes, $docCodes);
        if (! empty($missingInDocs)) {
            $this->error('BC di test tapi tidak ada di docs/regressions/: '.implode(', ', $missingInDocs));
            foreach ($missingInDocs as $code) {
                foreach ($testFiles[$code] as $f) {
                    $this->line("   - {$code}: {$f}");
                }
            }
        } else {
            $this->info('Semua BC di test ada di registry: OK');
        }

        // 2. BC di registry tanpa test penjaga
        $missingInTests = array_diff($docCodes, $testCodes);
        if (! empty($missingInTests)) {
            $this->warn('BC di registry tanpa test penjaga (tambahkan "Regression: BC-xx" di docblock test): '.implode(', ', $missingInTests));
        } else {
            $this->info('Semua BC di registry punya test penjaga: OK');
        }

        // 3. Ringkasan
        $this->line('Registry: '.count($docCodes).' kelas | Test markers: '.count($testCodes).' kelas');

        if (! empty($missingInDocs)) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
