<?php

/**
 * Regression: BC-17 — Logo navbar tidak terlihat / warna putih logo hilang di tema modern.
 */
it('BC-17: logo navbar mempertahankan warna asli dengan outline gelap agar bagian putih terlihat', function () {
    $css = (string) file_get_contents(resource_path('css/app.css'));

    expect($css)->toContain('body.theme-modern .navbar .navbar-brand img');
    expect($css)->toContain('background-color: #ffffff');
    expect($css)->toContain('filter: drop-shadow(0 0 1px rgba(0, 0, 0, 0.6))');
    expect($css)->not->toContain('filter: brightness(0) invert(1)');
});
