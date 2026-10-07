<?php

namespace App\Models;

use Database\Factories\BudgetItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $proposal_id
 * @property int $year
 * @property int $budget_group_id
 * @property int $budget_component_id
 * @property string|null $group
 * @property string|null $component
 * @property string|null $item_description
 * @property int $volume
 * @property float $unit_price
 * @property float $total_price
 * @property-read Proposal $proposal
 * @property-read BudgetGroup $budgetGroup
 * @property-read BudgetComponent|null $budgetComponent
 */
class BudgetItem extends Model
{
    /** @use HasFactory<BudgetItemFactory> */
    use HasFactory;

    protected $fillable = [
        'proposal_id',
        'year',
        'budget_group_id',
        'budget_component_id',
        'group',
        'component',
        'item_description',
        'volume',
        'unit_price',
        'total_price',
        'is_active',
        'version',
        'budget_amendment_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the proposal that owns the budget item.
     */
    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    /**
     * Get the budget group that owns the budget item.
     */
    public function budgetGroup(): BelongsTo
    {
        return $this->belongsTo(BudgetGroup::class);
    }

    /**
     * Get the budget component that owns the budget item.
     */
    public function budgetComponent(): BelongsTo
    {
        return $this->belongsTo(BudgetComponent::class);
    }
}
