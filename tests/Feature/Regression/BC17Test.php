<?php

/**
 * Regression: BC-17 — Logo navbar tidak siluet dan terlalu kecil di tema modern.
 */
it('BC-17: logo navbar dirender sebagai siluet putih dengan backdrop gradasi', function () {
    $css = (string) file_get_contents(resource_path('css/app.css'));

    expect($css)->toContain('body.theme-modern .navbar .navbar-brand img');
    expect($css)->toContain('filter: brightness(0) invert(1)');
    expect($css)->toContain('backdrop-filter: blur(6px)');
    expect($css)->toContain('linear-gradient(135deg, rgba(255, 255, 255, 0.22) 0%, rgba(255, 255, 255, 0.06) 100%)');
});
