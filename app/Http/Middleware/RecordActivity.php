<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class RecordActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! auth()->check()) {
            return $response;
        }

        $user = auth()->user();

        // Throttle last_active_at update (max once every 5 minutes) to avoid
        // a DB write on every single request (including Livewire updates).
        $lastActive = $user->last_active_at;
        if (! $lastActive || $lastActive->lt(now()->subMinutes(5))) {
            $user->update(['last_active_at' => now()]);
        }

        // Only record significant page views: GET, non-JSON, non-Livewire,
        // successful HTML responses.
        if (! $request->isMethod('GET')
            || $request->expectsJson()
            || str_contains($request->url(), '/livewire/')
            || $response->getStatusCode() !== 200
            || ! str_contains((string) $response->headers->get('Content-Type', ''), 'text/html')) {
            return $response;
        }

        // Deduplicate: don't log the same URL twice within a short window.
        $dedupKey = 'activity_view:'.$user->id.':'.md5($request->fullUrl());
        if (Cache::has($dedupKey)) {
            return $response;
        }
        Cache::put($dedupKey, true, now()->addMinutes(2));

        ActivityLog::create([
            'user_id' => $user->id,
            'activity' => 'page_view',
            'description' => 'Membuka halaman',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
        ]);

        return $response;
    }
}
