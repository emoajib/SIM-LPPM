<?php

namespace App\Actions\Proposal;

use App\Models\CommunityServiceScheme;
use App\Models\Proposal;
use App\Models\ResearchScheme;

/**
 * Validate the team composition including students and cross-prodi rules.
 */
class ValidateTeamCompositionAction
{
    /**
     * Validate the team composition including students and cross-prodi rules.
     *
     * @return array{is_valid: bool, errors: array<int, string>}
     */
    public function execute(Proposal $proposal): array
    {
        /** @var ResearchScheme|CommunityServiceScheme|null $scheme */
        $scheme = $proposal->research_scheme_id ? $proposal->researchScheme : $proposal->communityServiceScheme;

        if (! $scheme) {
            return ['is_valid' => true, 'errors' => []];
        }

        /** @var array<string, mixed> $rules */
        $rules = $scheme->eligibility_rules ?? [];

        if (empty($rules)) { // Removed redundant check: || !is_array($rules)
            return ['is_valid' => true, 'errors' => []];
        }

        $errors = [];
        $members = $proposal->teamMembers;
        $memberCount = $members->count();

        // 1. Min/Max Members
        if (isset($rules['min_members']) && $memberCount < $rules['min_members']) {
            $errors[] = 'Minimal jumlah anggota adalah '.$rules['min_members']." (Saat ini: $memberCount).";
        }

        if (isset($rules['max_members']) && $memberCount > $rules['max_members']) {
            $errors[] = 'Maksimal jumlah anggota adalah '.$rules['max_members']." (Saat ini: $memberCount).";
        }

        // 2. Student Involvement
        $studentCount = is_array($proposal->student_members) ? count($proposal->student_members) : 0;
        if (isset($rules['min_students_involved']) && $studentCount < $rules['min_students_involved']) {
            $errors[] = 'Minimal harus melibatkan '.$rules['min_students_involved']." mahasiswa (Saat ini: $studentCount).";
        }

        // 3. Cross-Prodi Requirement
        if (! empty($rules['require_cross_prodi']) && isset($rules['min_cross_prodi_members'])) {
            $leaderProdi = $proposal->submitter->identity?->study_program_id;
            $crossProdiCount = 0;

            foreach ($members as $member) {
                if ($member->id === $proposal->submitter_id) {
                    continue;
                }

                $memberProdi = $member->identity?->study_program_id;
                if ($memberProdi && $leaderProdi && $memberProdi !== $leaderProdi) {
                    $crossProdiCount++;
                }
            }

            if ($crossProdiCount < $rules['min_cross_prodi_members']) {
                $errors[] = 'Minimal harus melibatkan '.$rules['min_cross_prodi_members']." anggota lintas prodi (Saat ini: $crossProdiCount).";
            }
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}
