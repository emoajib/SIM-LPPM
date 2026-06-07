<?php

namespace App\Services;

use App\Actions\Proposal\IdentityEligibilityAction;
use App\Enums\ProposalStatus;
use App\Models\CommunityServiceScheme;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\ResearchScheme;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Service to check if a lecturer is eligible to submit a new proposal.
 */
class LecturerEligibilityService
{
    const GRACE_PERIOD_MINUTES = 5;

    const SCHEDULE_TIMEZONE = 'Asia/Jakarta';

    /**
     * Parse a schedule date string into a Carbon instance with timezone.
     * Handles both date-only (Y-m-d) and datetime (Y-m-d H:i:s) formats.
     * For date-only end dates, applies endOfDay() for backward compatibility.
     */
    public static function parseScheduleDate(?string $value, string $position = 'start'): ?Carbon
    {
        if (! $value) {
            return null;
        }

        $dt = Carbon::parse($value, self::SCHEDULE_TIMEZONE);

        // Backward compatibility: date-only values (10 chars) get endOfDay for end positions
        if (strlen($value) === 10 && $position === 'end') {
            $dt = $dt->endOfDay();
        }

        return $dt;
    }

    /**
     * Check if current time is within the schedule window defined by start and end dates.
     * Includes a grace period after the end date.
     */
    public static function isWithinSchedule(?string $startDate, ?string $endDate): bool
    {
        if (! $startDate || ! $endDate) {
            return true;
        }

        $now = Carbon::now(self::SCHEDULE_TIMEZONE);
        $start = static::parseScheduleDate($startDate, 'start');
        $end = static::parseScheduleDate($endDate, 'end');

        if (! $start || ! $end) {
            return true;
        }

        $endWithGrace = (clone $end)->addMinutes(self::GRACE_PERIOD_MINUTES);

        return $now->between($start, $endWithGrace);
    }

