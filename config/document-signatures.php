<?php

$currentKid = env('DOCUMENT_SIGNATURE_KID', 'v1');
$secret = env('DOCUMENT_SIGNATURE_SECRET');

if (empty($secret)) {
    if (app()->environment('production')) {
        throw new RuntimeException(
            'DOCUMENT_SIGNATURE_SECRET is not configured. '
            .'Generate a key: php -r "echo bin2hex(random_bytes(32));"'
        );
    }
    logger()->warning('DOCUMENT_SIGNATURE_SECRET not set — using development fallback');
    $secret = 'dev-fallback-secret-not-for-production';
}

return [
    'current_kid' => $currentKid,
    'keys' => [
        $currentKid => $secret,
    ],
];
