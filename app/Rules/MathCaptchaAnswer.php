<?php

namespace App\Rules;

use App\Services\Security\MathCaptchaService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Translation\PotentiallyTranslatedString;

class MathCaptchaAnswer implements ValidationRule
{
    public function __construct(private readonly string $id) {}

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $request = request();
        $sessionId = $request->hasSession() ? $request->session()->getId() : '';
        $key = 'captcha-verify:'.hash('sha256', $sessionId.'|'.$request->ip());

        $max = max(1, (int) config('captcha.throttle.verify', 10));
        $decay = max(1, (int) config('captcha.throttle.verify_decay', 300));

        if (RateLimiter::tooManyAttempts($key, $max)) {
            $minutes = max(1, (int) ceil(RateLimiter::availableIn($key) / 60));
            $fail('Terlalu banyak percobaan verifikasi. Coba lagi dalam '.$minutes.' menit.');

            return;
        }

        RateLimiter::hit($key, $decay);

        $status = app(MathCaptchaService::class)->verify($this->id, $value, $sessionId);

        if ($status === MathCaptchaService::OK) {
            RateLimiter::clear($key);

            return;
        }

        $fail($this->message($status));
    }

    private function message(string $status): string
    {
        return match ($status) {
            MathCaptchaService::TOO_FAST => 'Verifikasi terlalu cepat. Silakan coba lagi.',
            MathCaptchaService::EXPIRED => 'Sesi verifikasi keamanan berakhir. Silakan coba lagi.',
            MathCaptchaService::INVALID,
            MathCaptchaService::TOO_MANY_ATTEMPTS => 'Jawaban keamanan salah.',
            default => 'Verifikasi keamanan gagal. Silakan coba lagi.',
        };
    }
}
