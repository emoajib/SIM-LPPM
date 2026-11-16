<?php

/**
 * Regression: BC-01 — breadcrumb modern tidak boleh memuat teks liar.
 *
 * Artefak "ngapa" pernah lolos karena partial dirender di semua halaman
 * detail saat tema modern. Test ini menjaga struktur dan isi teks.
 */
it('renders modern breadcrumb without stray text', function () {
    $this->withSession(['sim_lppm_app_theme' => 'modern']);

    $html = view('components.partials.breadcrumb', [
        'items' => [
            ['label' => 'Dashboard', 'href' => route('dashboard')],
            ['label' => 'Penelitian', 'href' => route('research.proposal.index')],
            ['label' => 'Detail Proposal'],
        ],
    ])->render();

    expect($html)
        ->toContain('Dashboard')
        ->toContain('Penelitian')
        ->toContain('Detail Proposal')
        ->not->toContain('ngapa');

    $this->assertSame(3, substr_count($html, '<li'));
    $this->assertSame(2, substr_count($html, '<span class="mx-1">/</span>'));
});

it('does not render the modern breadcrumb in classic theme', function () {
    $this->withSession(['sim_lppm_app_theme' => 'classic']);

    $html = view('components.partials.breadcrumb', [
        'items' => [
            ['label' => 'Dashboard', 'href' => route('dashboard')],
            ['label' => 'Detail Proposal'],
        ],
    ])->render();

    expect($html)->toBe('');
});
