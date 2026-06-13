<?php

namespace App\Providers;

use App\Listeners\UserActivityListener;
use App\Models\Letter;
use App\Models\Proposal;
use App\Models\ProposalStatusLog;
use App\Observers\ProposalObserver;
use App\Observers\ProposalStatusLogObserver;
use App\Policies\LetterPolicy;
use App\Policies\MediaPolicy;
use App\Policies\ProposalPolicy;
use App\View\Composers\MenuComposer;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // CRITICAL: Set file-based drivers BEFORE any service tries to use database
        // This must happen in register() before boot() is called
        if (! $this->isInstalled()) {
            config([
                'cache.default' => 'file',
                'session.driver' => 'file',
                'queue.default' => 'sync',
                'telescope.enabled' => false,
                'debugbar.enabled' => false,
            ]);

            return;
        }

        // Only register Telescope when app is installed
        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS if running behind an HTTPS proxy (Cloudflare)
        $host = request()->getHost();
        if (request()->header('X-Forwarded-Proto') === 'https' || str_contains(config('app.url'), 'https://')) {
            if ($host && ! in_array($host, ['localhost', '127.0.0.1', '::1'])) {
                URL::forceRootUrl(config('app.url'));
                URL::forceScheme('https');

                // Force secure cookies in production/HTTPS
                config(['session.secure' => true]);
            }
        }

        // Only run observers when installed
        if ($this->isInstalled()) {
            View::composer('components.layouts.header', MenuComposer::class);
            Proposal::observe(ProposalObserver::class);
            ProposalStatusLog::observe(ProposalStatusLogObserver::class);
            Event::subscribe(UserActivityListener::class);

            // Register Policies
            Gate::policy(Proposal::class, ProposalPolicy::class);
            Gate::policy(Media::class, MediaPolicy::class);
            Gate::policy(Letter::class, LetterPolicy::class);

            // Global Password Policy
            Password::defaults(function () {
                $rule = Password::min(12)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols();

                return $this->app->isProduction()
                    ? $rule->uncompromised()
                    : $rule;
            });

            // Spatie: Implicitly grant "superadmin" role all permissions
            // This works in the app by using gate-related functions like auth()->user->can() and @can()
            Gate::before(function ($user, $ability) {
                return $user->hasRole('superadmin') ? true : null;
            });
        }
    }

    private function isInstalled(): bool
    {
        // Only check lock file - this is the definitive marker
        $lockFile = storage_path('app/.installed');

        return File::exists($lockFile);
    }
}
