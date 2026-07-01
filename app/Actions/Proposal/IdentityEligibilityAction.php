<?php

namespace App\Actions\Proposal;

use App\Enums\ProposalStatus;
use App\Models\CommunityServiceScheme;
use App\Models\Proposal;
use App\Models\ResearchScheme;
use App\Models\User;

class IdentityEligibilityAction
{
    /**
     * Evaluate if a user is eligible for a scheme based on their academic profile.
     *
     * @param  mixed  $scheme  (ResearchScheme|CommunityServiceScheme)
     * @param  string  $role  (leader|member)
     * @return array{is_eligible: bool, reason: string|null}
     */
    public function execute(User $user, $scheme, string $role = 'leader'): array
    {
        $rules = $scheme->eligibility_rules ?? [];
        if (empty($rules)) {
            return ['is_eligible' => true, 'reason' => null];
        }

        $identity = $user->identity;

        if (! $identity && $role === 'leader') {
            return [
                'is_eligible' => false,
                'reason' => 'Profil akademik Anda belum lengkap. Silakan lengkapi profil Anda terlebih dahulu.',
            ];
        }

        // 1. Functional Position (Leader only usually)
        if ($role === 'leader' && ! empty($rules['allowed_functional_positions'])) {
            $userPosition = $identity->functional_position ?? 'Tenaga Pengajar';
            if (! in_array($userPosition, $rules['allowed_functional_positions'])) {
                return [
                    'is_eligible' => false,
                    'reason' => "Jabatan fungsional Anda ($userPosition) tidak memenuhi syarat pimpinan untuk skema ini.",
                ];
            }
        }

        // 2. SINTA Score (Leader only usually)
        if ($role === 'leader') {
            $minSinta = $rules['min_sinta_score'] ?? null;
            $currentSinta = $identity->sinta_score_v3_overall ?? 0;
            if ($minSinta && $currentSinta < $minSinta) {
                return [
                    'is_eligible' => false,
                    'reason' => "Skor SINTA Anda ($currentSinta) kurang dari batas minimal ($minSinta).",
                ];
            }
        }

        // 3. Scopus Score (H-Index) (Leader only usually)
        if ($role === 'leader') {
            $minScopus = $rules['min_scopus_score'] ?? null;
            $currentScopus = $identity->scopus_h_index ?? 0;
            if ($minScopus && $currentScopus < $minScopus) {
                return [
                    'is_eligible' => false,
                    'reason' => "Skor Scopus (H-Index) Anda ($currentScopus) kurang dari batas minimal ($minScopus).",
                ];
            }
        }

        // 4. Quota Check (Active proposals)
        $activeStatuses = [
            ProposalStatus::DRAFT->value,
            ProposalStatus::SUBMITTED->value,
            ProposalStatus::NEED_ASSIGNMENT->value,
            ProposalStatus::APPROVED->value,
            ProposalStatus::WAITING_REVIEWER->value,
            ProposalStatus::UNDER_REVIEW->value,
            ProposalStatus::REVIEWED->value,
            ProposalStatus::REVISION_NEEDED->value,
            ProposalStatus::REVISION_SUBMITTED->value,
        ];

        if ($role === 'leader' && isset($rules['max_proposals_as_head'])) {
            $headCount = $this->countActiveLeaderProposals($user, $scheme, $activeStatuses);

            if ($headCount >= $rules['max_proposals_as_head']) {
                $details = $this->getActiveLeaderProposalsDetails($user, $scheme, $activeStatuses);

                return [
                    'is_eligible' => false,
                    'reason' => "Anda sudah mencapai batas maksimal usulan sebagai Ketua ($headCount dari {$rules['max_proposals_as_head']}). Detail proposal aktif: $details.",
                ];
            }
        }

        // 4.1 Total Quota Check (across all schemes of the same type)
        if ($role === 'leader' && isset($rules['max_total_proposals_as_head'])) {
            $totalHeadCount = $this->countActiveLeaderProposals($user, $scheme, $activeStatuses);

            if ($totalHeadCount >= $rules['max_total_proposals_as_head']) {
                $details = $this->getActiveLeaderProposalsDetails($user, $scheme, $activeStatuses);

                return [
                    'is_eligible' => false,
                    'reason' => "Anda sudah mencapai batas maksimal total usulan sebagai Ketua di kategori ini ($totalHeadCount dari {$rules['max_total_proposals_as_head']}). Detail proposal aktif: $details.",
                ];
            }
        }

        if ($role === 'member' && isset($rules['max_proposals_as_member'])) {
            $proposals = $this->getActiveMemberProposals($user, $scheme, $activeStatuses);
            $memberCount = $proposals->count();

            if ($memberCount >= $rules['max_proposals_as_member']) {
                $details = $proposals->map(function ($p) {
                    $type = $p->detailable_type === 'App\Models\Research' ? 'Penelitian' : 'Pengabdian';
                    $schemeName = $p->researchScheme->name ?? $p->communityServiceScheme->name ?? '-';

                    return "'{$p->title}' ({$type} - {$schemeName})";
                })->implode(', ');

                return [
                    'is_eligible' => false,
                    'reason' => "Dosen ini sudah mencapai batas maksimal keterlibatan sebagai Anggota ($memberCount dari {$rules['max_proposals_as_member']}). Detail proposal: $details.",
                ];
            }
        }

        // 4.2 Total Member Quota Check (across all proposals of the same type)
        if ($role === 'member' && isset($rules['max_total_proposals_as_member'])) {
            $proposals = $this->getActiveMemberProposals($user, $scheme, $activeStatuses);
            $uniqueProposals = $proposals->unique('id');
            $totalMemberCount = $uniqueProposals->count();

            if ($totalMemberCount >= $rules['max_total_proposals_as_member']) {
                $details = $uniqueProposals->map(function ($p) {
                    $type = $p->detailable_type === 'App\Models\Research' ? 'Penelitian' : 'Pengabdian';
                    $schemeName = $p->researchScheme->name ?? $p->communityServiceScheme->name ?? '-';

                    return "'{$p->title}' ({$type} - {$schemeName})";
                })->implode(', ');

                return [
                    'is_eligible' => false,
                    'reason' => "Dosen ini sudah mencapai batas maksimal total keterlibatan sebagai Anggota ($totalMemberCount dari {$rules['max_total_proposals_as_member']}). Detail proposal: $details.",
                ];
            }
        }

        // 5. Pending Reports Block (Granular Rule)
        $blockRole = $rules['pending_report_block_role'] ?? 'none';
        $shouldBlock = ($role === 'leader' && in_array($blockRole, ['leader', 'both'])) ||
            ($role === 'member' && in_array($blockRole, ['member', 'both']));

        if ($shouldBlock) {
            $pendingCount = Proposal::query()->where('submitter_id', '=', $user->id)
                ->where('start_year', '<', date('Y'))
                ->whereNotIn('status', [ProposalStatus::COMPLETED->value, ProposalStatus::REJECTED->value])
                ->count('*');

            if ($pendingCount > 0) {
                return [
                    'is_eligible' => false,
                    'reason' => "Dosen memiliki $pendingCount tanggungan usulan tahun sebelumnya yang belum diselesaikan.",
                ];
            }
        }

        return ['is_eligible' => true, 'reason' => null];
    }

