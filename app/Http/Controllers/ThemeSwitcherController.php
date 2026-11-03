<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ThemeSwitcherController extends Controller
{
    public function switch(Request $request): RedirectResponse
    {
        $theme = $request->input('theme', $request->query('theme'));

        if (! in_array($theme, ['modern', 'classic'])) {
            $current = session('sim_lppm_app_theme', session('sim_lppm_dashboard_theme', 'modern'));
            $theme = $current === 'modern' ? 'classic' : 'modern';
        }

        session(['sim_lppm_app_theme' => $theme]);
        session(['sim_lppm_dashboard_theme' => $theme]);

        // Invalidate menu cache agar tidak stale setelah switch tema
        if (Auth::check()) {
            Cache::forget('menu_items_'.Auth::id().'_'.(active_role() ?? 'none').'_'.($theme === 'modern' ? 'classic' : 'modern'));
        }

        cookie()->queue(cookie('sim_lppm_app_theme', $theme, 60 * 24 * 365));
        cookie()->queue(cookie('sim_lppm_dashboard_theme', $theme, 60 * 24 * 365));

        $themeLabel = $theme === 'modern' ? 'Modern (3D Infografis)' : 'Klasik (Standar Tabler)';

        return redirect()->back()->with('success', "Tema website berhasil diubah ke: {$themeLabel}");
    }
}
