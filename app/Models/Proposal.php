<?php

namespace App\Models;

use App\Enums\KaprodiStatus;
use App\Enums\ProposalStatus;
use App\Enums\ProposalUserStatus;
use App\Enums\ReviewStatus;
use Database\Factories\ProposalFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $title
 * @property string $submitter_id
 * @property string|null $detailable_id
 * @property string|null $detailable_type
 * @property string|null $research_scheme_id
 * @property string|null $community_service_scheme_id
 * @property string|null $focus_area_id
 * @property string|null $theme_id
 * @property string|null $topic_id
 * @property string|null $national_priority_id
 * @property string|null $cluster_level1_id
 * @property string|null $cluster_level2_id
 * @property string|null $cluster_level3_id
 * @property float|null $sbk_value
 * @property int|null $duration_in_years
 * @property int|null $start_year
 * @property string|null $summary
 * @property string|null $asta_cita
 * @property ProposalStatus $status
 * @property array|null $student_members
 * @property-read User $submitter
 * @property-read Model|Research|CommunityService $detailable
 * @property-read ResearchScheme|null $researchScheme
 * @property-read CommunityServiceScheme|null $communityServiceScheme
 * @property-read FocusArea|null $focusArea
 * @property-read Theme|null $theme
 * @property-read Topic|null $topic
 * @property-read NationalPriority|null $nationalPriority
 * @property-read ScienceCluster|null $clusterLevel1
 * @property-read ScienceCluster|null $clusterLevel2
 * @property-read ScienceCluster|null $clusterLevel3
 * @property-read Collection|Sdg[] $sdgs
 * @property-read Collection|MasterIku[] $targetedIkus
 * @property-read Collection|ProposalMonev[] $monevs
 * @property-read Collection|User[] $teamMembers
 * @property-read Collection|Keyword[] $keywords
 * @property-read Collection|ProposalOutput[] $outputs
 * @property-read Collection|BudgetItem[] $budgetItems
 * @property-read Collection|Partner[] $partners
 * @property-read Collection|ActivitySchedule[] $activitySchedules
 * @property-read Collection|ResearchStage[] $researchStages
 * @property-read Collection|ProposalReviewer[] $reviewers
 * @property-read Collection|ProgressReport[] $progressReports
 * @property-read Collection|DailyNote[] $dailyNotes
 * @property-read Collection|ProposalStatusLog[] $statusLogs
 * @property-read Collection|ReviewLog[] $reviewLogs
 * @property-read Collection|ProposalActivity[] $activities
 * @property-read Collection|DocumentSignature[] $signatures
 * @property-read User $user
 */
class Proposal extends Model
{
    /** @use HasFactory<ProposalFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    public ?string $notes = null;

    /**
     * The type of the auto-incrementing ID's primary key.
     */
    protected $keyType = 'string';

    /**
     * Indicates if the ID is auto-incrementing.
     */
    public $incrementing = false;

