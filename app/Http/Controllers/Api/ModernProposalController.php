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
use Carbon\Carbon;
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
        $submodule = $request->get('submodule', '');

        $queryRelations = ['submitter.identity', 'focusArea', 'researchScheme', 'communityServiceScheme'];
        if ($submodule === 'daily-note') {
            $queryRelations[] = 'budgetItems';
            $queryRelations[] = 'dailyNotes';
        }

        $query = Proposal::with($queryRelations)
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

        // Submodule-specific filtering (Perbaikan Usulan, Laporan Akhir, Realisasi Anggaran)
        if ($submodule === 'revision') {
            $query->with(['reviewers' => function ($q) {
                $q->whereIn('status', ['completed', 'pending']);
            }]);
            $query->where(function ($q) {
                $q->whereIn('status', [ProposalStatus::REVISION_NEEDED->value, ProposalStatus::REVISION_SUBMITTED->value])
                    ->orWhereHas('reviewers', function ($rq) {
                        $rq->whereIn('status', ['completed', 'pending']);
                    });
            });
        } elseif ($submodule === 'final-report') {
            $query->where('status', ProposalStatus::COMPLETED->value);
        } elseif ($submodule === 'daily-note') {
            $query->where('status', ProposalStatus::COMPLETED->value)
                ->has('dailyNotes');
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
                'budget_total' => $submodule === 'daily-note'
                    ? (float) $p->budgetItems->where('is_active', true)->sum('total_price')
                    : null,
                'actual_total' => $submodule === 'daily-note'
                    ? (float) $p->dailyNotes->sum('amount')
                    : null,
            ]),
            'meta' => [
                'current_page' => $proposals->currentPage(),
                'last_page' => $proposals->lastPage(),
                'total' => $proposals->total(),
            ],
        ]);
    }

    public function show(Request $request, string $id)
    {
        $user = Auth::user();
        $proposal = Proposal::with([
            'submitter.identity.faculty',
            'submitter.identity.studyProgram',
            'focusArea',
            'researchScheme',
            'communityServiceScheme',
            'theme',
            'topic',
            'clusterLevel1',
            'clusterLevel2',
            'clusterLevel3',
            'budgetItems.budgetGroup',
            'budgetItems.budgetComponent',
            'teamMembers.identity',
            'reviewers.user',
            'reviewers.scores.criteria',
            'statusLogs.user',
            'outputs',
        ])->find($id);

        if (! $proposal) {
            return response()->json(['message' => 'Proposal tidak ditemukan'], 404);
        }

        // Authorization check
        if (! $user->activeHasAnyRole(['admin lppm', 'kepala lppm', 'rektor', 'superadmin'])) {
            if ($proposal->submitter_id !== $user->id && ! $proposal->teamMembers()->where('user_id', $user->id)->exists() && ! $proposal->reviewers()->where('user_id', $user->id)->exists()) {
                if ($user->activeHasRole('dekan')) {
                    if ($user->identity?->faculty_id !== $proposal->submitter->identity?->faculty_id) {
                        return response()->json(['message' => 'Unauthorized'], 403);
                    }
                } elseif ($user->activeHasRole('kaprodi')) {
                    $kaprodiProdiId = StudyProgram::where('kaprodi_user_id', $user->id)->value('id') ?? $user->identity?->study_program_id;
                    if ($kaprodiProdiId !== $proposal->submitter->identity?->study_program_id) {
                        return response()->json(['message' => 'Unauthorized'], 403);
                    }
                } else {
                    return response()->json(['message' => 'Unauthorized'], 403);
                }
            }
        }

        $isResearch = $proposal->detailable_type === 'App\\Models\\Research';

        return response()->json([
            'id' => $proposal->id,
            'title' => $proposal->title,
            'status' => $proposal->status->value,
            'status_label' => $proposal->status->label(),
            'status_color' => $proposal->status->color(),
            'submitter' => [
                'id' => $proposal->submitter->id,
                'name' => $proposal->submitter->name,
                'identity_id' => $proposal->submitter->identity?->identity_id,
                'faculty' => $proposal->submitter->identity?->faculty?->name,
                'study_program' => $proposal->submitter->identity?->studyProgram?->name,
            ],
            'focus_area' => $proposal->focusArea?->name,
            'scheme' => $isResearch ? $proposal->researchScheme?->name : $proposal->communityServiceScheme?->name,
            'theme' => $proposal->theme?->name,
            'topic' => $proposal->topic?->name,
            'cluster_level1' => $proposal->clusterLevel1?->name,
            'cluster_level2' => $proposal->clusterLevel2?->name,
            'cluster_level3' => $proposal->clusterLevel3?->name,
            'duration_in_years' => $proposal->duration_in_years,
            'start_year' => $proposal->start_year,
            'semester' => $proposal->semester,
            'summary' => $proposal->summary,
            'asta_cita' => $proposal->asta_cita,
            'contract_number' => $proposal->contract_number,
            'contract_date' => $proposal->contract_date ? Carbon::parse($proposal->contract_date)->format('Y-m-d') : null,
            'created_at' => $proposal->created_at?->format('d M Y'),
            'updated_at' => $proposal->updated_at?->format('d M Y'),
            'detailable_type' => $proposal->detailable_type,
            'can_edit' => $proposal->submitter_id === $user->id && in_array($proposal->status->value, ['draft', 'revision_needed', 'need_assignment']),
            'can_delete' => $proposal->status->value === 'draft' && $proposal->submitter_id === $user->id,
            'budget_items' => $proposal->budgetItems->map(fn ($bi) => [
                'id' => $bi->id,
                'group' => $bi->group,
                'component' => $bi->component,
                'item_description' => $bi->item_description,
                'volume' => $bi->volume,
                'unit_price' => (float) $bi->unit_price,
                'total_price' => (float) $bi->total_price,
                'is_active' => $bi->is_active,
                'budget_group' => $bi->budgetGroup?->name,
            ]),
            'team_members' => $proposal->teamMembers->map(fn ($tm) => [
                'id' => $tm->id,
                'name' => $tm->name,
                'role' => $tm->pivot->role ?? 'anggota',
                'identity_id' => $tm->identity?->identity_id,
            ]),
            'reviewers' => $proposal->reviewers->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->user->name,
                'status' => $r->status,
                'recommendation' => $r->recommendation,
                'review_notes' => $r->review_notes,
                'completed_at' => $r->completed_at?->format('d M Y H:i'),
                'scores' => $r->scores->map(fn ($s) => [
                    'criteria' => $s->criteria?->criteria,
                    'score' => $s->score,
                    'weight' => (float) $s->weight_snapshot,
                    'value' => (float) $s->value,
                    'acuan' => $s->acuan,
                ]),
            ]),
            'status_logs' => $proposal->statusLogs->map(fn ($sl) => [
                'status_before' => $sl->status_before,
                'status_after' => $sl->status_after,
                'user' => $sl->user?->name,
                'at' => $sl->at?->format('d M Y H:i'),
                'notes' => $sl->notes,
            ]),
            'outputs' => $proposal->outputs->map(fn ($o) => [
                'id' => $o->id,
                'category' => $o->category,
                'type' => $o->type,
                'target_status' => $o->target_status,
            ]),
            'show_url' => $isResearch ? route('research.proposal.show', $proposal) : route('community-service.proposal.show', $proposal),
            'edit_url' => $isResearch ? route('research.proposal.edit', $proposal) : route('community-service.proposal.edit', $proposal),
            'preview_pdf_url' => route('proposals.preview-pdf', $proposal),
            'export_pdf_url' => route('proposals.export-pdf', $proposal),
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