    /**
     * Check if a lecturer is eligible to submit a new proposal as Chairperson.
     *
     * @param  string|null  $type  'research', 'pkm', or null for all
     * @return array ['eligible' => bool, 'reasons' => array, 'period' => array]
     */
    public function checkEligibility(User $user, ?string $type = null): array
    {
        $now = Carbon::now();
        $currentYear = $now->year;
        $currentMonth = $now->month;

        if ($currentMonth >= 9 || $currentMonth <= 2) {
            $currentSemester = 'ganjil';
            $prevSemester = 'genap';
            $prevYear = ($currentMonth >= 9) ? $currentYear : $currentYear - 1;
        } else {
            $currentSemester = 'genap';
            $prevSemester = 'ganjil';
            $prevYear = $currentYear - 1;
        }

        $reasons = [];
        $memberReasons = [];
        $scheduleInfo = $this->getScheduleStatus($user);

        // --- 1. Schedule Validation (type-aware) ---
        if ($type === 'research' && ! $scheduleInfo['research_open']) {
            $reasons[] = 'Jadwal pengajuan Penelitian saat ini ditutup.';
        } elseif ($type === 'pkm' && ! $scheduleInfo['pkm_open']) {
            $reasons[] = 'Jadwal pengajuan Pengabdian Masyarakat saat ini ditutup.';
        } elseif ($type === null && ! $scheduleInfo['research_open'] && ! $scheduleInfo['pkm_open']) {
            $reasons[] = 'Sistem saat ini ditutup untuk pengajuanusulan baru (bukan periode pendaftaran).';
        }

        // --- 2. Historical Obligation Checks (always checked, affect all types) ---
        $prevProposals = Proposal::with('outputs')->where('submitter_id', $user->id)
            ->whereIn('status', [ProposalStatus::APPROVED, ProposalStatus::COMPLETED])
            ->where(function ($query) use ($prevYear, $prevSemester) {
                if ($prevSemester === 'ganjil') {
                    $query->where(function ($q) use ($prevYear) {
                        $q->where(function ($sq) use ($prevYear) {
                            $sq->whereYear('created_at', $prevYear)->whereMonth('created_at', '>=', 9);
                        })->orWhere(function ($sq) use ($prevYear) {
                            $sq->whereYear('created_at', $prevYear + 1)->whereMonth('created_at', '<=', 2);
                        });
                    });
                } else {
                    $query->whereYear('created_at', $prevYear)->whereMonth('created_at', '>=', 3)->whereMonth('created_at', '<=', 8);
                }
            })
            ->get();

        foreach ($prevProposals as $proposal) {
            $hasFinalReport = ProgressReport::where('proposal_id', $proposal->id)->where('reporting_period', 'final')->whereIn('status', ['approved', 'completed'])->exists();
            if (! $hasFinalReport) {
                $reasons[] = "Proposal '{$proposal->title}' belum memiliki Laporan Akhir yang disetujui.";
            }

            $targets = $proposal->outputs->where('category', 'Wajib');
            foreach ($targets as $target) {
                $isSubmitted = DB::table('mandatory_outputs')->join('progress_reports', 'mandatory_outputs.progress_report_id', '=', 'progress_reports.id')->where('progress_reports.proposal_id', $proposal->id)->where('mandatory_outputs.proposal_output_id', $target->id)->exists();
                if (! $isSubmitted) {
                    $reasons[] = "Proposal '{$proposal->title}' belum memenuhi luaran wajib: {$target->type}.";
                }
            }
        }

        // --- 3. Scheme-Specific Eligibility Check (type-aware) ---
        $researchOpen = $type === null || $type === 'research';
        $pkmOpen = $type === null || $type === 'pkm';

        if (($researchOpen && $scheduleInfo['research_open']) || ($pkmOpen && $scheduleInfo['pkm_open'])) {
            $effectiveEligible = false;
            $identityReasons = [];
            $eligibilityAction = app(IdentityEligibilityAction::class);

            if ($researchOpen && $scheduleInfo['research_open']) {
                foreach (ResearchScheme::all() as $scheme) {
                    $res = $eligibilityAction->execute($user, $scheme);
                    if ($res['is_eligible']) {
                        $effectiveEligible = true;
                        break;
                    }
                    $identityReasons[] = $res['reason'];
                }
            }

            if (! $effectiveEligible && $pkmOpen && $scheduleInfo['pkm_open']) {
                foreach (CommunityServiceScheme::all() as $scheme) {
                    $res = $eligibilityAction->execute($user, $scheme);
                    if ($res['is_eligible']) {
                        $effectiveEligible = true;
                        break;
                    }
                    $identityReasons[] = $res['reason'];
                }
            }

            if (! $effectiveEligible) {
                if ($type === 'research') {
                    $totalAvailableSchemes = ResearchScheme::count();
                } elseif ($type === 'pkm') {
                    $totalAvailableSchemes = CommunityServiceScheme::count();
                } else {
                    $totalAvailableSchemes = ResearchScheme::count() + CommunityServiceScheme::count();
                }
                if ($totalAvailableSchemes > 0) {
                    $reasons = array_merge($reasons, array_unique($identityReasons));
                }
            }

            // --- 4. Member Quota Check (type-aware) ---
            if ($researchOpen && $scheduleInfo['research_open']) {
                foreach (ResearchScheme::all() as $scheme) {
                    $res = $eligibilityAction->execute($user, $scheme, 'member');
                    if (! $res['is_eligible']) {
                        $memberReasons[] = $res['reason'];
                    }
                }
            }
            if ($pkmOpen && $scheduleInfo['pkm_open']) {
                foreach (CommunityServiceScheme::all() as $scheme) {
                    $res = $eligibilityAction->execute($user, $scheme, 'member');
                    if (! $res['is_eligible']) {
                        $memberReasons[] = $res['reason'];
                    }
                }
            }
        }

        return [
            'eligible' => empty($reasons),
            'reasons' => $reasons,
            'member_reasons' => ! empty($memberReasons) ? array_unique($memberReasons) : [],
            'period' => [
                'current_semester' => $currentSemester,
                'current_year' => $currentYear,
                'checked_semester' => $prevSemester,
                'checked_year' => $prevYear,
            ],
            'schedule' => $scheduleInfo,
        ];
    }

