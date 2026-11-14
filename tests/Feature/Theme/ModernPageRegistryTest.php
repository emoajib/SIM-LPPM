<?php

use Illuminate\Support\Facades\Route;

/**
 * Regression: BC-01 — registry halaman modern harus konsisten.
 *
 * Kalau key komponen di config/modern-pages.php tidak ada di PAGES
 * (resources/js/modern.jsx), halaman modern akan blank tanpa error server.
 */
it('maps every registered modern route to an existing route', function () {
    $pages = config('modern-pages.pages', []);

    expect($pages)->not->toBeEmpty();

    foreach (array_keys($pages) as $routeName) {
        expect(route_exists($routeName))->toBeTrue("Route '{$routeName}' tidak terdaftar di routes/.");
    }
});

it('maps every registered modern route to a component present in PAGES', function () {
    $source = file_get_contents(resource_path('js/modern.jsx'));

    preg_match('/const PAGES = \{(.*?)\n\};/s', $source, $matches);
    preg_match_all('/"([^"]+)"\s*:/', $matches[1] ?? '', $keys);
    $components = $keys[1] ?? [];

    expect($components)->not->toBeEmpty();

    foreach (config('modern-pages.pages', []) as $routeName => $component) {
        expect(in_array($component, $components, true))
            ->toBeTrue("Komponen '{$component}' (route {$routeName}) tidak ada di PAGES.");
    }
});

it('keeps hide_header_routes as a subset of pages', function () {
    $pages = config('modern-pages.pages', []);

    foreach (config('modern-pages.hide_header_routes', []) as $routeName) {
        expect(array_key_exists($routeName, $pages))->toBeTrue("'{$routeName}' ada di hide_header_routes tapi tidak di pages.");
    }
});

/**
 * Cek route tanpa memicu error bila route belum di-boot.
 */
function route_exists(string $name): bool
{
    return Route::has($name);
}
