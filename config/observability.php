<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Konfigurasi observability (W4-H2) — pencatatan error & peristiwa pengembangan.
 *
 * Semua nilai bisa dimatikan lewat env tanpa mengubah kode, supaya deploy
 * tetap aman bila tabel belum dimigrasikan atau DB sedang bermasalah.
 */
return [

    'error_signatures' => [
        /*
         * Bila false, ErrorSignatureRecorder langsung berhenti (tidak menyentuh DB).
         */
        'enabled' => (bool) env('ERROR_SIGNATURES_ENABLED', true),

        /*
         * Saat unit test, recorder nonaktif agar suite tidak menulis ke tabel
         * dan tidak memperlambat ribuan kasus. Nyalakan hanya untuk test khusus.
         */
        'allow_in_tests' => (bool) env('ERROR_SIGNATURES_ALLOW_IN_TESTS', false),

        /*
         * Kelas exception yang tidak perlu dicatat: error 4xx adalah perilaku
         * normal (validasi, otorisasi, 404), bukan bug aplikasi.
         */
        'ignore' => [
            HttpResponseException::class,
            ValidationException::class,
            AuthenticationException::class,
            AuthorizationException::class,
            ModelNotFoundException::class,
            HttpException::class,
            NotFoundHttpException::class,
            MethodNotAllowedHttpException::class,
            TokenMismatchException::class,
        ],
    ],

    'retention' => [
        'dev_events_days' => (int) env('DEV_EVENTS_RETENTION_DAYS', 365),
        'error_signatures_days' => (int) env('ERROR_SIGNATURES_RETENTION_DAYS', 180),
    ],

    /*
     * Pencatatan peristiwa pengembangan (commit/CI/deploy/bug). Dimatikan
     * lewat env bila tabel dev_events belum ada di lingkungan tersebut.
     */
    'dev_events_enabled' => (bool) env('DEV_EVENTS_ENABLED', true),

];
