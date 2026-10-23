<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Math CAPTCHA (fallback when Turnstile is not configured)
    |--------------------------------------------------------------------------
    |
    | The challenge is rendered as an image so the arithmetic expression is
    | never exposed as text to the client. Answers live only in the server
    | cache and are single-use.
    |
    */

    'ttl' => (int) env('CAPTCHA_TTL', 600),

    'max_attempts' => (int) env('CAPTCHA_MAX_ATTEMPTS', 3),

    'min_solve_seconds' => (float) env('CAPTCHA_MIN_SOLVE_SECONDS', 1.2),

    'width' => 400,

    'height' => 140,

    'display_width' => 200,

    'noise_lines' => [6, 10],

    'font_path' => resource_path('fonts/DejaVuSans.ttf'),

    'glyphs_path' => resource_path('captcha/glyphs.php'),

    'audio_path' => resource_path('audio/captcha'),

    'audio_sample_rate' => 22050,

    /*
    |--------------------------------------------------------------------------
    | Audio voice packs
    |--------------------------------------------------------------------------
    |
    | Each pack contains the same digit and operator recordings spoken by a
    | different voice. Admins choose the active pack from Settings. Packs are
    | pre-generated so production does not depend on a TTS engine.
    |
    */

    'audio_default_voice' => 'damayanti',

    'audio_voices' => [
        'damayanti' => [
            'label' => 'Damayanti (perempuan, Indonesia)',
            'locale' => 'id_ID',
        ],
        'daniel' => [
            'label' => 'Daniel (laki-laki, Inggris)',
            'locale' => 'en_GB',
        ],
        'samantha' => [
            'label' => 'Samantha (perempuan, Inggris)',
            'locale' => 'en_US',
        ],
    ],

    'throttle' => [
        // Generous per-IP limit: campuses commonly put hundreds of users
        // behind a single NAT address.
        'image' => (int) env('CAPTCHA_IMAGE_THROTTLE', 120),
        'audio' => (int) env('CAPTCHA_AUDIO_THROTTLE', 5),
        // Verification is limited per session + IP to stop captcha oracles.
        'verify' => (int) env('CAPTCHA_VERIFY_THROTTLE', 10),
        'verify_decay' => (int) env('CAPTCHA_VERIFY_DECAY', 300),
    ],
];
