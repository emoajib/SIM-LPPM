<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

/**
 * Validasi registry halaman modern (config/modern-pages.php) terhadap:
 * 1. route yang benar-benar terdaftar di aplikasi, dan
 * 2. komponen React yang benar-benar terdaftar di resources/js/modern.jsx (PAGES).
 *
 * BC-01 — mencegah halaman blank / dead-end karena key tidak cocok.
 */
class CheckModernPages extends Command
{
    protected $signature = 'modern:check';

    protected $description = 'Validasi registry halaman modern (route <-> komponen React)';

    public function handle(): int
    {
        $registry = config('modern-pages', []);
        $pages = $registry['pages'] ?? [];
        $hideHeader = $registry['hide_header_routes'] ?? [];
        $allowedUnrouted = $registry['allowed_unrouted_components'] ?? [];

        $errors = [];
        $warnings = [];

        $components = $this->registeredComponents();
        if ($components === []) {
            $this->error('Tidak bisa membaca PAGES dari resources/js/modern.jsx.');

            return self::FAILURE;
        }

        // 1. Route di registry harus benar-benar ada.
        foreach (array_keys($pages) as $routeName) {
            if (! Route::has($routeName)) {
                $errors[] = "Route '{$routeName}' ada di registry tapi tidak terdaftar di routes/.";
            }
        }

        // 2. Komponen di registry harus terdaftar di PAGES.
        foreach ($pages as $routeName => $component) {
            if (! in_array($component, $components, true)) {
                $errors[] = "Route '{$routeName}' memakai komponen '{$component}' yang tidak ada di PAGES (resources/js/modern.jsx).";
            }
        }

        // 3. hide_header_routes harus subset dari pages.
        foreach ($hideHeader as $routeName) {
            if (! array_key_exists($routeName, $pages)) {
                $errors[] = "hide_header_routes berisi '{$routeName}' yang tidak ada di pages.";
            }
        }

        // 4. Setiap route *.modern wajib terdaftar (kalau tidak, halaman modern tidak akan render).
        foreach (Route::getRoutes()->getRoutes() as $route) {
            $name = $route->getName();
            if ($name === null || ! str_ends_with($name, '.modern')) {
                continue;
            }
            if (! array_key_exists($name, $pages)) {
                $errors[] = "Route modern '{$name}' belum terdaftar di config/modern-pages.php.";
            }
        }

        // 5. Komponen terdaftar tapi tidak dipakai route mana pun (indikatif dead code).
        $usedComponents = array_values(array_unique($pages));
        foreach ($components as $component) {
            if (in_array($component, $usedComponents, true)) {
                continue;
            }
            if (in_array($component, $allowedUnrouted, true)) {
                continue;
            }
            $warnings[] = "Komponen '{$component}' terdaftar di PAGES tapi tidak dipakai route mana pun (dead code?).";
        }

        $this->info('=== Modern Pages Check ===');
        $this->line('Registry: '.count($pages).' route | Komponen PAGES: '.count($components));

        foreach ($warnings as $warning) {
            $this->warn('PERINGATAN: '.$warning);
        }

        if ($errors !== []) {
            foreach ($errors as $error) {
                $this->error('GAGAL: '.$error);
            }

            return self::FAILURE;
        }

        $this->info('Registry halaman modern konsisten: OK');

        return self::SUCCESS;
    }

    /**
     * Baca key PAGES dari resources/js/modern.jsx.
     *
     * @return array<int, string>
     */
    private function registeredComponents(): array
    {
        $path = resource_path('js/modern.jsx');
        if (! is_file($path)) {
            return [];
        }

        $source = (string) file_get_contents($path);
        if (! preg_match('/const PAGES = \{(.*?)\n\};/s', $source, $matches)) {
            return [];
        }

        preg_match_all('/"([^"]+)"\s*:/', $matches[1], $keys);

        return $keys[1] ?? [];
    }
}
