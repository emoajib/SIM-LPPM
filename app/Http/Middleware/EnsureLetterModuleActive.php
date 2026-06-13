<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLetterModuleActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! (bool) Setting::get('module_persuratan_active', false)) {
            if ($request->expectsJson()) {
                abort(403, 'Modul persuratan sedang dinonaktifkan.');
            }

            return redirect()->back()->with('error', 'Modul persuratan sedang dinonaktifkan.');
        }

        return $next($request);
    }
}
