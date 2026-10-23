<?php

namespace App\Models;

use App\Support\AcademicPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property string $id
 * @property string $proposal_id
 * @property string $reviewer_id
 * @property float $score
 * @property string|null $status
 * @property string|null $notes
 * @property array|null $borang_data
 * @property string $academic_year
 * @property string $semester
 * @property Carbon|null $reviewed_at
 * @property Carbon|null $finalized_by_lppm_at
 * @property string|null $finalized_by_lppm_by
 * @property Carbon|null $approved_by_kepala_at
 * @property string|null $approved_by_kepala_by
 * @property Carbon|null $reported_to_rektor_at
 * @property-read Proposal $proposal
 * @property-read User $reviewer
 * @property-read Collection|DocumentSignature[] $signatures
 */
class MonevReview extends Model implements HasMedia
{
    use HasFactory, HasUuids, InteractsWithMedia;

    protected $fillable = [
        'proposal_id',
        'reviewer_id',
        'score',
        'status',
        'notes',
        'borang_data',
        'academic_year',
        'semester',
        'reviewed_at',
        'finalized_by_lppm_at',
        'finalized_by_lppm_by',
        'approved_by_kepala_at',
        'approved_by_kepala_by',
        'reported_to_rektor_at',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'score' => 'float',
            'borang_data' => 'array',
            'reviewed_at' => 'datetime',
            'finalized_by_lppm_at' => 'datetime',
            'approved_by_kepala_at' => 'datetime',
            'reported_to_rektor_at' => 'datetime',
        ];
    }

    /**
     * Get the proposal being reviewed.
     */
    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    /**
     * Filter by academic period (stored as the semester's start year).
     *
     * @param  Builder<MonevReview>  $query
     * @return Builder<MonevReview>
     */
    public function scopeForAcademicPeriod(Builder $query, ?string $academicYear, ?string $semester = null): Builder
    {
        $start = AcademicPeriod::startYear($academicYear);

        if ($start !== null) {
            $query->whereIn('academic_year', [$start, $start + 1]);
        }

        if (in_array($semester, [AcademicPeriod::GANJIL, AcademicPeriod::GENAP], true)) {
            $query->where('semester', $semester);
        }

        return $query;
    }

    /**
     * Get the reviewer user.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * Get all digital signatures for the monev review.
     */
    public function signatures(): MorphMany
    {
        return $this->morphMany(DocumentSignature::class, 'document', 'document_type', 'document_id');
    }
}
