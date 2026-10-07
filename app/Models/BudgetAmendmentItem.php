<?php

namespace App\Models;

use Database\Factories\BudgetAmendmentItemFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $budget_amendment_id
 * @property int|null $budget_group_id
 * @property int|null $budget_component_id
 * @property int|null $year
 * @property string|null $group
 * @property string|null $component
 * @property string|null $item_description
 * @property float|null $volume
 * @property float|null $unit_price
 * @property float|null $total_price
 * @property-read BudgetAmendment $amendment
 * @property-read BudgetGroup|null $budgetGroup
 * @property-read BudgetComponent|null $budgetComponent
 */
class BudgetAmendmentItem extends Model
{
    /** @use HasFactory<BudgetAmendmentItemFactory> */
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
