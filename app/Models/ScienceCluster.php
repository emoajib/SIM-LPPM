<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $parent_id
 * @property int $level
 * @property string $name
 * @property bool $is_active_for_research
 * @property bool $is_active_for_community_service
 * @property-read ScienceCluster|null $parent
 * @property-read Collection|ScienceCluster[] $children
 */
class ScienceCluster extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'level',
        'name',
        'is_active_for_research',
        'is_active_for_community_service',
    ];

    protected $casts = [
        'is_active_for_research' => 'boolean',
        'is_active_for_community_service' => 'boolean',
    ];

    /**
     * Get the parent science cluster.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ScienceCluster::class, 'parent_id');
    }

    /**
     * Get all child science clusters.
     */
    public function children(): HasMany
    {
        return $this->hasMany(ScienceCluster::class, 'parent_id');
    }

    /**
     * Get all identities (dosen) with this rumpun ilmu.
     */
    public function identities(): HasMany
    {
        return $this->hasMany(Identity::class);
    }

    /**
     * Get all proposals using this as level 1 cluster.
     */
    public function proposalsLevel1(): HasMany
    {
        return $this->hasMany(Proposal::class, 'cluster_level1_id');
    }

    /**
     * Get all proposals using this as level 2 cluster.
     */
    public function proposalsLevel2(): HasMany
    {
        return $this->hasMany(Proposal::class, 'cluster_level2_id');
    }

    /**
     * Get all proposals using this as level 3 cluster.
     */
    public function proposalsLevel3(): HasMany
    {
        return $this->hasMany(Proposal::class, 'cluster_level3_id');
    }
}
