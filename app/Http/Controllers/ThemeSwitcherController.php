<?php

namespace App\Http\Controllers;

use App\Enums\Theme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ThemeSwitcherController extends Controller
{
    public function switch(Request $request): RedirectResponse
    {
        $requested = Theme::tryFromString($request->input('theme', $request->query('theme')));
        $current = Theme::fromString(session('sim_lppm_app_theme', session('sim_lppm_dashboard_theme', Theme::CLASSIC->value)));

        $theme = $requested ?? $current->toggle();

        session(['sim_lppm_app_theme' => $theme->value]);
        session(['sim_lppm_dashboard_theme' => $theme->value]);

        // Invalidate menu cache kedua tema agar tidak stale setelah switch tema
        if (Auth::check()) {
            foreach (Theme::cases() as $case) {
                Cache::forget('menu_items_'.Auth::id().'_'.(active_role() ?? 'none').'_'.$case->value);
            }
        }

        cookie()->queue(cookie('sim_lppm_app_theme', $theme->value, 60 * 24 * 365));
        cookie()->queue(cookie('sim_lppm_dashboard_theme', $theme->value, 60 * 24 * 365));

        return redirect()->back()->with('success', "Tema website berhasil diubah ke: {$theme->label()}");
    }
}
