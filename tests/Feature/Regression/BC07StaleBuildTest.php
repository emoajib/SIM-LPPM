<?php

namespace Tests\Feature\Regression;

use Tests\TestCase;

/**
 * Regression: BC-07 — stale build asset.
 *
 * Deploy tanpa `npm run build` membuat `public/build/manifest.json` menunjuk
 * file yang sudah tidak ada, sehingga halaman modern blank di produksi.
 * Penjaga ini memverifikasi manifest dan seluruh file yang dirujuknya ada.
 */
class BC07StaleBuildTest extends TestCase
{
    public function test_manifest_exists_and_references_existing_files(): void
    {
        $manifestPath = public_path('build/manifest.json');
        expect(is_file($manifestPath))->toBeTrue($this->manifestMissingMessage());

        $manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
        expect(is_array($manifest))->toBeTrue();

        $missing = [];

        foreach ($manifest as $entry) {
            if (! is_array($entry) || ! isset($entry['file'])) {
                continue;
            }

            $file = ltrim((string) $entry['file'], '/');
            if ($file === '' || ! is_file(public_path('build/'.$file))) {
                $missing[] = $file;
            }
        }

        expect($missing)->toBe([], 'Manifest menunjuk file yang hilang: '.implode(', ', $missing));
    }

    private function manifestMissingMessage(): string
    {
        return 'public/build/manifest.json tidak ada. Jalankan `npm run build` lalu commit public/build.';
    }
}
