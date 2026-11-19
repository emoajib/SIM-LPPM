<?php

namespace Tests\Feature\Regression;

use Tests\TestCase;

/**
 * Regression: BC-12 — navbar tema modern menutupi header modal.
 *
 * `body.theme-modern .navbar` dipaksa `z-index: 2147483647` agar dropdown
 * header tidak tertutup banner. Efek samping: navbar di atas `.modal` (1055)
 * dan `.modal-backdrop` (1050), sehingga judul + tombol close + field atas
 * modal tertutup navbar.
 *
 * Penjaga ini mengunci aturan override yang dilepas hanya saat modal terbuka
 * (`modal-open` pada <body>), agar regresi ini tertangkap di CI.
 */
class BC12ModalNavbarZIndexTest extends TestCase
{
    public function test_app_css_releases_navbar_stacking_context_while_modal_is_open(): void
    {
        $css = (string) file_get_contents(resource_path('css/app.css'));

        expect($css)->toContain('body.modal-open .navbar');
        expect($css)->toContain('body.theme-modern.modal-open .navbar');
        expect($css)->toContain('z-index: auto !important');
    }

    public function test_built_css_ships_the_override_rule(): void
    {
        $files = glob(public_path('build/assets/app-*.css'));
        $this->assertNotEmpty($files, 'Bundle CSS tidak ditemukan — jalankan npm run build.');

        $found = false;
        foreach ($files as $file) {
            $css = (string) file_get_contents($file);
            if (str_contains($css, 'body.modal-open .navbar')) {
                $found = true;
                break;
            }
        }

        expect($found)->toBeTrue('Aturan override navbar modal-open tidak termuat di bundle CSS produksi.');
    }

    public function test_budget_cap_modal_is_scrollable(): void
    {
        $blade = (string) file_get_contents(resource_path('views/livewire/settings/tabs/budget-cap-manager.blade.php'));

        expect($blade)->toContain('scrollable');
    }

    public function test_header_wrapper_has_stable_id_for_offset_measurement(): void
    {
        $blade = (string) file_get_contents(resource_path('views/components/layouts/header.blade.php'));

        expect($blade)->toContain('id="site-header"');
    }

    public function test_app_css_offsets_modal_below_header(): void
    {
        $css = (string) file_get_contents(resource_path('css/app.css'));

        expect($css)->toContain('top: var(--sllp-modal-top, 0px)');
        expect($css)->toContain('height: calc(100dvh - var(--sllp-modal-top, 0px))');
    }

    public function test_app_js_sets_modal_offset_variable(): void
    {
        $js = (string) file_get_contents(resource_path('js/app.js'));

        expect($js)->toContain('syncModalOffset');
        expect($js)->toContain('--sllp-modal-top');
        expect($js)->toContain('show.bs.modal');
    }
}
