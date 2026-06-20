<?php

namespace App\Models;

use App\Enums\ReportStatus;
use Database\Factories\ProgressReportFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property string $id
 * @property string $proposal_id
 * @property string|null $summary_update
 * @property int|null $reporting_year
 * @property string|null $reporting_period
 * @property string|null $status
 * @property string|null $submitted_by
 * @property Carbon|null $submitted_at
 * @property-read Proposal $proposal
 * @property-read User|null $submitter
 * @property-read Collection|Keyword[] $keywords
 * @property-read Collection|MandatoryOutput[] $mandatoryOutputs
 * @property-read Collection|AdditionalOutput[] $additionalOutputs
 */
class ProgressReport extends Model implements HasMedia
{
    /** @use HasFactory<ProgressReportFactory> */
    use HasFactory, HasUuids, InteractsWithMedia, SoftDeletes;

    /**
     * The type of the auto-incrementing ID's primary key.
     */
    protected $keyType = 'string';

    /**
     * Indicates if the ID is auto-incrementing.
     */
    public $incrementing = false;

    protected $fillable = [
        'proposal_id',
        'summary_update',
        'reporting_year',
        'reporting_period',
        'status',
        'submitted_by',
        'submitted_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ReportStatus::class,
            'submitted_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the proposal that owns the progress report.
     */
    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    /**
     * Get the user who submitted the report.
     */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * Get all keywords for the progress report.
     */
    public function keywords(): BelongsToMany
    {
        return $this->belongsToMany(Keyword::class, 'progress_report_keyword')
            ->withTimestamps();
    }

    /**
     * Get all mandatory outputs for the progress report.
     */
    public function mandatoryOutputs(): HasMany
    {
        return $this->hasMany(MandatoryOutput::class);
    }

    /**
     * Get all additional outputs for the progress report.
     */
    public function additionalOutputs(): HasMany
    {
        return $this->hasMany(AdditionalOutput::class);
    }

    /**
     * Get all digital signatures for the report.
     */
    public function signatures(): MorphMany
    {
        return $this->morphMany(DocumentSignature::class, 'document', 'document_type', 'document_id');
    }

    /**
     * Check if this is a final report.
     */
    public function isFinalReport(): bool
    {
        return $this->reporting_period === 'final';
    }

    /**
     * Scope a query to only include final reports.
     *
     * @return Builder<ProgressReport>
     */
    public function scopeFinalReports($query): Builder
    {
        return $query->where('reporting_period', 'final');
    }

    /**
     * Scope a query to only include progress reports (exclude final).
     *
     * @return Builder<ProgressReport>
     */
    public function scopeProgressReports($query): Builder
    {
        return $query->whereIn('reporting_period', ['semester_1', 'semester_2', 'annual']);
    }

    /**
     * Register media collections for this model.
     */
    public function registerMediaCollections(): void
    {
        // Substance file untuk Progress & Final Report
        $this->addMediaCollection('substance_file')
            ->singleFile()
            ->acceptsMimeTypes(['application/pdf']);

        // File realisasi keterlibatan (Final Report only)
        $this->addMediaCollection('realization_file')
            ->singleFile()
            ->acceptsMimeTypes(['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);

        // File presentasi hasil penelitian (Final Report only)
        $this->addMediaCollection('presentation_file')
            ->singleFile()
            ->acceptsMimeTypes(['application/pdf', 'application/vnd.openxmlformats-officedocument.presentationml.presentation']);

        // Halaman pengesahan tanda tangan fisik (Optional Final Report)
        $this->addMediaCollection('signature_page')
            ->singleFile()
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png']);
    }
}
