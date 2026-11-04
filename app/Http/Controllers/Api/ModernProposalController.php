<?php

namespace App\Http\Controllers\Api;

use App\Enums\ProposalStatus;
use App\Models\CommunityService;
use App\Models\CommunityServiceScheme;
use App\Models\Faculty;
use App\Models\Proposal;
use App\Models\Research;
use App\Models\ResearchScheme;
use App\Models\Setting;
use App\Models\StudyProgram;
use App\Services\EligibilityService;
use App\Services\LecturerEligibilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ModernProposalController
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $search = $request->get('search', '');
        $status = $request->get('status', 'all');
        $year = $request->get('year', '');
        $type = $request->get('type', 'research');
        $roleFilter = $request->get('role', 'all');
        $facultyId = $request->get('faculty_id');
        $studyProgramId = $request->get('study_program_id');
        $schemeId = $request->get('scheme_id');

        $query = Proposal::with(['submitter.identity', 'focusArea', 'researchScheme', 'communityServiceScheme'])
            ->where('detailable_type', $type === 'research' ? 'App\\Models\\Research' : CommunityService::class);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('submitter', fn ($s) => $s->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('submitter.identity', fn ($i) => $i->where('identity_id', 'like', "%{$search}%"));
            });
        }
        if ($facultyId) {
            $query->whereHas('submitter.identity', fn ($si) => $si->where('faculty_id', $facultyId));
        }
        if ($studyProgramId) {
            $query->whereHas('submitter.identity', fn ($si) => $si->where('study_program_id', $studyProgramId));
        }
        if ($schemeId) {
            $query->where($type === 'research' ? 'research_scheme_id' : 'community_service_scheme_id', $schemeId);
        }
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        if ($year) {
            $query->whereYear('created_at', $year);
        }

        if ($user->activeHasAnyRole(['admin lppm', 'kepala lppm', 'rektor'])) {
            // global access
        } elseif ($user->activeHasRole('dekan')) {
            if ($user->identity?->faculty_id) {
                $query->whereHas('submitter.identity', fn ($si) => $si->where('faculty_id', $user->identity->faculty_id));
            } else {
                $query->whereRaw('1=0');
            }
        } else {
            $isReviewerOnly = $user->activeHasRole('reviewer') && ! $user->activeHasAnyRole(['dosen', 'kaprodi', 'dekan']);
            $query->where(function ($q) use ($user, $roleFilter, $isReviewerOnly) {
                if ($isReviewerOnly) {
                    $q->whereHas('reviewers', fn ($r) => $r->where('user_id', $user->id));

                    return;
                }
                if ($roleFilter === 'ketua') {
                    $q->where('submitter_id', $user->id);
                } elseif ($roleFilter === 'anggota') {
                    // Ketua juga tersimpan di pivot proposal_user (role = 'ketua'),
                    // jadi WAJIB filter role agar usulan sendiri tidak ikut tampil.
                    $q->whereHas('teamMembers', fn ($t) => $t->where('user_id', $user->id)
                        ->where('proposal_user.role', 'anggota'));
                } else {
                    $q->where('submitter_id', $user->id)
                        ->orWhereHas('teamMembers', fn ($t) => $t->where('user_id', $user->id))
                        ->orWhere(function ($q2) use ($user) {
                            if ($user->activeHasRole('kaprodi') && $user->identity?->study_program_id) {
                                $q2->whereHas('submitter.identity', fn ($si) => $si->where('study_program_id', $user->identity->study_program_id));
                            }
                        })
                        ->orWhereHas('reviewers', fn ($r) => $r->where('user_id', $user->id));
                }
            });
        }

        $proposals = $query->latest()->paginate(15);

        return response()->json([
            'data' => $proposals->map(fn ($p) => [
                'id' => $p->id,
                'title' => $p->title,
                'focus_area' => $p->focusArea?->name,
                'submitter' => $p->submitter->name,
                'identity_id' => $p->submitter->identity?->identity_id,
                'status' => $p->status->value,
                'status_label' => $p->status->label(),
                'status_color' => $p->status->color(),
                'created_at' => $p->created_at?->format('d M Y'),
                'show_url' => $p->detailable_type === CommunityService::class
                    ? route('community-service.proposal.show', $p)
                    : route('research.proposal.show', $p),
            ]),
            'meta' => [
                'current_page' => $proposals->currentPage(),
                'last_page' => $proposals->lastPage(),
                'total' => $proposals->total(),
            ],
        ]);
    }

    public function availableFilters(Request $request)
    {
        $user = Auth::user();
        $type = $request->get('type', 'research');

        $years = Proposal::where('detailable_type', $type === 'research' ? Research::class : CommunityService::class)
            ->pluck('created_at')
            ->map(fn ($date) => $date?->year)
            ->filter()
            ->unique()
            ->sortDesc()
            ->values();

        $schemes = $type === 'research'
            ? ResearchScheme::select('id', 'name')->get()
            : CommunityServiceScheme::select('id', 'name')->get();

        $statuses = collect(ProposalStatus::cases())->map(fn ($s) => [
            'value' => $s->value,
            'label' => $s->label(),
        ]);

        $faculties = [];
        $studyPrograms = [];
        if ($user->activeHasAnyRole(['admin lppm', 'kepala lppm', 'rektor', 'dekan'])) {
            $faculties = Faculty::select('id', 'name')->orderBy('name')->get();
            if ($user->activeHasRole('dekan') && $user->identity?->faculty_id) {
                $studyPrograms = StudyProgram::where('faculty_id', $user->identity->faculty_id)->select('id', 'name')->orderBy('name')->get();
            } else {
                $studyPrograms = StudyProgram::select('id', 'name', 'faculty_id')->orderBy('name')->get();
            }
        } elseif ($user->activeHasRole('kaprodi') && $user->identity?->study_program_id) {
            $studyPrograms = StudyProgram::where('id', $user->identity->study_program_id)->select('id', 'name')->get();
        }

        return response()->json([
            'years' => $years,
            'schemes' => $schemes,
            'statuses' => $statuses,
            'faculties' => $faculties,
            'study_programs' => $studyPrograms,
        ]);
    }

    public function eligibility(Request $request)
    {
        $user = Auth::user();
        $type = $request->get('type', 'research');
        $svc = app(LecturerEligibilityService::class);
        $eligibility = ['eligible' => true, 'reasons' => []];
        $scheduleInfo = ['research_open' => false, 'research_schemes' => [], 'pkm_open' => false, 'pkm_schemes' => []];
        if ($user && $user->activeHasRole('dosen')) {
            $eligibility = $svc->checkEligibility($user, $type);
            $scheduleInfo = $svc->getScheduleStatus($user);
        }
        $eligibilityService = app(EligibilityService::class);
        $canCreate = [];
        try {
            $canCreate = $eligibilityService->canCreateProposal($user, $type);
        } catch (\Throwable $e) {
            $canCreate = ['can_create' => false];
        }
        $isWithinSchedule = false;
        $startDate = Setting::where('key', ($type === 'research' ? 'research' : 'community_service').'_proposal_start_date')->value('value');
        $endDate = Setting::where('key', ($type === 'research' ? 'research' : 'community_service').'_proposal_end_date')->value('value');
        $isWithinSchedule = LecturerEligibilityService::isWithinSchedule($startDate, $endDate);

        return response()->json([
            'eligible' => $eligibility['eligible'] ?? true,
            'reasons' => $eligibility['reasons'] ?? [],
            'schedule_open' => $isWithinSchedule,
            'schedule_start' => $startDate,
            'schedule_end' => $endDate,
            'schemes' => $type === 'research' ? ($scheduleInfo['research_schemes'] ?? []) : ($scheduleInfo['pkm_schemes'] ?? []),
            'can_create' => $canCreate['can_create'] ?? false,
            'quota_tooltip' => $canCreate['quota_info'] ?? null,
        ]);
    }
}
