<?php

// Vetted by SOSIOMEN - Manual Review Required by Senior Engineer/Manager

namespace App\Http\Controllers\Api\Settings;

use App\Actions\HandleHybridInstitution;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Identity;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileApiController extends Controller
{
    public function __construct(
        private HandleHybridInstitution $institutionHandler
    ) {}

    /**
     * Get authenticated user's profile and identity data.
     */
    public function show(): JsonResponse
    {
        $user = Auth::user()->load('identity');

        return response()->json([
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'whatsapp_number' => $user->whatsapp_number,
                'photo_url' => $user->getAttribute('profile_picture'),
            ],
            'identity' => $user->identity ? $this->formatIdentity($user->identity) : null,
        ]);
    }

    /**
     * Update user profile and identity data.
     */
    public function update(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'user.name' => 'required|string|max:255',
            'user.email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'user.whatsapp_number' => 'nullable|string|max:20|regex:/^62[0-9]{9,12}$/',
            'identity.identity_id' => 'nullable|string|max:50',
            'identity.type' => 'nullable|in:dosen,mahasiswa,reviewer',
            'identity.title_prefix' => 'nullable|string|max:50',
            'identity.title_suffix' => 'nullable|string|max:50',
            'identity.sinta_id' => 'nullable|string|max:100',
            'identity.scopus_id' => 'nullable|string|max:100',
            'identity.google_scholar_id' => 'nullable|string|max:100',
            'identity.wos_id' => 'nullable|string|max:100',
            'identity.gender' => 'nullable|in:L,P',
            'identity.address' => 'nullable|string|max:500',
            'identity.birthdate' => 'nullable|date',
            'identity.birthplace' => 'nullable|string|max:100',
            'identity.last_education' => 'nullable|string|max:100',
            'identity.functional_position' => 'nullable|string|max:100',
            'identity.faculty_id' => 'nullable|exists:faculties,id',
            'identity.study_program_id' => 'nullable|exists:study_programs,id',
            'identity.science_cluster_id' => 'nullable|exists:science_clusters,id',
        ]);

        $user->update($validated['user'] ?? []);

        if (isset($validated['identity'])) {
            $identityData = $validated['identity'];

            // Handle hybrid institution (existing ID or new name) - same as Livewire ProfileForm
            $institutionInput = $identityData['institution_id'] ?? null;
            $finalInstitutionId = $this->institutionHandler->execute($institutionInput);
            $finalInstitutionName = is_numeric($institutionInput) ? null : $institutionInput;

            $user->identity()->updateOrCreate(
                ['user_id' => $user->id],
                array_merge($identityData, [
                    'institution_id' => $finalInstitutionId,
                    'institution_name' => $finalInstitutionName,
                ])
            );
        }

        ActivityLog::create([
            'user_id' => $user->id,
            'activity' => 'profile_update',
            'description' => 'User memperbarui informasi profil/identitas via modern theme',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json(['message' => 'Profil diperbarui.']);
    }

    /**
     * Upload profile photo (avatar).
     */
    public function uploadPhoto(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $user = Auth::user();
        $file = $request->file('photo');
        $path = $file->store('profile-photos', 'public');

        // Delete old avatar if exists
        if ($user->hasMedia('avatar')) {
            $user->clearMediaCollection('avatar');
        }

        // Add new avatar
        $user->addMedia($file->getRealPath())
            ->usingFileName($file->getClientOriginalName())
            ->toMediaCollection('avatar');

        return response()->json([
            'message' => 'Foto profil diperbarui.',
            'photo_url' => $user->getFirstMediaUrl('avatar'),
        ]);
    }

    /**
     * Delete profile photo (avatar).
     */
    public function deletePhoto(): JsonResponse
    {
        $user = Auth::user();

        if ($user->hasMedia('avatar')) {
            $user->clearMediaCollection('avatar');
        }

        return response()->json(['message' => 'Foto profil dihapus.']);
    }

    private function formatIdentity(Identity $identity): array
    {
        return [
            'identity_id' => $identity->identity_id,
            'type' => $identity->type,
            'title_prefix' => $identity->title_prefix,
            'title_suffix' => $identity->title_suffix,
            'sinta_id' => $identity->sinta_id,
            'scopus_id' => $identity->scopus_id,
            'google_scholar_id' => $identity->google_scholar_id,
            'wos_id' => $identity->wos_id,
            'gender' => $identity->gender,
            'address' => $identity->address,
            'birthdate' => $identity->birthdate?->format('Y-m-d'),
            'birthplace' => $identity->birthplace,
            'last_education' => $identity->last_education,
            'functional_position' => $identity->functional_position,
            'institution_id' => $identity->institution_id,
            'institution_name' => $identity->institution_name,
            'faculty_id' => $identity->faculty_id,
            'study_program_id' => $identity->study_program_id,
            'science_cluster_id' => $identity->science_cluster_id,
        ];
    }
}
