<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Peristiwa pengembangan permanen (commit, CI, deploy, error, bug, decision).
 *
 * @property string $id
 * @property string $type
 * @property string|null $area
 * @property string $title
 * @property string|null $reference
 * @property string|null $bc_code
 * @property string|null $status
 * @property array<string, mixed>|null $payload
 * @property Carbon|null $occurred_at
 */
class DevEvent extends Model
{
    use HasUuids;

    public const TYPE_COMMIT = 'commit';

    public const TYPE_CI = 'ci';

    public const TYPE_DEPLOY = 'deploy';

    public const TYPE_ERROR = 'error';

    public const TYPE_BUG = 'bug';

    public const TYPE_DECISION = 'decision';

    protected $fillable = [
        'type',
        'area',
        'title',
        'reference',
        'bc_code',
        'status',
        'payload',
        'occurred_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'occurred_at' => 'datetime',
    ];

    /**
     * @param  Builder<DevEvent>  $query
     * @return Builder<DevEvent>
     */
    public function scopeForArea(Builder $query, string $area): Builder
    {
        return $query->where('area', $area);
    }

    /**
     * @param  Builder<DevEvent>  $query
     * @return Builder<DevEvent>
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }
}