    protected $fillable = [
        'title',
        'submitter_id',
        'detailable_id',
        'detailable_type',
        'research_scheme_id',
        'community_service_scheme_id',
        'focus_area_id',
        'theme_id',
        'topic_id',
        'national_priority_id',
        'cluster_level1_id',
        'cluster_level2_id',
        'cluster_level3_id',
        'sbk_value',
        'duration_in_years',
        'start_year',
        'semester',
        'summary',
        'asta_cita',
        'status',
        'logbook_signed_at',
        'logbook_approved_at',
        'student_members',
        'study_program_roadmap_id',
        'bima_proposal_id',
        'is_roadmap_validated_by_kaprodi',
        'kaprodi_validation_notes',
        'kaprodi_validated_at',
        'kaprodi_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sbk_value' => 'decimal:2',
            'duration_in_years' => 'integer',
            'start_year' => 'integer',
            'status' => ProposalStatus::class,
            'logbook_signed_at' => 'datetime',
            'logbook_approved_at' => 'datetime',
            'student_members' => 'array',
            'qualification_snapshot' => 'array',
            'is_roadmap_validated_by_kaprodi' => 'boolean',
            'kaprodi_validated_at' => 'datetime',
        ];
    }

    /**
     * Get the SDGs for the proposal.
     *
     * @return BelongsToMany<Sdg, $this>
     */
    public function sdgs(): BelongsToMany
    {
        return $this->belongsToMany(Sdg::class, 'proposal_sdg');
    }

    /**
     * Get the targeted IKUs for the proposal.
     *
     * @return BelongsToMany<MasterIku, $this>
     */
    public function targetedIkus(): BelongsToMany
    {
        return $this->belongsToMany(MasterIku::class, 'proposal_target_iku');
    }

    /**
     * Get all monev sessions for the proposal.
     *
     * @return HasMany<ProposalMonev, $this>
     */
    public function monevs(): HasMany
    {
        return $this->hasMany(ProposalMonev::class)->orderBy('monev_date', 'desc');
    }

    /**
     * Get the user who submitted the proposal.
     *
     * @return BelongsTo<User, $this>
     */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitter_id');
    }

    /**
     * Alias for submitter relationship.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->submitter();
    }

    /**
     * Get the detailable model (Research or CommunityService).
     *
     * @return MorphTo<Model, $this>
     */
    public function detailable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the research scheme for the proposal.
     *
     * @return BelongsTo<ResearchScheme, $this>
     */
    public function researchScheme(): BelongsTo
    {
        return $this->belongsTo(ResearchScheme::class);
    }

    /**
     * Get the community service scheme for the proposal.
     *
     * @return BelongsTo<CommunityServiceScheme, $this>
     */
    public function communityServiceScheme(): BelongsTo
    {
        return $this->belongsTo(CommunityServiceScheme::class);
    }

    /**
     * Get the focus area for the proposal.
     *
     * @return BelongsTo<FocusArea, $this>
     */
    public function focusArea(): BelongsTo
    {
        return $this->belongsTo(FocusArea::class);
    }

    /**
     * Get the theme for the proposal.
     *
     * @return BelongsTo<Theme, $this>
     */
    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    /**
     * Get the topic for the proposal.
     *
     * @return BelongsTo<Topic, $this>
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * Get the national priority for the proposal.
     *
     * @return BelongsTo<NationalPriority, $this>
     */
    public function nationalPriority(): BelongsTo
    {
        return $this->belongsTo(NationalPriority::class);
    }

    /**
     * Get the level 1 science cluster for the proposal.
     *
     * @return BelongsTo<ScienceCluster, $this>
     */
    public function clusterLevel1(): BelongsTo
    {
        return $this->belongsTo(ScienceCluster::class, 'cluster_level1_id');
    }

    /**
     * Get the level 2 science cluster for the proposal.
     *
     * @return BelongsTo<ScienceCluster, $this>
     */
    public function clusterLevel2(): BelongsTo
    {
        return $this->belongsTo(ScienceCluster::class, 'cluster_level2_id');
    }

    /**
     * Get the level 3 science cluster for the proposal.
     *
     * @return BelongsTo<ScienceCluster, $this>
     */
    public function clusterLevel3(): BelongsTo
    {
        return $this->belongsTo(ScienceCluster::class, 'cluster_level3_id');
    }

    /**
     * Get the team members for the proposal.
     *
     * @return BelongsToMany<User, $this>
     */
    public function teamMembers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'proposal_user')
            ->orderByPivot('created_at', 'desc')
            ->withPivot('role', 'tasks', 'status')
            ->withTimestamps();
    }

    /**
     * Get all keywords for the proposal.
     *
     * @return BelongsToMany<Keyword, $this>
     */
    public function keywords(): BelongsToMany
    {
        return $this->belongsToMany(Keyword::class, 'proposal_keyword')
            ->withTimestamps();
    }

    /**
     * Get all outputs for the proposal.
     *
     * @return HasMany<ProposalOutput, $this>
     */
    public function outputs(): HasMany
    {
        return $this->hasMany(ProposalOutput::class);
    }

    /**
     * Get all budget items for the proposal.
     *
     * @return HasMany<BudgetItem, $this>
     */
    public function budgetItems(): HasMany
    {
        return $this->hasMany(BudgetItem::class);
    }

    /**
     * Get all partners for the proposal.
     *
     * @return BelongsToMany<Partner, $this>
     */
    public function partners(): BelongsToMany
    {
        return $this->belongsToMany(Partner::class, 'proposal_partner')
            ->withTimestamps();
    }

    /**
     * Get all activity schedules for the proposal.
     *
     * @return HasMany<ActivitySchedule, $this>
     */
    public function activitySchedules(): HasMany
    {
        return $this->hasMany(ActivitySchedule::class);
    }

    /**
     * Get all research stages for the proposal.
     *
     * @return HasMany<ResearchStage, $this>
     */
    public function researchStages(): HasMany
    {
        return $this->hasMany(ResearchStage::class);
    }

    /**
     * Get all reviewers for the proposal.
     *
     * @return HasMany<ProposalReviewer, $this>
     */
    public function reviewers(): HasMany
    {
        return $this->hasMany(ProposalReviewer::class)->orderBy('round', 'desc')->orderBy('assigned_at', 'desc');
    }

    /**
     * Get all progress reports for the proposal.
     *
     * @return HasMany<ProgressReport, $this>
     */
    public function progressReports(): HasMany
    {
        return $this->hasMany(ProgressReport::class);
    }

    /**
     * Get all daily notes for the proposal.
     *
     * @return HasMany<DailyNote, $this>
     */
    public function dailyNotes(): HasMany
    {
        return $this->hasMany(DailyNote::class);
    }

    /**
     * Get all status change logs for the proposal.
     *
     * @return HasMany<ProposalStatusLog, $this>
     */
    public function statusLogs(): HasMany
    {
        return $this->hasMany(ProposalStatusLog::class)->orderBy('at', 'desc');
    }

    /**
     * Get all review logs for the proposal.
     *
     * @return HasMany<ReviewLog, $this>
     */
    public function reviewLogs(): HasMany
    {
        return $this->hasMany(ReviewLog::class)->orderBy('round', 'desc')->orderBy('completed_at', 'desc');
    }

    /**
     * Get all activities for the proposal.
     *
     * @return HasMany<ProposalActivity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(ProposalActivity::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get all digital signatures for the proposal.
     *
     * @return MorphMany<DocumentSignature, $this>
     */
    public function signatures(): MorphMany
    {
        return $this->morphMany(DocumentSignature::class, 'document', 'document_type', 'document_id');
    }

    public function kaprodiApproval(): HasMany
    {
        return $this->hasMany(KaprodiApproval::class)->orderBy('created_at', 'desc');
    }

    public function latestKaprodiApproval(): HasOne
    {
        // Vetted by AI - Manual Review Required by Senior Engineer/Manager
        // latestOfMany() includes MAX(id) as a tie-breaker subquery, which fails
        // on UUID PKs in PostgreSQL (function max(uuid) does not exist).
        // The cleanest driver-agnostic fix that supports eager loading without N+1
        // is to use a correlated subquery on the `id` column.
        return $this->hasOne(KaprodiApproval::class)
            ->whereRaw('proposal_kaprodi_approvals.id = (
                SELECT id 
                FROM proposal_kaprodi_approvals AS sub 
                WHERE sub.proposal_id = proposal_kaprodi_approvals.proposal_id 
                ORDER BY sub.created_at DESC 
                LIMIT 1
            )');
    }

    public function hasApprovedKaprodi(): bool
    {
        return $this->kaprodiApproval()
            ->where('status', KaprodiStatus::APPROVED)
            ->exists();
    }

    public function hasPendingKaprodiApproval(): bool
    {
        return $this->kaprodiApproval()
            ->where('status', KaprodiStatus::PENDING)
            ->exists();
    }

    public function kaprodiApprovalStatus(): ?KaprodiStatus
    {
        $latest = $this->latestKaprodiApproval;

        if (! $latest instanceof KaprodiApproval) {
            return null;
        }

        /** @var KaprodiStatus $status */
        $status = $latest->status;

        return $status;
    }

    /**
     * Check if all team members have accepted the invitation.
     */
    public function allTeamMembersAccepted(): bool
    {
        $totalMembers = $this->teamMembers()->count();
        if ($totalMembers === 0) {
            return true;
        }

        $acceptedMembers = $this->teamMembers()
            ->wherePivot('status', ProposalUserStatus::ACCEPTED->value)
            ->count();

        return $totalMembers === $acceptedMembers;
    }

    /**
     * Check if all reviewers have completed their reviews.
     * Vetted by AI - Manual Review Required by Senior Engineer/Manager
     */
    public function allReviewsCompleted(): bool
    {
        $requiredCount = (int) Setting::get('reviewer_count_required', 1);
        $total = $this->reviewers()->count();
        $completed = $this->reviewers()->where('status', ReviewStatus::COMPLETED)->count();

        return $total >= $requiredCount && $completed === $total;
    }

    /**
     * Get pending team member invitations.
     *
     * @return BelongsToMany<User, $this>
     */
    public function pendingTeamMembers(): BelongsToMany
    {
        return $this->teamMembers()
            ->wherePivot('status', ProposalUserStatus::PENDING->value);
    }

    /**
     * Get pending reviewer assignments.
     *
     * @return HasMany<ProposalReviewer, $this>
     */
    public function pendingReviewers(): HasMany
    {
        return $this->reviewers()
            ->whereIn('status', [
                ReviewStatus::PENDING->value,
                ReviewStatus::RE_REVIEW_REQUESTED->value,
            ]);
    }

    /**
     * Get all pending team members (anggota who haven't accepted).
     *
     * @return Collection<int, User>
     */
    public function getPendingTeamMembers(): Collection
    {
        return $this->teamMembers()
            ->wherePivot('status', '!=', ProposalUserStatus::ACCEPTED->value)
            ->get();
    }

    /**
     * Get all pending reviewers (who haven't completed their review).
     *
     * @return Collection<int, ProposalReviewer>
     */
    public function getPendingReviewers(): Collection
    {
        return $this->reviewers()
            ->where('status', '!=', ReviewStatus::COMPLETED->value)
            ->get();
    }

    /**
     * Check if proposal can be approved (all reviewers completed).
     */
    public function canBeApproved(): bool
    {
        return $this->allReviewsCompleted();
    }

    /**
     * Get all monev reviews (post-completion audits) for the proposal.
     *
     * @return HasMany<MonevReview, $this>
     */
    public function monevReviews(): HasMany
    {
        return $this->hasMany(MonevReview::class);
    }

    /**
     * Scope for academic year.
     *
     * @return Builder<Proposal>
     */
    /**
     * @param  Builder<Proposal>  $query
     * @return Builder<Proposal>
     */
    public function scopeForSemester($query, string $semester): Builder
    {
        return $query->where('semester', $semester);
    }

    /**
     * Get the study program roadmap this proposal aligns with.
     *
     * @return BelongsTo<StudyProgramRoadmap, $this>
     */
    public function studyProgramRoadmap(): BelongsTo
    {
        return $this->belongsTo(StudyProgramRoadmap::class);
    }

    /**
     * Check if proposal is validated by kaprodi.
     */
    public function isValidatedByKaprodi(): bool
    {
        return (bool) $this->is_roadmap_validated_by_kaprodi;
    }

    /**
     * Get the study program of the proposal's submitter.
     */
    public function getSubmitterStudyProgram(): ?StudyProgram
    {
        return $this->submitter->identity?->studyProgram;
    }

    /**
     * Calculate alignment score between proposal and study program roadmap.
     * Returns a value between 0 and 100.
     */
    public function getRoadmapAlignmentScore(): int
    {
        $studyProgram = $this->getSubmitterStudyProgram();

        if (! $studyProgram || ! $studyProgram->research_roadmap) {
            return 0;
        }

        $roadmap = $studyProgram->research_roadmap;
        $score = 0;
        $totalChecks = 0;

        $researchTree = $roadmap['research_tree'] ?? null;
        if ($researchTree) {
            $treeArray = is_array($researchTree) ? $researchTree : array_map('trim', explode(',', $researchTree));
            if ($treeArray !== []) {
                $totalChecks++;
                $researchTree = array_map('strtolower', $treeArray);

                $proposalTitle = strtolower($this->title);
                $proposalSummary = $this->summary !== null ? strtolower($this->summary) : '';
                $proposalCategory = $this->focusArea->name ?? '';
                $proposalTheme = $this->theme->name ?? '';
                $proposalTopic = $this->topic->name ?? '';

                foreach ($researchTree as $tree) {
                    if (str_contains($proposalTitle, $tree)
                        || str_contains($proposalSummary, $tree)
                        || str_contains(strtolower($proposalCategory), $tree)
                        || str_contains(strtolower($proposalTheme), $tree)
                        || str_contains(strtolower($proposalTopic), $tree)) {
                        $score += 100;
                        break;
                    }
                }
            }
        }

        $priorities = $roadmap['priorities'] ?? null;
        if (is_array($priorities) && $priorities !== []) {
            $totalChecks++;
            $currentYear = now()->year;

            foreach ($priorities as $priority) {
                $priorityYear = $priority['year'] ?? 0;
                if ($priorityYear == $currentYear) {
                    $themes = strtolower($priority['themes'] ?? '');
                    $proposalTitle = strtolower($this->title);
                    $proposalSummary = $this->summary !== null ? strtolower($this->summary) : '';

                    $themeKeywords = array_map('trim', explode(',', $themes));
                    foreach ($themeKeywords as $keyword) {
                        if (str_contains($proposalTitle, $keyword)
                            || str_contains($proposalSummary, $keyword)) {
                            $score += 100;
                            break;
                        }
                    }
                    break;
                }
            }
        }

        if ($totalChecks === 0) {
            return 50;
        }

        return (int) round($score / $totalChecks);
    }

    /**
     * Get alignment level label.
     */
    public function getRoadmapAlignmentLevel(): string
    {
        $score = $this->getRoadmapAlignmentScore();

        return match (true) {
            $score >= 80 => 'Sangat Sesuai',
            $score >= 50 => 'Sesuai',
            $score >= 20 => 'Kurang Sesuai',
            default => 'Tidak Sesuai',
        };
    }

    /**
     * Get alignment level color for UI badges.
     */
    public function getRoadmapAlignmentColor(): string
    {
        $score = $this->getRoadmapAlignmentScore();

        return match (true) {
            $score >= 80 => 'success',
            $score >= 50 => 'primary',
            $score >= 20 => 'warning',
            default => 'danger',
        };
    }

    /**
     * Get the dynamic average score of completed reviewer logs.
     * Vetted by AI - Manual Review Required by Senior Engineer/Manager
     */
    public function getScoreAttribute(): ?float
    {
        $completedLogs = $this->reviewers()
            ->where('status', ReviewStatus::COMPLETED->value)
            ->get()
            ->map(fn ($r) => $r->latestLog()?->total_score)
            ->filter(fn ($score) => ! is_null($score));

        if ($completedLogs->isEmpty()) {
            return null;
        }

        return round($completedLogs->average(), 1);
    }
}
