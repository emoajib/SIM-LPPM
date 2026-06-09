<?php

namespace App\Actions\Reviewer;

use App\Models\Institution;
use App\Models\ReviewerProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * "Efficiency is the goal, but Integrity is the foundation."
 */
class CreateExternalReviewerAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): User
    {
        return DB::transaction(function () use ($data) {

            // 1. Create Base User
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            // Assign reviewer role
            $user->assignRole('reviewer');

            // 2. Handle Institution Logic
            $institutionId = $data['institution_id'] ?? null;
            $institutionName = $data['institution_name'] ?? null;

            // SMART LOOKUP: Auto-detect existing institution by name if manual input provided
            if (empty($institutionId) && ! empty($institutionName)) {
                $existing = Institution::where('name', $institutionName)->first();
                if ($existing) {
                    $institutionId = $existing->id;
                    $institutionName = null; // Clean up manual input
                }
            }

            if (! $institutionId && empty($institutionName)) {
                throw ValidationException::withMessages([
                    'institution' => 'Institution must be selected or manually entered.',
                ]);
            }

            // 3. Create Profile
            ReviewerProfile::create([
                'user_id' => $user->id,
                'institution_id' => $institutionId,
                'institution_name' => $institutionName,
                'academic_title' => $data['academic_title'] ?? null,
                'nidn' => $data['nidn'] ?? null,
                'expertise_keywords' => $data['expertise_keywords'] ?? null,
            ]);

            return $user;
        });
    }
}
