<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ThemeSwitcherController extends Controller
{
    /**
     * Switch the active website theme for the current user (Modern Infographic vs Classic Tabler).
     */
    public function switch(Request $request): RedirectResponse
    {
        $theme = $request->input('theme', $request->query('theme'));

        if (! in_array($theme, ['modern', 'classic'])) {
            $current = session('sim_lppm_dashboard_theme', 'modern');
            $theme = $current === 'modern' ? 'classic' : 'modern';
        }

        // Store active theme in session
        session(['sim_lppm_dashboard_theme' => $theme]);

        // Also persist in cookie for 1 year so user preference sticks
        cookie()->queue(cookie('sim_lppm_dashboard_theme', $theme, 60 * 24 * 365));

        $themeLabel = $theme === 'modern' ? 'Modern (3D Infografis)' : 'Klasik (Standar Tabler)';

        return redirect()->back()->with('success', "Tema website berhasil diubah ke: {$themeLabel}");
    }
}
