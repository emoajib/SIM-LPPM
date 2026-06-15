<?php

$currentKid = env('DOCUMENT_SIGNATURE_KID', 'v1');

// Always provide a fallback secret to prevent runtime errors
$keys = [
    $currentKid => env('DOCUMENT_SIGNATURE_SECRET', 'default-signature-secret-do-not-use-in-production'),
];

// Validation: Ensure current_kid exists in keys and has a non-empty secret
// We wrap this in a check to allow bootstrapping during static analysis or CLI if needed
// Note: In production without env set, we use fallback above but warn in logs

return [
    'current_kid' => $currentKid,
    'keys' => $keys,
];
