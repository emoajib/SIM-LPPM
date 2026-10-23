<?php

namespace App\Livewire\Auth;

use App\Models\Identity;
use App\Models\Setting;
use App\Models\User;
use App\Rules\MathCaptchaAnswer;
use App\Rules\Turnstile;
use App\Services\Security\CaptchaAudioService;
use App\Services\Security\MathCaptchaService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Features;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Spatie\Permission\Exceptions\RoleDoesNotExist;

#[Layout('components.layouts.auth')]
class Login extends Component
{
    #[Validate('required|string')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    #[Locked]
    public string $captchaId = '';

    public int $captchaVersion = 0;

    public bool $audioEnabled = false;

    public string $math_answer = '';

    public string $username_honeypot = '';

    public bool $remember = false;

    public string $loginTitle = 'Login to your account';

    public string $captcha = '';

    public function mount(): void
    {
        $this->audioEnabled = ! config('turnstile.site_key')
            && app(CaptchaAudioService::class)->available();

        if (! config('turnstile.site_key')) {
            $this->captchaId = app(MathCaptchaService::class)->issue(session()->getId())['id'];
        }

        $this->loginTitle = Cache::remember('settings.login_title', 3600, function () {
            return Setting::where('key', 'login_title')->value('value') ?? 'Login to your account';
        });
    }

    public function refreshCaptcha(): void
    {
        $service = app(MathCaptchaService::class);

        if ($this->captchaId !== '') {
            $service->forget($this->captchaId);
        }

        $this->resetValidation('math_answer');
        $this->math_answer = '';
        $this->captcha = '';
        $this->captchaVersion++;

        if (! config('turnstile.site_key')) {
            $this->captchaId = $service->issue(session()->getId())['id'];
        }

        $this->dispatch('resetTurnstile');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $rules = [
            'email' => 'required|string',
            'password' => 'required|string',
        ];

        if (! app()->environment('testing')) {
            if (config('turnstile.site_key')) {
                $rules['captcha'] = ['required', new Turnstile];
            } else {
                $rules['math_answer'] = ['required', new MathCaptchaAnswer($this->captchaId)];
            }
        }

        try {
            $this->validate($rules);
        } catch (ValidationException $e) {
            // Rotate the challenge so the failed expression cannot be retried.
            $this->refreshCaptcha();
            throw $e;
        }

        // Manual Honey Pot Check
        if (! empty($this->username_honeypot)) {
            $this->refreshCaptcha();

            throw ValidationException::withMessages([
                'general' => 'Terjadi kesalahan validasi.',
            ]);
        }

        $this->ensureIsNotRateLimited();

        // Try authentication by email first
        $credentials = ['email' => $this->email, 'password' => $this->password];

        // If input doesn't look like email, try to find user by identity_id
        if (! filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $identity = Identity::where('identity_id', $this->email)->first();
            if ($identity && $identity->user) {
                $credentials = ['email' => $identity->user->email, 'password' => $this->password];
            }
        }

        if (! Auth::attempt($credentials, $this->remember)) {
            $this->refreshCaptcha();
            RateLimiter::hit($this->throttleKey());
            $attemptsLeft = max(0, 5 - RateLimiter::attempts($this->throttleKey()));
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
                'general' => __('auth.failed').' Sisa percobaan: '.$attemptsLeft.'.',
            ]);
        }

        $user = Auth::user();

        if (Features::canManageTwoFactorAuthentication() && $user->hasEnabledTwoFactorAuthentication()) {
            Session::put([
                'login.id' => $user->getKey(),
                'login.remember' => $this->remember,
            ]);

            $this->redirect(route('two-factor.login'), navigate: true);

            return;
        }

        RateLimiter::clear($this->throttleKey());
        Session::regenerate();

        $firstRole = $user->getRoleNames()->first();
        if ($firstRole) {
            session(['active_role' => $firstRole]);
        }

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }

    public function devLogin(string $roleName): void
    {
        if (! app()->environment('local')) {
            return;
        }

        try {
            $user = User::role($roleName)->first();
        } catch (RoleDoesNotExist) {
            $this->addError('email', "Role '$roleName' tidak ditemukan di database. Jalankan `php artisan db:seed --class=RoleSeeder`.");

            return;
        }

        if (! $user) {
            $this->addError('email', "Tidak ada user dengan role: $roleName");

            return;
        }

        Auth::login($user, true);
        Session::regenerate();
        session(['active_role' => $roleName]);

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: false);
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        $this->refreshCaptcha();

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
            'general' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        // Use the actual email used for authentication
        $email = $this->email;
        if (! filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $identity = Identity::where('identity_id', $this->email)->first();
            if ($identity && $identity->user) {
                $email = $identity->user->email;
            }
        }

        return Str::transliterate(Str::lower($email).'|'.request()->ip());
    }
}