    /**
     * Generate a qualification snapshot for a proposal at submission time.
     * Freezes eligibility data so retroactive rule changes don't affect submitted proposals.
     */
    public function generateSnapshot(User $user, Proposal $proposal): array
    {
        $identity = $user->identity;
        $scheme = $proposal->detailable_type === 'App\Models\Research'
            ? $proposal->researchScheme
            : $proposal->communityServiceScheme;

        $activeStatuses = [
            ProposalStatus::DRAFT->value,
            ProposalStatus::SUBMITTED->value,
            ProposalStatus::NEED_ASSIGNMENT->value,
            ProposalStatus::APPROVED->value,
            ProposalStatus::WAITING_REVIEWER->value,
            ProposalStatus::UNDER_REVIEW->value,
            ProposalStatus::REVIEWED->value,
            ProposalStatus::REVISION_NEEDED->value,
        ];

        $activeHeadCount = Proposal::where('submitter_id', $user->id)
            ->whereIn('status', $activeStatuses)
            ->count();

        return [
            'functional_position' => $identity?->functional_position,
            'sinta_score_v3_overall' => $identity?->sinta_score_v3_overall,
            'scopus_h_index' => $identity?->scopus_h_index,
            'active_head_proposals_count' => $activeHeadCount,
            'scheme_type' => $proposal->detailable_type === 'App\Models\Research' ? 'research' : 'community_service',
            'scheme_id' => $scheme?->getKey(),
            'scheme_name' => $scheme?->name,
            'scheme_rules' => $scheme?->eligibility_rules,
            'submitted_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get the open/closed status for research and pkm based on admin settings.
     */
    public function getScheduleStatus(?User $user = null): array
    {
        $resStart = Setting::where('key', 'research_proposal_start_date')->value('value');
        $resEnd = Setting::where('key', 'research_proposal_end_date')->value('value');
        $pkmStart = Setting::where('key', 'community_service_proposal_start_date')->value('value');
        $pkmEnd = Setting::where('key', 'community_service_proposal_end_date')->value('value');

        $researchSchemes = ResearchScheme::all();
        $pkmSchemes = CommunityServiceScheme::all();

        if ($user) {
            $eligibilityAction = app(IdentityEligibilityAction::class);

            $researchSchemes = $researchSchemes->filter(function ($scheme) use ($user, $eligibilityAction) {
                $result = $eligibilityAction->execute($user, $scheme);

                return $result['is_eligible'];
            });

            $pkmSchemes = $pkmSchemes->filter(function ($scheme) use ($user, $eligibilityAction) {
                $result = $eligibilityAction->execute($user, $scheme);

                return $result['is_eligible'];
            });
        }

        return [
            'research_open' => static::isWithinSchedule($resStart, $resEnd),
            'research_dates' => ['start' => $resStart, 'end' => $resEnd],
            'research_schemes' => $researchSchemes->pluck('name')->toArray(),
            'pkm_open' => static::isWithinSchedule($pkmStart, $pkmEnd),
            'pkm_dates' => ['start' => $pkmStart, 'end' => $pkmEnd],
            'pkm_schemes' => $pkmSchemes->pluck('name')->toArray(),
        ];
    }

    /**
     * Check if revision period is open.
     */
    public function isRevisionOpen(string $type): bool
    {
        $startKey = $type === 'research' ? 'research_revision_start_date' : 'community_service_revision_start_date';
        $endKey = $type === 'research' ? 'research_revision_end_date' : 'community_service_revision_end_date';

        $start = Setting::where('key', $startKey)->value('value');
        $end = Setting::where('key', $endKey)->value('value');

        return static::isWithinSchedule($start, $end);
    }

    /**
     * Check if final report period is open.
     */
    public function isFinalReportOpen(string $type): bool
    {
        $startKey = $type === 'research' ? 'research_final_report_start_date' : 'community_service_final_report_start_date';
        $endKey = $type === 'research' ? 'research_final_report_end_date' : 'community_service_final_report_end_date';

        $start = Setting::where('key', $startKey)->value('value');
        $end = Setting::where('key', $endKey)->value('value');

        return static::isWithinSchedule($start, $end);
    }
}
