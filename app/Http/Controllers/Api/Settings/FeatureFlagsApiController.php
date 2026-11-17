<?php

// Vetted by SOSIOMEN - Manual Review Required by Senior Engineer/Manager

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Modern-theme API for Feature Flags.
 * Mirrors App\Livewire\Settings\FeatureFlags (same keys, same cache-busting).
 */
class FeatureFlagsApiController extends Controller
{
    /**
     * Keys that invalidate the navigation menu cache when changed.
     */
    private const MENU_CACHE_KEYS = [
        'feature_roadmap_active',
        'feature_kaprodi_validation',
        'module_persuratan_active',
        'surat_signature_mode',
        'surat_wet_signature_bypass',
        'feature_community_partner_required',
    ];

    public function show(): JsonResponse
    {
        $this->authorizeAdmin();

        return response()->json([
            'feature_roadmap_active' => (bool) Setting::get('feature_roadmap_active', false),
            'feature_kaprodi_validation' => (bool) Setting::get('feature_kaprodi_validation', false),
            'module_persuratan_active' => (bool) Setting::get('module_persuratan_active', false),
            'surat_signature_mode' => (string) Setting::get('surat_signature_mode', 'tte'),
            'surat_wet_signature_bypass' => (bool) Setting::get('surat_wet_signature_bypass', false),
            'feature_community_partner_required' => (bool) Setting::get('feature_community_partner_required', true),
            'reviewer_count_required' => (int) Setting::get('reviewer_count_required', 1),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'feature_roadmap_active' => 'sometimes|boolean',
            'feature_kaprodi_validation' => 'sometimes|boolean',
            'module_persuratan_active' => 'sometimes|boolean',
            'surat_signature_mode' => 'sometimes|in:tte,manual',
            'surat_wet_signature_bypass' => 'sometimes|boolean',
            'feature_community_partner_required' => 'sometimes|boolean',
            'reviewer_count_required' => 'sometimes|integer|in:1,2,3',
        ]);

        $booleans = [
            'feature_roadmap_active',
            'feature_kaprodi_validation',
            'module_persuratan_active',
            'surat_wet_signature_bypass',
            'feature_community_partner_required',
        ];

        foreach ($booleans as $key) {
            if (array_key_exists($key, $validated)) {
                Setting::set($key, (bool) $validated[$key], 'boolean');
            }
        }

        if (array_key_exists('surat_signature_mode', $validated)) {
            Setting::set('surat_signature_mode', $validated['surat_signature_mode'], 'string');

            // Non-manual mode cannot bypass approval (mirrors Livewire behaviour).
            if ($validated['surat_signature_mode'] !== 'manual') {
                Setting::set('surat_wet_signature_bypass', false, 'boolean');
            }
        }

        if (array_key_exists('reviewer_count_required', $validated)) {
            Setting::set('reviewer_count_required', (int) $validated['reviewer_count_required'], 'integer');
        }

        // Bust menu cache if any menu-affecting flag changed.
        if (array_intersect(array_keys($validated), self::MENU_CACHE_KEYS) !== []) {
            Setting::set('menu_cache_version', (int) Setting::get('menu_cache_version', 1) + 1, 'integer');
        }

        return response()->json([
            'message' => 'Feature Flags berhasil diperbarui.',
            'data' => $this->show()->getData(true),
        ]);
    }

    private function authorizeAdmin(): void
    {
        abort_unless(
            Auth::user()?->activeHasAnyRole(['admin lppm', 'superadmin']),
            403
        );
    }
}
