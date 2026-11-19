<?php

namespace Tests\Feature\Regression;

use Tests\TestCase;

/**
 * Regression: BC-11 — modal tertutup backdrop sehingga tidak bisa diklik/diedit.
 *
 * `.page-body` dan `.card` diberi `z-index: 1` agar dropdown dashboard tidak
 * tertutup kartu. Keduanya menjadi stacking context, sehingga `.modal`
 * (z-index 1055) yang berada di dalamnya ikut terkurung dan kalah oleh
 * `.modal-backdrop` (z-index 1050, anak langsung <body>). Semua klik jatuh
 * ke backdrop: modal terlihat, nilainya terbaca, tetapi read-only.
 *
 * Penjaga ini mengunci aturan override yang dilepas hanya saat modal terbuka
 * (Bootstrap menambah `modal-open` pada <body>), agar regresi ini tertangkap di CI.
 */
class BC11ModalStackingContextTest extends TestCase
{
    public function test_app_css_releases_stacking_context_while_modal_is_open(): void
    {
        $css = (string) file_get_contents(resource_path('css/app.css'));

        expect($css)->toContain('body.modal-open .page-body');
        expect($css)->toContain('body.modal-open .card');
        expect($css)->toContain('z-index: auto !important');
    }

    public function test_built_css_ships_the_override_rule(): void
    {
        $files = glob(public_path('build/assets/app-*.css'));
        $this->assertNotEmpty($files, 'Bundle CSS tidak ditemukan — jalankan npm run build.');

        $found = false;
        foreach ($files as $file) {
            $css = (string) file_get_contents($file);
            if (str_contains($css, 'body.modal-open .page-body')) {
                $found = true;
                break;
            }
        }

        expect($found)->toBeTrue('Aturan override modal-open tidak termuat di bundle CSS produksi.');
    }
}
