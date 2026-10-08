<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * BudgetCap model represents year-based budget limits for proposals.
 *
 * Each year can have different budget caps for research and community service.
 * Used to enforce maximum budget constraints when creating/editing proposals.
 *
 * @property int $id
 * @property int $year
 * @property float $research_budget_cap
 * @property float $community_service_budget_cap
 */
class BudgetCap extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'semester',
        'research_budget_cap',
        'community_service_budget_cap',
        'scheme_caps',
        'enforce_percentage',
        'amendment_tolerance_percent',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'semester' => 'string',
            'research_budget_cap' => 'decimal:2',
            'community_service_budget_cap' => 'decimal:2',
            'scheme_caps' => 'array',
            'enforce_percentage' => 'boolean',
            'amendment_tolerance_percent' => 'decimal:2',
        ];
    }

    /**
     * Get the budget cap for a specific year, semester, type and selectively by scheme.
     *
     * @param  string  $type  'research' or 'community_service'
     * @param  string|null  $semester  'ganjil' or 'genap'
     * @param  int|null  $schemeId  Optional scheme ID to check for specific caps
     */
    public static function getCapForPeriod(int $year, ?string $semester = null, string $type = 'research', ?int $schemeId = null): ?float
    {
        $query = self::where('year', $year);

        // Defensive check: only filter by semester if the column exists in the database
        // This prevents 500 errors during the transition period when migrations are pending
        if ($semester && Schema::hasColumn('budget_caps', 'semester')) {
            $query->where('semester', $semester);
        }

        $budgetCap = $query->first();

        if (! $budgetCap) {
            return null;
        }

        // 1. Check for specific scheme cap first natively
        if ($schemeId && is_array($budgetCap->scheme_caps) && isset($budgetCap->scheme_caps[$type]) && isset($budgetCap->scheme_caps[$type][$schemeId])) {
            return (float) $budgetCap->scheme_caps[$type][$schemeId];
        }

        // 2. Fallback to global generic cap
        return $type === 'research'
            ? ($budgetCap->research_budget_cap !== null ? (float) $budgetCap->research_budget_cap : null)
            : ($budgetCap->community_service_budget_cap !== null ? (float) $budgetCap->community_service_budget_cap : null);
    }
}
