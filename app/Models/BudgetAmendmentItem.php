<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetAmendmentItem extends Model
{
    /** @use HasFactory<Database\Factories\BudgetAmendmentItemFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'budget_amendment_id',
        'budget_group_id',
        'budget_component_id',
        'year',
        'group',
        'component',
        'item_description',
        'volume',
        'unit_price',
        'total_price',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'volume' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
        ];
    }

    public function amendment(): BelongsTo
    {
        return $this->belongsTo(BudgetAmendment::class, 'budget_amendment_id');
    }

    public function budgetGroup(): BelongsTo
    {
        return $this->belongsTo(BudgetGroup::class);
    }

    public function budgetComponent(): BelongsTo
    {
        return $this->belongsTo(BudgetComponent::class);
    }
}
