<?php

namespace App\Services;

use App\Models\CommunityServiceScheme;
use App\Models\Identity;
use App\Models\Proposal;
use App\Models\ResearchScheme;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EligibilityService
{
    /**
     * Check if a dosen can submit to a specific research scheme
     */
    public function canSubmitResearchProposal(Identity $identity, ResearchScheme $scheme): bool
    {
        return $this->checkEligibility($identity, $scheme->eligibility_rules ?? [])['eligible'];
    }

    /**
     * Check if a dosen can submit to a specific community service scheme
     */
    public function canSubmitCommunityServiceProposal(Identity $identity, CommunityServiceScheme $scheme): bool
    {
        return $this->checkEligibility($identity, $scheme->eligibility_rules ?? [])['eligible'];
    }

    /**
     * Check if a user can create a new proposal of given type
     *
     * @return array{can_create: bool, reason: ?string, quota_info: array{head_limit: int, head_current: int, member_limit: int, member_current: int}}
     */
    public function canCreateProposal(User $user, string $type): array
    {
        $schemes = $type === 'research'
            ? ResearchScheme::all()
            : CommunityServiceScheme::all();

        $totalHeadLimit = 0;
        $totalMemberLimit = 0;

        // Find the maximum global limits across all schemes
        foreach ($schemes as $scheme) {
            $rules = $scheme->eligibility_rules ?? [];
            if (isset($rules['max_total_proposals_as_head'])) {
                $totalHeadLimit = max($totalHeadLimit, (int) $rules['max_total_proposals_as_head']);
            }
            if (isset($rules['max_total_proposals_as_member'])) {
                $totalMemberLimit = max($totalMemberLimit, (int) $rules['max_total_proposals_as_member']);
            }
        }

        // Count current active proposals (including drafts) of the SPECIFIC type
        $activeStatuses = [
            'draft',
            'submitted',
            'need_assignment',
            'approved',
            'waiting_reviewer',
            'under_review',
            'reviewed',
            'revision_needed',
            'revision_submitted',
        ];

        $headQuery = Proposal::where('submitter_id', $user->id)
            ->whereIn('status', $activeStatuses);

        if ($type === 'research') {
            $headQuery->whereNotNull('research_scheme_id');
        } else {
            $headQuery->whereNotNull('community_service_scheme_id');
        }

        $currentHeadCount = $headQuery->count();

        $memberQuery = DB::table('proposal_user')
            ->join('proposals', 'proposal_user.proposal_id', '=', 'proposals.id')
            ->where('proposal_user.user_id', $user->id)
            ->where('proposal_user.role', '!=', 'Ketua')
            ->whereIn('proposals.status', $activeStatuses);

        if ($type === 'research') {
            $memberQuery->whereNotNull('proposals.research_scheme_id');
        } else {
            $memberQuery->whereNotNull('proposals.community_service_scheme_id');
        }

        $currentMemberCount = $memberQuery->distinct()->count('proposal_user.proposal_id');

        $quotaInfo = [
            'head_limit' => $totalHeadLimit,
            'head_current' => $currentHeadCount,
            'member_limit' => $totalMemberLimit,
            'member_current' => $currentMemberCount,
        ];

        // Check head quota
        if ($totalHeadLimit > 0 && $currentHeadCount >= $totalHeadLimit) {
            $messageService = app(QuotaMessageService::class);

            return [
                'can_create' => false,
                'reason' => $messageService->getMessage('access_denied', [
                    'limit' => $totalHeadLimit,
                ]),
                'quota_info' => $quotaInfo,
            ];
        }

        // Check member quota
        if ($totalMemberLimit > 0 && $currentMemberCount >= $totalMemberLimit) {
            $messageService = app(QuotaMessageService::class);

            return [
                'can_create' => false,
                'reason' => $messageService->getMessage('member_denied', [
                    'limit' => $totalMemberLimit,
                ]),
                'quota_info' => $quotaInfo,
            ];
        }

        return [
            'can_create' => true,
            'reason' => null,
            'quota_info' => $quotaInfo,
        ];
    }

    /**
     * Get eligibility status for a dosen against a scheme
     * Returns: ['eligible' => bool, 'passed_checks' => [], 'failed_checks' => []]
     */
    public function getEligibilityStatus(Identity $identity, array $rules): array
    {
        return $this->checkEligibility($identity, $rules);
    }

    /**
     * Get all eligible research schemes for a dosen
     */
    public function getEligibleResearchSchemes(Identity $identity): Collection
    {
        return ResearchScheme::query()
            ->get()
            ->filter(fn ($scheme) => $this->canSubmitResearchProposal($identity, $scheme));
    }

    /**
     * Get all ineligible research schemes for a dosen with reasons
     */
    public function getIneligibleResearchSchemes(Identity $identity): Collection
    {
        return ResearchScheme::query()
            ->get()
            ->map(function ($scheme) use ($identity) {
                $status = $this->checkEligibility($identity, $scheme->eligibility_rules ?? []);

                return [
                    'scheme' => $scheme,
                    'eligible' => $status['eligible'],
                    'failed_checks' => $status['failed_checks'],
                ];
            })
            ->filter(fn ($item) => ! $item['eligible']);
    }

    /**
     * Get all eligible community service schemes for a dosen
     */
    public function getEligibleCommunityServiceSchemes(Identity $identity): Collection
    {
        return CommunityServiceScheme::query()
            ->where('is_active', true)
            ->get()
            ->filter(fn ($scheme) => $this->canSubmitCommunityServiceProposal($identity, $scheme));
    }

    /**
     * Check eligibility against requirements
     *
     * @return array ['eligible' => bool, 'passed_checks' => [], 'failed_checks' => []]
     */
    private function checkEligibility(Identity $identity, array $rules): array
    {
        $passedChecks = [];
        $failedChecks = [];

        // Check Minimum SINTA Score
        if (isset($rules['min_sinta_score'])) {
            $minScore = (float) $rules['min_sinta_score'];
            $currentScore = (float) ($identity->sinta_score_v3_overall ?? 0);

            if ($currentScore >= $minScore) {
                $passedChecks[] = [
                    'name' => 'SINTA Score',
                    'required' => $minScore,
                    'current' => $currentScore,
                    'type' => 'numeric',
                ];
            } else {
                $failedChecks[] = [
                    'name' => 'SINTA Score',
                    'required' => $minScore,
                    'current' => $currentScore,
                    'type' => 'numeric',
                    'message' => "Skor SINTA Anda ({$currentScore}) di bawah minimum ({$minScore})",
                ];
            }
        }

        // Check Minimum Scopus H-Index
        if (isset($rules['min_scopus_score'])) {
            $minScore = (float) $rules['min_scopus_score'];
            $currentScore = (float) ($identity->scopus_h_index ?? 0);

            if ($currentScore >= $minScore) {
                $passedChecks[] = [
                    'name' => 'Scopus H-Index',
                    'required' => $minScore,
                    'current' => $currentScore,
                    'type' => 'numeric',
                ];
            } else {
                $failedChecks[] = [
                    'name' => 'Scopus H-Index',
                    'required' => $minScore,
                    'current' => $currentScore,
                    'type' => 'numeric',
                    'message' => "H-Index Scopus Anda ({$currentScore}) di bawah minimum ({$minScore})",
                ];
            }
        }

        // Check Allowed Functional Positions
        if (isset($rules['allowed_functional_positions']) && ! empty($rules['allowed_functional_positions'])) {
            $allowedPositions = (array) $rules['allowed_functional_positions'];
            $currentPosition = $identity->functional_position;

            if ($currentPosition && in_array($currentPosition, $allowedPositions)) {
                $passedChecks[] = [
                    'name' => 'Posisi Fungsional',
                    'required' => implode(', ', $allowedPositions),
                    'current' => $currentPosition,
                    'type' => 'string',
                ];
            } else {
                $failedChecks[] = [
                    'name' => 'Posisi Fungsional',
                    'required' => implode(', ', $allowedPositions),
                    'current' => $currentPosition ?? 'Tidak Ditetapkan',
                    'type' => 'string',
                    'message' => 'Posisi Anda ('.($currentPosition ?? 'Tidak Ditetapkan').') bukan termasuk yang diizinkan',
                ];
            }
        }

        // Check Minimum Scopus Documents
        if (isset($rules['min_scopus_documents'])) {
            $minDocs = (int) $rules['min_scopus_documents'];
            $currentDocs = (int) ($identity->scopus_document_count ?? 0);

            if ($currentDocs >= $minDocs) {
                $passedChecks[] = [
                    'name' => 'Scopus Documents',
                    'required' => $minDocs,
                    'current' => $currentDocs,
                    'type' => 'numeric',
                ];
            } else {
                $failedChecks[] = [
                    'name' => 'Scopus Documents',
                    'required' => $minDocs,
                    'current' => $currentDocs,
                    'type' => 'numeric',
                    'message' => "Jumlah publikasi Scopus Anda ({$currentDocs}) di bawah minimum ({$minDocs})",
                ];
            }
        }

        // Check Minimum Scopus Citations
        if (isset($rules['min_scopus_citations'])) {
            $minCitations = (int) $rules['min_scopus_citations'];
            $currentCitations = (int) ($identity->scopus_citation_count ?? 0);

            if ($currentCitations >= $minCitations) {
                $passedChecks[] = [
                    'name' => 'Scopus Citations',
                    'required' => $minCitations,
                    'current' => $currentCitations,
                    'type' => 'numeric',
                ];
            } else {
                $failedChecks[] = [
                    'name' => 'Scopus Citations',
                    'required' => $minCitations,
                    'current' => $currentCitations,
                    'type' => 'numeric',
                    'message' => "Jumlah sitasi Scopus Anda ({$currentCitations}) di bawah minimum ({$minCitations})",
                ];
            }
        }

        // Check Minimum Education Level
        if (isset($rules['min_education_level'])) {
            $minLevel = $rules['min_education_level']; // e.g., 'S2' or 'S3'
            $currentLevel = $identity->last_education;

            $educationHierarchy = ['S1' => 1, 'S2' => 2, 'S3' => 3];
            $minLevelValue = $educationHierarchy[$minLevel] ?? 0;
            $currentLevelValue = $educationHierarchy[$currentLevel] ?? 0;

            if ($currentLevelValue >= $minLevelValue) {
                $passedChecks[] = [
                    'name' => 'Tingkat Pendidikan',
                    'required' => $minLevel,
                    'current' => $currentLevel ?? 'Tidak Ditetapkan',
                    'type' => 'string',
                ];
            } else {
                $failedChecks[] = [
                    'name' => 'Tingkat Pendidikan',
                    'required' => $minLevel,
                    'current' => $currentLevel ?? 'Tidak Ditetapkan',
                    'type' => 'string',
                    'message' => 'Pendidikan Anda ('.($currentLevel ?? 'Tidak Ditetapkan').") di bawah minimum ({$minLevel})",
                ];
            }
        }

        // Check Minimum Affiliation Score (if available)
        if (isset($rules['min_affiliation_score'])) {
            $minScore = (float) $rules['min_affiliation_score'];
            $currentScore = (float) ($identity->affil_score_v3_overall ?? 0);

            if ($currentScore >= $minScore) {
                $passedChecks[] = [
                    'name' => 'Skor Afiliasi',
                    'required' => $minScore,
                    'current' => $currentScore,
                    'type' => 'numeric',
                ];
            } else {
                $failedChecks[] = [
                    'name' => 'Skor Afiliasi',
                    'required' => $minScore,
                    'current' => $currentScore,
                    'type' => 'numeric',
                    'message' => "Skor afiliasi Anda ({$currentScore}) di bawah minimum ({$minScore})",
                ];
            }
        }

        return [
            'eligible' => empty($failedChecks),
            'passed_checks' => $passedChecks,
            'failed_checks' => $failedChecks,
        ];
    }

    /**
     * Get count of dosen eligible for a research scheme
     */
    public function countEligibleForResearchScheme(ResearchScheme $scheme): int
    {
        $rules = $scheme->eligibility_rules ?? [];

        return Identity::query()
            ->get()
            ->filter(function ($identity) use ($rules) {
                return $this->checkEligibility($identity, $rules)['eligible'];
            })
            ->count();
    }

    /**
     * Get count of dosen ineligible for a research scheme with breakdown
     */
    public function getResearchSchemeEligibilityBreakdown(ResearchScheme $scheme): array
    {
        $rules = $scheme->eligibility_rules ?? [];
        $identities = Identity::query()->get();

        $breakdown = [
            'total_dosen' => $identities->count(),
            'eligible' => 0,
            'ineligible_by_reason' => [
                'SINTA Score' => 0,
                'Scopus H-Index' => 0,
                'Functional Position' => 0,
                'Scopus Documents' => 0,
                'Scopus Citations' => 0,
                'Education Level' => 0,
                'Affiliation Score' => 0,
            ],
        ];

        foreach ($identities as $identity) {
            $status = $this->checkEligibility($identity, $rules);

            if ($status['eligible']) {
                $breakdown['eligible']++;
            } else {
                foreach ($status['failed_checks'] as $failedCheck) {
                    $breakdown['ineligible_by_reason'][$failedCheck['name']]++;
                }
            }
        }

        $breakdown['ineligible'] = $breakdown['total_dosen'] - $breakdown['eligible'];

        return $breakdown;
    }

    /**
     * Get detailed eligibility status for all dosen against a scheme
     */
    public function getDetailedResearchSchemeEligibility(ResearchScheme $scheme): Collection
    {
        $rules = $scheme->eligibility_rules ?? [];

        return Identity::query()
            ->with('user')
            ->get()
            ->map(function ($identity) use ($rules) {
                $status = $this->checkEligibility($identity, $rules);

                $user = $identity->user;

                return [
                    'identity' => $identity,
                    'user' => $user,
                    'name' => $user->name ?? 'Unknown',
                    'nip' => $identity->identity_id,
                    'eligible' => $status['eligible'],
                    'passed_checks' => $status['passed_checks'],
                    'failed_checks' => $status['failed_checks'],
                ];
            });
    }
}
