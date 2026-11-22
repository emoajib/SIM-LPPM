<?php

/**
 * Regression: BC-16 — Logo navbar tidak kontras dengan background gradasi biru gelap tema modern.
 *
 * Penyebab: logo.png (gelap) ditampilkan di navbar gradasi biru tanpa backdrop,
 * sehingga menyatu dengan background dan tidak jelas.
 *
 * Penjaga ini mengunci keberadaan backdrop putih pada img logo di scope
 * body.theme-modern tanpa perlu browser.
 */
it('BC-16: logo navbar memiliki backdrop di tema modern', function () {
    $css = (string) file_get_contents(resource_path('css/app.css'));

    expect($css)->toContain('body.theme-modern .navbar .navbar-brand img');
    expect($css)->toContain('background:');
    expect($css)->toContain('border-radius: 12px');
});
