<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $strata
 * @property array<string, mixed>|null $eligibility_rules
 */
class ResearchScheme extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'strata',
        'min_tkt',
        'max_tkt',
        'description',
        'eligibility_rules',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'eligibility_rules' => 'array',
        ];
    }

    /**
     * Get all proposals using this research scheme.
     */
    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class);
    }
}
