<?php

/**
 * Registry halaman tema modern (React).
 *
 * Setiap route yang merender root React (`#modern-root`) WAJIB terdaftar di sini,
 * dan setiap komponen React yang dipakai WAJIB terdaftar di
 * `resources/js/modern.jsx` (konstanta PAGES).
 *
 * Validasi: `php artisan modern:check` (dijalankan di CI).
 *
 * BC-01 — mencegah halaman blank karena key tidak cocok / komponen tidak terdaftar.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Route name => komponen React (key pada PAGES di resources/js/modern.jsx)
    |--------------------------------------------------------------------------
    */
    'pages' => [
        'research.proposal.index' => 'proposals-index',
        'research.proposal-revision.index' => 'research-revision-index',
        'research.final-report.index' => 'research-final-report-index',
        'research.daily-note.index' => 'research-daily-note-index',
        'community-service.proposal.index' => 'pengabdian-index',
        'community-service.proposal-revision.index' => 'pengabdian-revision-index',
        'community-service.final-report.index' => 'pengabdian-final-report-index',
        'community-service.daily-note.index' => 'pengabdian-daily-note-index',
        'research.verifikasi-luaran.page' => 'verifikasi-luaran-index',
        'community-service.verifikasi-luaran.page' => 'verifikasi-luaran-index',
        'research.daily-note.modern' => 'daily-note-index',
        'research.proposal.show.modern' => 'proposal-show-index',
        'community-service.daily-note.modern' => 'daily-note-index',
        'community-service.proposal.show.modern' => 'proposal-show-index',
        'settings.notification-config.page' => 'notification-config-index',
        'settings.profile.page' => 'profile-index',
        'settings.index.page' => 'settings-index',
        'settings.password.page' => 'password-index',
        'settings.feature-flags.page' => 'feature-flags-index',
        'settings.captcha.page' => 'captcha-index',
    ],

    /*
    |--------------------------------------------------------------------------
    | Route yang header-nya disembunyikan pada tema modern
    |--------------------------------------------------------------------------
    | Halaman ini punya judul sendiri di dalam komponen React.
    */
    'hide_header_routes' => [
        'research.proposal-revision.index',
        'research.final-report.index',
        'research.daily-note.index',
        'community-service.proposal-revision.index',
        'community-service.final-report.index',
        'community-service.daily-note.index',
    ],

    /*
    |--------------------------------------------------------------------------
    | Komponen yang boleh terdaftar di PAGES tanpa route (dipakai manual)
    |--------------------------------------------------------------------------
    */
    'allowed_unrouted_components' => [
        'dashboard-index',
    ],

];
