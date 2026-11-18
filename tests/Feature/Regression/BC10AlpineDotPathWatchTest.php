<?php

namespace Tests\Feature\Regression;

use Tests\TestCase;

/**
 * Regression: BC-10 — Alpine `$watch` dengan string dot-path.
 *
 * `moneyInputSingle` menyimpan model seperti "research_scheme_caps.12".
 * Memakai `$watch(`$wire.${name}`)` membuat Alpine meng-compile
 * `$wire.research_scheme_caps.12` → "SyntaxError: Unexpected number".
 * Satu error itu menghentikan antrean directive Alpine, sehingga seluruh isi
 * modal (input Rupiah lain, wire:model, wire:submit) tidak pernah terpasang:
 * modal Edit pagu tampil tetapi tidak bisa diedit/disimpan.
 *
 * Penjaga ini mengunci bentuk yang benar (getter + $wire.get) tanpa perlu
 * browser, sehingga regresi bisa tertangkap di CI.
 */
class BC10AlpineDotPathWatchTest extends TestCase
{
    public function test_money_input_single_uses_dot_path_aware_watch(): void
    {
        $js = (string) file_get_contents(resource_path('js/app.js'));

        expect($js)->toContain('this.$wire.get(wireModelName)');

        // Scope the assertion to the moneyInputSingle component: other Alpine
        // components (TomSelect) legitimately watch plain, non-dot field names.
        $start = strpos($js, 'Alpine.data("moneyInputSingle"');
        $this->assertNotFalse($start);

        $end = strpos($js, 'Alpine.data(', $start + 10);
        $block = substr($js, (int) $start, $end === false ? null : $end - $start);

        // Bentuk lama yang meledak: this.$watch(`$wire.${wireModelName}`, ...)
        expect($block)->not->toMatch('/this\.\$watch\(\s*`\$wire\.\$\{/');

        // Mengosongkan field tidak boleh memicu request server per ketukan.
        expect($block)->toContain('this.$wire.set(wireModelName, null, false);');
    }

    public function test_budget_cap_tab_still_renders_dot_path_money_models(): void
    {
        // Guard di atas hanya relevan selama view memang memakai model ber-titik.
        $view = (string) file_get_contents(
            resource_path('views/livewire/settings/tabs/budget-cap-manager.blade.php')
        );

        expect($view)->toContain("moneyInputSingle('research_scheme_caps.");
        expect($view)->toContain("moneyInputSingle('community_service_scheme_caps.");
        expect($view)->toContain('wire:submit="save"');
    }

    /**
     * Regression: BC-03 — UI ↔ API drift pada dashboard modern.
     *
     * Dashboard dulu memaksa `type=research` saat filter "Semua Tipe", sehingga
     * data pengabdian tidak pernah tampil. Penjaga ini memastikan filter
     * dikirim apa adanya dan tabel punya kolom nomor urut.
     */
    public function test_dashboard_requests_selected_type_and_numbers_rows(): void
    {
        $jsx = (string) file_get_contents(resource_path('js/modern/DashboardIndex.jsx'));

        expect($jsx)->not->toContain('params.set("type", "research")');
        expect($jsx)->toContain('type: typeFilter');
        expect($jsx)->toContain('row_number');
        expect($jsx)->toContain('per_page');
    }
}
