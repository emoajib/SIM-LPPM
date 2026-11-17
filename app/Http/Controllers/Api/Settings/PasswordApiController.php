<?php

// Vetted by SOSIOMEN - Manual Review Required by Senior Engineer/Manager

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class PasswordApiController extends Controller
{
    /**
     * Update the authenticated user's password.
     * Mirrors App\Livewire\Settings\Password behaviour.
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($user !== null, 401);

        try {
            $validated = $request->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password' => ['required', 'string', PasswordRule::defaults(), 'confirmed'],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        ActivityLog::create([
            'user_id' => $user->id,
            'activity' => 'password_update',
            'description' => 'User memperbarui kata sandi via modern theme',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json(['message' => 'Kata sandi berhasil diperbarui.']);
    }
}
