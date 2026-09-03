<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleSwitcherController extends Controller
{
    /**
     * Switch the active role for the current user.
     */
    public function switch(Request $request): RedirectResponse
    {
        $request->validate([
            'role' => ['required', 'string'],
        ]);

        $user = Auth::user();
        $role = $request->input('role');

        // Check if user has this role
        if (! $user->roles->contains('name', $role)) {
            return redirect()->back()->with('error', 'Anda tidak memiliki peran tersebut.');
        }

        // Store active role in session
        session(['active_role' => $role]);

        return redirect()->back()->with('success', 'Peran berhasil diubah ke: '.format_role_name($role));
    }
}
