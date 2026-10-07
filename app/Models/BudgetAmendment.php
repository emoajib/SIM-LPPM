<?php

namespace App\Models;

use App\Enums\BudgetAmendmentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $proposal_id
 * @property int $version
 * @property BudgetAmendmentStatus $status
 */
class BudgetAmendment extends Model
{
    /** @use HasFactory<Database\Factories\BudgetAmendmentFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'proposal_id',
        'version',
        'status',
        'reason',
        'decision_notes',
        'requested_by',
        'decided_by',
        'decided_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BudgetAmendmentStatus::class,
            'decided_at' => 'datetime',
        ];
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BudgetAmendmentItem::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
