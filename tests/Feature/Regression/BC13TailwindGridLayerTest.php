<?php

namespace Tests\Feature\Regression;

use Tests\TestCase;

/**
 * Regression: BC-13 — utility Tailwind (grid-cols-*) kalah dari `.grid` Tabler unlayered.
 *
 * Tabler mendefinisikan `.grid` (12 kolom, UNLAYERED). Tailwind v4 meletakkan
 * utility di `@layer utilities`; unlayered selalu menang, sehingga `grid-cols-*`
 * diabaikan dan `col-span-*` memecah konten jadi 12 kolom sempit → form gepeng
 * & overflow horizontal (profil, notifikasi, captcha, password).
 *
 * Penjaga ini mengunci netralisasi `#modern-root .grid` + mirror utility grid
 * yang dipakai halaman modern, serta memastikan setiap kelas `grid-cols-*`
 * yang dipakai di JSX punya mirror di modern.css.
 */
class BC13TailwindGridLayerTest extends TestCase
{
    private function modernCss(): string
    {
        return (string) file_get_contents(resource_path('css/modern.css'));
    }

    public function test_modern_css_neutralises_tabler_grid_inside_modern_root(): void
    {
        $css = $this->modernCss();
        expect($css)->toContain('#modern-root .grid');
        expect($css)->toContain('grid-template-columns: none');
        expect($css)->toContain('grid-template-rows: none');
    }

    public function test_modern_css_mirrors_grid_utilities_used_by_modern_pages(): void
    {
        $css = $this->modernCss();

        $files = glob(resource_path('js/modern/*.jsx'));
        $componentFiles = glob(resource_path('js/modern/components/*.jsx'));
        $source = '';
        foreach (array_merge($files, $componentFiles) as $f) {
            $source .= (string) file_get_contents($f)."\n";
        }

        preg_match_all('/(?:(sm|md|lg|xl|2xl):)?grid-cols-(\d+)/', $source, $m, PREG_SET_ORDER);
        $found = [];
        foreach ($m as $match) {
            $bp = $match[1] !== '' ? $match[1].':' : '';
            $found[] = $bp.'grid-cols-'.$match[2];
        }
        $found = array_values(array_unique($found));

        foreach ($found as $cls) {
            $selector = '#modern-root .'.str_replace(':', '\\:', $cls);
            expect(str_contains($css, $selector))->toBeTrue("Mirror hilang untuk kelas {$cls} di modern.css");
        }
    }

    public function test_profile_and_notification_grids_are_two_columns_where_col_span_two_is_used(): void
    {
        $profile = (string) file_get_contents(resource_path('js/modern/ProfileIndex.jsx'));
        $notification = (string) file_get_contents(resource_path('js/modern/NotificationConfigIndex.jsx'));

        // Jika ada sm:col-span-2, grid-nya harus sm:grid-cols-2 (bukan grid-cols-1 saja).
        expect($profile)->not->toContain('grid grid-cols-1 gap-4');
        expect($profile)->toContain('grid grid-cols-1 sm:grid-cols-2 gap-4');
        expect($notification)->not->toContain('grid grid-cols-1 gap-4 mb-6');
        expect($notification)->toContain('grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6');
    }

    public function test_header_menu_wraps_instead_of_pushing_page_width(): void
    {
        $css = (string) file_get_contents(resource_path('css/app.css'));

        expect(str_contains($css, '#site-header .navbar-collapse .navbar-nav'))->toBeTrue('Selector menu header hilang dari app.css');
        expect(str_contains($css, 'flex-wrap: wrap'))->toBeTrue('Menu header wajib flex-wrap: wrap agar halaman tidak overflow horizontal.');
    }

    public function test_template_settings_media_rows_wrap_instead_of_overflowing(): void
    {
        $blade = (string) file_get_contents(resource_path('views/livewire/settings/proposal-template.blade.php'));

        // Baris berkas + tombol "Unduh" wajib bisa membungkus, kalau tidak tombol
        // meluber keluar kartu dan mendorong lebar dokumen di <=1024px.
        preg_match_all('/p-2 bg-light rounded d-flex ([^"]+)"/', $blade, $matches);
        expect($matches[1])->not->toBeEmpty('Baris media template tidak ditemukan di blade.');

        foreach ($matches[1] as $classes) {
            expect(str_contains($classes, 'flex-wrap'))
                ->toBeTrue('Baris media template wajib flex-wrap agar tidak overflow horizontal: '.$classes);
        }

        // Nama berkas panjang harus pecah, bukan melebarkan halaman.
        expect($blade)->toContain('text-break');
        expect(substr_count($blade, 'style="min-width:0"'))->toBeGreaterThan(0);
    }

    public function test_built_modern_css_ships_the_neutraliser_rule(): void
    {
        $files = glob(public_path('build/assets/modern-*.css'));
        $this->assertNotEmpty($files, 'Bundle CSS modern tidak ditemukan — jalankan npm run build.');

        $found = false;
        foreach ($files as $file) {
            $css = (string) file_get_contents($file);
            if (str_contains($css, '#modern-root .grid') && (str_contains($css, 'grid-template-columns: none') || str_contains($css, 'grid-template-columns:none'))) {
                $found = true;
                break;
            }
        }
        expect($found)->toBeTrue('Aturan netralisasi #modern-root .grid tidak termuat di bundle CSS produksi.');
    }
}
