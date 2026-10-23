<?php

namespace App\Models;

use App\Support\AcademicPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property string $id
 * @property string $proposal_id
 * @property Carbon $monev_date
 * @property int $progress_percentage
 * @property string|null $notes
 * @property-read Proposal $proposal
 *
 * "Efficiency is the goal, but Integrity is the foundation."
 */
class ProposalMonev extends Model implements HasMedia
{
    use HasFactory, HasUuids, InteractsWithMedia;

    protected $fillable = [
        'proposal_id',
        'monev_date',
        'progress_percentage',
        'notes',
        'academic_year',
        'semester',
    ];

    protected function casts(): array
    {
        return [
            'monev_date' => 'date',
        ];
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    /**
     * Filter by academic period (stored as the semester's start year).
     *
     * @param  Builder<ProposalMonev>  $query
     * @return Builder<ProposalMonev>
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

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('berita_acara')
            ->singleFile()
            ->acceptsMimeTypes([
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ]);

        $this->addMediaCollection('borang')
            ->singleFile()
            ->acceptsMimeTypes([
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ]);

        $this->addMediaCollection('rekap_penilaian')
            ->singleFile()
            ->acceptsMimeTypes([
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ]);
    }
}
