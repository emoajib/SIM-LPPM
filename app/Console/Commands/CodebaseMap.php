<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

/**
 * Peta kodebase ringkas untuk agent & engineer baru (W4-H3.1).
 *
 * Tujuan: sebelum menyentuh area apa pun, agent bisa membaca satu file
 * (`docs/history/codebase-map.md`) untuk tahu di mana controller, komponen
 * Livewire, halaman React, command, test, dan kelas bug berada — tanpa
 * menjelajah ulang seluruh repo setiap sesi.
 *
 * Pemakaian: php artisan codebase:map
 */
class CodebaseMap extends Command
{
    protected $signature = 'codebase:map {--stdout : Cetak ke layar, jangan tulis file}';

    protected $description = 'Buat docs/history/codebase-map.md (peta struktur kodebase)';

    private const OUTPUT_PATH = 'docs/history/codebase-map.md';

    public function handle(): int
    {
        $map = $this->buildMap();

        if ($this->option('stdout')) {
            $this->line($map);

            return self::SUCCESS;
        }

        $path = base_path(self::OUTPUT_PATH);
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $map);

        $this->info('Peta kodebase ditulis ke '.self::OUTPUT_PATH);

        return self::SUCCESS;
    }

    private function buildMap(): string
    {
        $lines = [];
        $lines[] = '# Peta Kodebase (dibuat otomatis)';
        $lines[] = '';
        $lines[] = '> Dihasilkan oleh `php artisan codebase:map`. **Jangan edit manual** —';
        $lines[] = '> jalankan ulang perintahnya setelah menambah route/komponen/test.';
        $lines[] = '> Dibuat: '.now()->format('Y-m-d');
        $lines[] = '';

        $lines[] = '## Halaman modern (React)';
        $lines[] = '';
        $lines[] = '| Route | Komponen (PAGES) |';
        $lines[] = '|---|---|';
        foreach (config('modern-pages.pages', []) as $routeName => $component) {
            $lines[] = '| `'.$routeName.'` | `'.$component.'` |';
        }
        $lines[] = '';

        $lines[] = '## Route per modul (jumlah)';
        $lines[] = '';
        $lines[] = '| Prefix | Jumlah route |';
        $lines[] = '|---|---|';
        foreach ($this->routeCounts() as $prefix => $count) {
            $lines[] = '| `'.$prefix.'` | '.$count.' |';
        }
        $lines[] = '';

        $lines[] = '## Controller';
        $lines[] = '';
        $lines = array_merge($lines, $this->compactFileList('app/Http/Controllers'));
        $lines[] = '';

        $lines[] = '## Komponen Livewire';
        $lines[] = '';
        $lines = array_merge($lines, $this->compactFileList('app/Livewire'));
        $lines[] = '';

        $lines[] = '## Artisan command';
        $lines[] = '';
        $lines = array_merge($lines, $this->compactFileList('app/Console/Commands'));
        $lines[] = '';

        $lines[] = '## Model';
        $lines[] = '';
        $lines = array_merge($lines, $this->compactFileList('app/Models'));
        $lines[] = '';

        $lines[] = '## Test (per folder)';
        $lines[] = '';
        $lines[] = '| Folder | Jumlah file |';
        $lines[] = '|---|---|';
        foreach ($this->testCounts() as $folder => $count) {
            $lines[] = '| `'.$folder.'` | '.$count.' |';
        }
        $lines[] = '';

        $lines[] = '## Kelas bug (registry regresi)';
        $lines[] = '';
        foreach (File::glob(base_path('docs/regressions/BC-*.md')) as $file) {
            $lines[] = '- `'.basename($file).'`';
        }
        $lines[] = '';

        $lines[] = '## Dokumen acuan';
        $lines[] = '';
        $lines[] = '- `AGENTS.md` — workflow & Definition of Done';
        $lines[] = '- `docs/plans/master-plan.md` — urutan eksekusi & mitigasi';
        $lines[] = '- `docs/plans/gap-analysis-modern-classic.md` — gap tema';
        $lines[] = '- `docs/regressions/README.md` — registry kelas bug';
        $lines[] = '';

        return implode("\n", $lines);
    }

    /**
     * Jumlah route per segmen pertama URI.
     *
     * @return array<string, int>
     */
    private function routeCounts(): array
    {
        $counts = [];
        foreach (Route::getRoutes()->getRoutes() as $route) {
            $uri = $route->uri();
            $prefix = str_contains($uri, '/') ? strstr($uri, '/', true) : $uri;
            $prefix = $prefix === '' ? '/' : (string) $prefix;
            $counts[$prefix] = ($counts[$prefix] ?? 0) + 1;
        }

        arsort($counts);

        return $counts;
    }

    /**
     * Daftar file per subfolder, dipadatkan satu baris per folder.
     *
     * @return array<int, string>
     */
    private function compactFileList(string $directory): array
    {
        $base = base_path($directory);
        if (! File::isDirectory($base)) {
            return ['(tidak ada)'];
        }

        $groups = [];
        foreach (File::allFiles($base) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $relativeDir = str_replace(base_path().'/', '', $file->getPath());
            $groups[$relativeDir][] = $file->getFilename();
        }

        ksort($groups);

        $lines = [];
        foreach ($groups as $dir => $names) {
            sort($names);
            $lines[] = '- `'.$dir.'` ('.count($names).'): '
                .implode(', ', array_map(fn (string $name) => '`'.$name.'`', $names));
        }

        return $lines === [] ? ['(tidak ada)'] : $lines;
    }

    /**
     * @return array<string, int>
     */
    private function testCounts(): array
    {
        $base = base_path('tests');
        $counts = [];

        if (! File::isDirectory($base)) {
            return $counts;
        }

        foreach (File::allFiles($base) as $file) {
            $relative = str_replace(base_path().'/', '', $file->getPathname());
            $folder = dirname(str_replace('tests/', '', $relative));
            $counts[$folder] = ($counts[$folder] ?? 0) + 1;
        }

        ksort($counts);

        return $counts;
    }
}
