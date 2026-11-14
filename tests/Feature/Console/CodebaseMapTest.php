<?php

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Regression: BC-03/BC-08 — `codebase:map` adalah peta orientasi kodebase.
 *
 * Kalau perintah ini rusak, agent/engineer kehilangan satu-satunya ringkasan
 * struktur (controller, Livewire, halaman React, command, test, kelas bug) dan
 * harus menjelajah ulang repo — biaya konteks besar dan rawan melewatkan guard.
 */
class CodebaseMapTest extends TestCase
{
    private function map(): string
    {
        expect(Artisan::call('codebase:map', ['--stdout' => true]))->toBe(0);

        return Artisan::output();
    }

    public function test_prints_the_map_to_stdout_without_writing_the_file(): void
    {
        $path = base_path('docs/history/codebase-map.md');
        $before = is_file($path) ? file_get_contents($path) : null;

        $map = $this->map();

        expect($map)
            ->toContain('Peta Kodebase')
            ->toContain('Halaman modern (React)')
            ->toContain('Kelas bug (registry regresi)');

        expect(is_file($path) ? file_get_contents($path) : null)->toBe($before);
    }

    public function test_map_lists_every_registered_modern_page(): void
    {
        $map = $this->map();

        foreach (array_keys(config('modern-pages.pages', [])) as $routeName) {
            expect($map)->toContain($routeName);
        }
    }

    public function test_map_lists_all_registered_bug_classes(): void
    {
        $map = $this->map();

        foreach (glob(base_path('docs/regressions/BC-*.md')) ?: [] as $file) {
            expect($map)->toContain(basename($file));
        }
    }
}
