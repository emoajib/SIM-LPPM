<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ThemeSwitcherController extends Controller
{
    public function switch(Request $request)
    {
        $theme = $request->input('theme', 'classic');
        $theme = in_array($theme, ['modern', 'classic']) ? $theme : 'classic';

        session(['sim_lppm_app_theme' => $theme]);
        session(['sim_lppm_dashboard_theme' => $theme]);

        return redirect()->back()->withCookie(cookie('sim_lppm_app_theme', $theme, 60 * 24 * 365));
    }
}
