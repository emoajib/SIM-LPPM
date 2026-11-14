<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CheckRegressions extends Command
{
    protected $signature = 'regression:check';

    protected $description = 'Verifikasi dua arah: setiap BC-xx di docs/regressions/ punya test penjaga, dan setiap marker "Regression: BC-xx" di test merujuk ke registry.';

    public function handle(): int
    {
        $this->info('=== Regression Check ===');

        $docsDir = base_path('docs/regressions');
        $docCodes = [];
        if (is_dir($docsDir)) {
            foreach (File::glob($docsDir.'/BC-*.md') as $file) {
                if (preg_match('/BC-(\d{2})/', basename($file), $m)) {
                    $docCodes[] = 'BC-'.$m[1];
                }
            }
        }
        $docCodes = array_values(array_unique($docCodes));

        $testCodes = [];
        $testFiles = [];
        foreach (File::allFiles(base_path('tests')) as $file) {
            if (! str_ends_with($file->getFilename(), '.php')) {
                continue;
            }
            $content = $file->getContents();
            if (preg_match_all('/Regression:\s*BC-(\d{2})/', $content, $m)) {
                foreach ($m[1] as $code) {
                    $testCodes[] = 'BC-'.$code;
                    $testFiles['BC-'.$code][] = str_replace(base_path().'/', '', $file->getPathname());
                }
            }
        }
        $testCodes = array_values(array_unique($testCodes));

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
