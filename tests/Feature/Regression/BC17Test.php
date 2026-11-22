<?php

/**
 * Regression: BC-17 — Logo navbar: bagian putih logo tidak terlihat di tema modern.
 */
it('BC-17: logo navbar mempertahankan warna asli dengan backdrop abu-abu dan outline gelap kuat', function () {
    $css = (string) file_get_contents(resource_path('css/app.css'));

    expect($css)->toContain('body.theme-modern .navbar .navbar-brand img');
    expect($css)->toContain('background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%)');
    expect($css)->toContain('filter: drop-shadow(0 0 2px rgba(0, 0, 0, 0.75))');
    expect($css)->not->toContain('filter: brightness(0) invert(1)');
});
