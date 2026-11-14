<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CheckTheme extends Command
{
    protected $signature = 'theme:check';

    protected $description = 'Guard kontrak tema (BC-01, BC-02): cegah leak partial modern & hasRole di view.';

    public function handle(): int
    {
        $this->info('=== Theme Check ===');

        $violations = [];

        // Aturan 1: tidak boleh ->hasRole( di resources/views (BC-02)
        foreach (File::allFiles(base_path('resources/views')) as $file) {
            foreach (preg_split('/\R/', $file->getContents()) as $i => $line) {
                if (str_contains($line, '->hasRole(')) {
                    $violations[] = $file->getRelativePathname().':'.($i + 1).' uses ->hasRole( (pakai activeHasRole)';
                }
            }
        }

        // Aturan 2: tidak boleh literal sim_lppm_app_theme di view (pakai is_modern_theme())
        foreach (File::allFiles(base_path('resources/views')) as $file) {
            foreach (preg_split('/\R/', $file->getContents()) as $i => $line) {
                if (str_contains($line, 'sim_lppm_app_theme')) {
                    $violations[] = $file->getRelativePathname().':'.($i + 1).' literal tema (pakai is_modern_theme())';
                }
            }
        }

        // Aturan 3: setiap include partial modern wajib diawali guard is_modern_theme()
        // pada baris sebelumnya (BC-01).
        foreach (File::allFiles(base_path('resources/views')) as $file) {
            $lines = preg_split('/\R/', $file->getContents());
            foreach ($lines as $i => $line) {
                if (str_contains($line, "include('components.partials.module-tabs'") ||
                    str_contains($line, 'include("components.partials.module-tabs"')) {
                    $prev = $i > 0 ? trim($lines[$i - 1]) : '';
                    if ($prev !== '@if (is_modern_theme())') {
                        $violations[] = $file->getRelativePathname().':'.($i + 1).' include module-tabs tanpa guard is_modern_theme()';
                    }
                }
            }
        }

        if (empty($violations)) {
            $this->info('Tidak ada pelanggaran tema: OK');

            return self::SUCCESS;
        }

        foreach ($violations as $v) {
            $this->error($v);
        }
        $this->error(count($violations).' pelanggaran ditemukan.');

        return self::FAILURE;
    }
}