    private function countActiveLeaderProposals(User $user, $scheme, array $activeStatuses): int
    {
        $query = Proposal::where('submitter_id', $user->id)
            ->whereIn('status', $activeStatuses);

        if ($scheme instanceof ResearchScheme) {
            $query->whereNotNull('research_scheme_id');
        } elseif ($scheme instanceof CommunityServiceScheme) {
            $query->whereNotNull('community_service_scheme_id');
        }

        return $query->count();
    }

    private function getActiveLeaderProposalsDetails(User $user, $scheme, array $activeStatuses): string
    {
        $query = Proposal::with(['researchScheme', 'communityServiceScheme'])
            ->where('submitter_id', $user->id)
            ->whereIn('status', $activeStatuses);

        if ($scheme instanceof ResearchScheme) {
            $query->whereNotNull('research_scheme_id');
        } elseif ($scheme instanceof CommunityServiceScheme) {
            $query->whereNotNull('community_service_scheme_id');
        }

        return $query->get()->map(function ($p, $i) {
            $type = $p->detailable_type === 'App\Models\Research' ? 'Penelitian' : 'Pengabdian';
            $schemeName = $p->researchScheme->name ?? $p->communityServiceScheme->name ?? '-';

            return ($i + 1).". '{$p->title}' ({$type} - {$schemeName})";
        })->implode(', ');
    }

    private function getActiveMemberProposals(User $user, $scheme, array $activeStatuses)
    {
        $query = Proposal::with(['researchScheme', 'communityServiceScheme'])
            ->join('proposal_user', 'proposals.id', '=', 'proposal_user.proposal_id')
            ->where('proposal_user.user_id', $user->id)
            ->where('proposal_user.role', '!=', 'Ketua')
            ->whereIn('proposals.status', $activeStatuses)
            ->select('proposals.*');

        if ($scheme instanceof ResearchScheme) {
            $query->whereNotNull('proposals.research_scheme_id');
        } elseif ($scheme instanceof CommunityServiceScheme) {
            $query->whereNotNull('proposals.community_service_scheme_id');
        }

        return $query->get();
    }
}
