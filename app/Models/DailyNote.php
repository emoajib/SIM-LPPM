<?php

namespace App\Models;

// Vetted by AI - Manual Review Required by Senior Engineer/Manager

use Database\Factories\DailyNoteFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property string $id
 * @property string $proposal_id
 * @property string|null $budget_group_id
 * @property float|null $amount
 * @property Carbon|null $activity_date
 * @property string|null $activity_description
 * @property int|null $progress_percentage
 * @property string|null $notes
 * @property-read Proposal $proposal
 * @property-read BudgetGroup|null $budgetGroup
 */
class DailyNote extends Model implements HasMedia
{
    /** @use HasFactory<DailyNoteFactory> */
    use HasFactory, HasUuids, InteractsWithMedia;

    protected $fillable = [
        'proposal_id',
        'budget_group_id',
        'amount',
        'activity_date',
        'activity_description',
        'progress_percentage',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activity_date' => 'date',
            'progress_percentage' => 'integer',
            'amount' => 'decimal:2',
        ];
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::saved(function () {
            Cache::forever('dashboard.cache_version', time());
        });

        static::deleted(function () {
            Cache::forever('dashboard.cache_version', time());
        });
    }

    /**
     * Get the proposal that owns the daily note.
     */
    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    /**
     * Get the budget group for the daily note.
     */
    public function budgetGroup(): BelongsTo
    {
        return $this->belongsTo(BudgetGroup::class);
    }

    /**
     * Register media collections for this model.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('evidence');
    }

    /**
     * Register media conversions to generate an optimized image for PDF rendering.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('pdf_image')
            ->nonQueued()
            ->width(800)
            ->quality(80)
            // @phpstan-ignore-next-line method.notFound
            ->performOnCollections('evidence');
    }
}
