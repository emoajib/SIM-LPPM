<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $proposal_reviewer_id
 * @property string $proposal_id
 * @property string $user_id
 * @property int $round
 * @property string|null $review_notes
 * @property string|null $recommendation
 * @property int|null $total_score
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property-read ProposalReviewer $proposalReviewer
 * @property-read Proposal $proposal
 * @property-read User $user
 * @property-read Collection|ReviewScore[] $scores
 *
 * @method \Illuminate\Database\Eloquent\Builder|static forProposal(string $proposalId)
 * @method \Illuminate\Database\Eloquent\Builder|static forReviewer(string $userId)
 * @method \Illuminate\Database\Eloquent\Builder|static forRound(int $round)
 * @method \Illuminate\Database\Eloquent\Builder|static completed()
 * @method \Illuminate\Database\Eloquent\Builder|static latestRound()
 */
class ReviewLog extends Model
{
    protected $table = 'review_logs';

    protected $fillable = [
        'proposal_reviewer_id',
        'proposal_id',
        'user_id',
        'round',
        'review_notes',
        'recommendation',
        'total_score',
        'started_at',
        'completed_at',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'round' => 'integer',
            'total_score' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Get the proposal reviewer assignment.
     */
    public function proposalReviewer(): BelongsTo
    {
        return $this->belongsTo(ProposalReviewer::class);
    }

    /**
     * Get the proposal being reviewed.
     */
    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    /**
     * Get the reviewer user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get scores for this log round.
     */
    public function scores(): HasMany
    {
        return $this->hasMany(ReviewScore::class, 'proposal_reviewer_id', 'proposal_reviewer_id')
            ->where('round', $this->round);
    }

    /**
     * Get the recommendation label in Indonesian.
     */
    public function getRecommendationLabelAttribute(): string
    {
        return match ($this->recommendation) {
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'revision_needed' => 'Perlu Revisi',
            default => '-',
        };
    }

    /**
     * Get the recommendation color for badges.
     */
    public function getRecommendationColorAttribute(): string
    {
        return match ($this->recommendation) {
            'approved' => 'success',
            'rejected' => 'danger',
            'revision_needed' => 'warning',
            default => 'secondary',
        };
    }

    /**
     * Scope for specific proposal.
     *
     * @param  Builder<ReviewLog>  $query
     * @return Builder<ReviewLog>
     */
    public function scopeForProposal($query, string $proposalId): Builder
    {
        return $query->where('proposal_id', $proposalId);
    }

    /**
     * Scope for specific reviewer.
     *
     * @param  Builder<ReviewLog>  $query
     * @return Builder<ReviewLog>
     */
    public function scopeForReviewer($query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for specific round.
     *
     * @return Builder<ReviewLog>
     */
    public function scopeForRound($query, int $round): Builder
    {
        return $query->where('round', $round);
    }

    /**
     * Scope for completed reviews.
     *
     * @return Builder<ReviewLog>
     */
    public function scopeCompleted($query): Builder
    {
        return $query->whereNotNull('completed_at');
    }

    /**
     * Scope ordered by round descending (latest first).
     *
     * @return Builder<ReviewLog>
     */
    public function scopeLatestRound($query): Builder
    {
        return $query->orderBy('round', 'desc');
    }
}
