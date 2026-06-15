<?php

namespace App\Livewire\Auth;

use App\Models\Identity;
use App\Models\Setting;
use App\Models\User;
use App\Rules\Turnstile;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Features;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.auth')]
class Login extends Component
{
    #[Validate('required|string')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public int $n1 = 0;

    public int $n2 = 0;

    public string $math_answer = '';

    public string $username_honeypot = '';

    public bool $remember = false;

    public string $loginTitle = 'Login to your account';

    public string $captcha = '';

    public function mount(): void
    {
        $this->generateMathQuestion();
        $this->loginTitle = Cache::remember('settings.login_title', 3600, function () {
            return Setting::where('key', 'login_title')->value('value') ?? 'Login to your account';
        });
    }

    public function generateMathQuestion(): void
    {
        $this->n1 = rand(1, 9);
        $this->n2 = rand(1, 9);
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
                $rules['math_answer'] = [
                    'required',
                    function ($attribute, $value, $fail) {
                        if ((int) $value !== ($this->n1 + $this->n2)) {
                            $fail('Jawaban keamanan salah.');
                        }
                    },
                ];
            }
        }

        try {
            $this->validate($rules);
        } catch (ValidationException $e) {
            // Reset CAPTCHA and math answer on validation failure to allow retry
            $this->captcha = '';
            $this->math_answer = '';
            $this->generateMathQuestion();
            $this->dispatch('resetTurnstile');
            throw $e;
        }

        // Manual Honey Pot Check
        if (! empty($this->username_honeypot)) {
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

        $user = User::role($roleName)->first();

        if (! $user) {
            $this->addError('email', "No user found with role: $roleName");

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
