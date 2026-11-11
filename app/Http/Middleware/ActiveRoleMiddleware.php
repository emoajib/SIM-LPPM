<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

class ActiveRoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip if app is not installed or on installer/livewire routes
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        if (Auth::check()) {
            $user = Auth::user();
            $activeRole = session('active_role');
            $roleNames = $user->getRoleNames();

            // If no active role set, prefer 'dosen' when the user has it
            // (so a dosen who is also admin/leader defaults to the dosen view),
            // otherwise fall back to the first role.
            if (! $activeRole || ! $roleNames->contains($activeRole)) {
                $preferred = $roleNames->contains('dosen') ? 'dosen' : $roleNames->first();

                if ($preferred) {
                    session(['active_role' => $preferred]);
                }
            }
        }

        return $next($request);
    }

    /**
     * Determine if the middleware should skip processing.
     */
    private function shouldSkip(Request $request): bool
    {
        // Skip if app is not installed
        if (! File::exists(storage_path('app/.installed'))) {
            return true;
        }

        // Skip for installer routes
        $path = $request->path();
        if (str_starts_with($path, 'install')) {
            return true;
        }

        // Skip for Livewire internal routes
        if (preg_match('/^livewire-[a-f0-9]+\//', $path)) {
            return true;
        }

        return false;
    }
}
