<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class MathCaptchaService
{
    public const OK = 'ok';

    public const INVALID = 'invalid';

    public const EXPIRED = 'expired';

    public const SESSION_MISMATCH = 'session_mismatch';

    public const TOO_MANY_ATTEMPTS = 'too_many_attempts';

    public const TOO_FAST = 'too_fast';

    /**
     * Create a new challenge and persist the answer server-side only.
     *
     * @return array{id: string, challenge: array<string, mixed>}
     */
    public function issue(string $sessionId): array
    {
        $a = random_int(2, 9);
        $b = random_int(2, 9);
        $op = ['+', '-', '×'][random_int(0, 2)];

        if ($op === '-' && $a < $b) {
            [$a, $b] = [$b, $a];
        }

        $answer = match ($op) {
            '+' => $a + $b,
            '×' => $a * $b,
            default => $a - $b,
        };

        $challenge = [
            'a' => $a,
            'b' => $b,
            'op' => $op,
            'answer' => $answer,
            'session' => $this->sessionHash($sessionId),
            'attempts' => 0,
            'issued_at' => microtime(true),
        ];

        $id = Str::random(32);
        Cache::put($this->key($id), $challenge, (int) config('captcha.ttl', 600));

        return ['id' => $id, 'challenge' => $challenge];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $id, string $sessionId): ?array
    {
        if (! preg_match('/^[A-Za-z0-9]{32}$/', $id)) {
            return null;
        }

        $challenge = Cache::get($this->key($id));

        if (! is_array($challenge) || ! isset($challenge['session'], $challenge['answer'])) {
            return null;
        }

        if (! hash_equals((string) $challenge['session'], $this->sessionHash($sessionId))) {
            return null;
        }

        return $challenge;
    }

    public function verify(string $id, mixed $answer, string $sessionId): string
    {
        $challenge = $this->find($id, $sessionId);

        if ($challenge === null) {
            return self::EXPIRED;
        }

        $normalized = is_scalar($answer) ? trim((string) $answer) : '';

        if ($normalized === '' || ! preg_match('/^-?\d{1,3}$/', $normalized)) {
            $this->recordFailure($id, $challenge);

            return self::INVALID;
        }

        if ((int) $normalized !== (int) $challenge['answer']) {
            $this->recordFailure($id, $challenge);

            return self::INVALID;
        }

        $elapsed = microtime(true) - (float) ($challenge['issued_at'] ?? 0);

        Cache::forget($this->key($id));

        if ($elapsed < (float) config('captcha.min_solve_seconds', 1.2)) {
            return self::TOO_FAST;
        }

        return self::OK;
    }

    public function forget(string $id): void
    {
        Cache::forget($this->key($id));
    }

    /**
     * @param  array<string, mixed>  $challenge
     * @return array{body: string, mime: string}
     */
    public function render(array $challenge): array
    {
        if ($this->canvasSupported()) {
            try {
                return ['body' => $this->renderPng($challenge), 'mime' => 'image/png'];
            } catch (Throwable $e) {
                Log::warning('CAPTCHA PNG rendering failed, falling back to SVG', [
                    'exception' => class_basename($e),
                ]);
            }
        }

        return ['body' => $this->renderSvg($challenge), 'mime' => 'image/svg+xml'];
    }

    public function canvasSupported(): bool
    {
        return extension_loaded('gd')
            && function_exists('imagettftext')
            && is_file((string) config('captcha.font_path'));
    }

    /**
     * @param  array<string, mixed>  $challenge
     */
    public function renderPng(array $challenge): string
    {
        $width = (int) config('captcha.width', 400);
        $height = (int) config('captcha.height', 140);
        $font = (string) config('captcha.font_path');

        $image = imagecreatetruecolor($width, $height);

        if ($image === false) {
            throw new RuntimeException('Unable to create CAPTCHA canvas.');
        }

        imagealphablending($image, true);
        imagesavealpha($image, true);

        $background = imagecolorallocate(
            $image,
            random_int(246, 252),
            random_int(246, 252),
            random_int(246, 252)
        );

        imagefilledrectangle($image, 0, 0, $width, $height, $background);

        $this->addSpeckles($image, $width, $height);
        $this->addNoiseLines($image, $width, $height);
        $this->drawExpression($image, $challenge, $font, $width, $height);
        $this->addNoiseLines($image, $width, $height);

        $distorted = $this->applyWave($image, $width, $height, $background);

        if ($distorted !== $image) {
            imagedestroy($image);
        }

        $image = $distorted;

        ob_start();
        imagepng($image, null, 6);
        $body = (string) ob_get_clean();

        imagedestroy($image);

        if ($body === '') {
            throw new RuntimeException('CAPTCHA image could not be encoded.');
        }

        return $body;
    }

    /**
     * @param  array<string, mixed>  $challenge
     */
    public function renderSvg(array $challenge): string
    {
        $glyphs = $this->svgGlyphs();
        $chars = preg_split('//u', $this->expression($challenge), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $parts = ['<rect width="400" height="140" fill="#f8f9fb"/>'];

        for ($i = 0; $i < 60; $i++) {
            $parts[] = sprintf(
                '<circle cx="%d" cy="%d" r="%d" fill="#d7dbe0" opacity="0.55"/>',
                random_int(0, 400),
                random_int(0, 140),
                random_int(1, 2)
            );
        }

        for ($i = 0; $i < 6; $i++) {
            $parts[] = sprintf(
                '<polyline points="%d,%d %d,%d %d,%d" fill="none" stroke="#7b8794" stroke-width="1" stroke-opacity="0.28"/>',
                random_int(0, 400),
                random_int(0, 140),
                random_int(0, 400),
                random_int(0, 140),
                random_int(0, 400),
                random_int(0, 140)
            );
        }

        $x = 22.0;

        foreach ($chars as $char) {
            if ($char === ' ') {
                $x += 14;

                continue;
            }

            $segments = $glyphs[$char] ?? null;

            if ($segments === null) {
                continue;
            }

            $angle = random_int(-12, 12);
            $scale = random_int(88, 108) / 100;
            $y = random_int(4, 12);

            foreach ($segments as $segment) {
                $points = [];

                foreach ($segment as $point) {
                    $points[] = round($point[0], 1).','.round($point[1], 1);
                }

                $parts[] = sprintf(
                    '<polyline points="%s" fill="none" stroke="#3f4752" stroke-width="7" stroke-linecap="round" stroke-linejoin="round" transform="translate(%.1f %.1f) rotate(%d) scale(%.2f)"/>',
                    implode(' ', $points),
                    $x,
                    $y,
                    $angle,
                    $scale
                );
            }

            $x += 64;
        }

        for ($i = 0; $i < 4; $i++) {
            $parts[] = sprintf(
                '<polyline points="%d,%d %d,%d %d,%d" fill="none" stroke="#7b8794" stroke-width="1" stroke-opacity="0.3"/>',
                random_int(0, 400),
                random_int(0, 140),
                random_int(0, 400),
                random_int(0, 140),
                random_int(0, 400),
                random_int(0, 140)
            );
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="140" viewBox="0 0 400 140" role="img" aria-label="Soal verifikasi keamanan">'
            .implode('', $parts)
            .'</svg>';
    }

    /**
     * @param  resource|\GdImage  $img
     * @param  array<string, mixed>  $challenge
     */
    private function drawExpression($img, array $challenge, string $font, int $width, int $height): void
    {
        $chars = preg_split('//u', $this->expression($challenge), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $sizes = [];

        foreach ($chars as $char) {
            $box = imagettfbbox(32, 0, $font, $char);
            $sizes[] = $box === false ? 18 : max(12, (int) abs($box[2] - $box[0]));
        }

        $spacing = random_int(4, 9);
        $total = array_sum($sizes) + $spacing * max(0, count($chars) - 1);
        $x = (int) max(12, ($width - $total) / 2);
        $baseline = (int) ($height / 2 + 16);

        foreach ($chars as $index => $char) {
            if ($char === ' ') {
                $x += $sizes[$index] + $spacing;

                continue;
            }

            $size = random_int(28, 34);
            $angle = random_int(-13, 13);
            $y = $baseline + random_int(-7, 7);
            $color = imagecolorallocatealpha(
                $img,
                random_int(45, 85),
                random_int(45, 85),
                random_int(45, 85),
                random_int(10, 28)
            );

            imagettftext($img, $size, $angle, $x, $y, $color, $font, $char);

            $x += (int) ($sizes[$index] * random_int(90, 115) / 100) + $spacing;
        }
    }

    /**
     * @param  resource|\GdImage  $img
     */
    private function addSpeckles($img, int $width, int $height): void
    {
        $count = random_int(120, 200);

        for ($i = 0; $i < $count; $i++) {
            $tone = random_int(200, 235);
            $color = imagecolorallocatealpha($img, $tone, $tone, $tone, random_int(70, 110));

            imagefilledellipse(
                $img,
                random_int(0, $width),
                random_int(0, $height),
                random_int(1, 2),
                random_int(1, 2),
                $color
            );
        }
    }

    /**
     * @param  resource|\GdImage  $img
     */
    private function addNoiseLines($img, int $width, int $height): void
    {
        $range = (array) config('captcha.noise_lines', [6, 10]);
        $min = (int) ($range[0] ?? 6);
        $max = (int) ($range[1] ?? 10);
        $count = random_int(min($min, $max), max($min, $max));

        imagesetthickness($img, 1);

        for ($i = 0; $i < $count; $i++) {
            $color = imagecolorallocatealpha(
                $img,
                random_int(40, 130),
                random_int(40, 130),
                random_int(40, 130),
                random_int(55, 85)
            );

            $midX = random_int(0, $width);
            $midY = random_int(0, $height);

            imageline($img, random_int(0, $width), random_int(0, $height), $midX, $midY, $color);
            imageline($img, $midX, $midY, random_int(0, $width), random_int(0, $height), $color);
        }

        for ($i = 0; $i < 2; $i++) {
            $color = imagecolorallocatealpha(
                $img,
                random_int(60, 140),
                random_int(60, 140),
                random_int(60, 140),
                random_int(70, 100)
            );

            imagearc(
                $img,
                random_int(0, $width),
                random_int(0, $height),
                random_int(120, 320),
                random_int(40, 120),
                random_int(0, 360),
                random_int(0, 360),
                $color
            );
        }
    }

    /**
     * @param  resource|\GdImage  $img
     * @return resource|\GdImage
     */
    private function applyWave($img, int $width, int $height, int $background)
    {
        $distorted = imagecreatetruecolor($width, $height);

        if ($distorted === false) {
            return $img;
        }

        imagealphablending($distorted, true);
        imagefilledrectangle($distorted, 0, 0, $width, $height, $background);

        $amplitude = random_int(2, 4);
        $period = random_int(14, 22) / 2;

        for ($y = 0; $y < $height; $y++) {
            $shift = (int) round(sin($y / $period) * $amplitude);

            if ($shift === 0) {
                imagecopy($distorted, $img, 0, $y, 0, $y, $width, 1);
            } elseif ($shift > 0) {
                imagecopy($distorted, $img, $shift, $y, 0, $y, $width - $shift, 1);
            } else {
                imagecopy($distorted, $img, 0, $y, -$shift, $y, $width + $shift, 1);
            }
        }

        return $distorted;
    }

    /**
     * @return array<string, array<int, array<int, array{0: float, 1: float}>>>
     */
    private function svgGlyphs(): array
    {
        $path = (string) config('captcha.glyphs_path');

        if (! is_file($path)) {
            return [];
        }

        $glyphs = require $path;

        return is_array($glyphs) ? $glyphs : [];
    }

    /**
     * @param  array<string, mixed>  $challenge
     */
    private function expression(array $challenge): string
    {
        return $challenge['a'].' '.$challenge['op'].' '.$challenge['b'].' =';
    }

    private function key(string $id): string
    {
        return 'captcha:math:'.$id;
    }

    private function sessionHash(string $sessionId): string
    {
        return hash('sha256', config('app.key').'|'.$sessionId);
    }

    /**
     * @param  array<string, mixed>  $challenge
     */
    private function recordFailure(string $id, array $challenge): void
    {
        $attempts = (int) ($challenge['attempts'] ?? 0) + 1;

        if ($attempts >= (int) config('captcha.max_attempts', 3)) {
            Cache::forget($this->key($id));

            return;
        }

        $challenge['attempts'] = $attempts;

        $elapsed = max(0.0, microtime(true) - (float) ($challenge['issued_at'] ?? microtime(true)));
        $remaining = max(1, (int) config('captcha.ttl', 600) - (int) $elapsed);

        Cache::put($this->key($id), $challenge, $remaining);
    }
}
