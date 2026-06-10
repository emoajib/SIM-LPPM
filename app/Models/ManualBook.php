<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ManualBook extends Model implements HasMedia
{
    use HasUuids, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'version_number',
        'status',
        'assigned_roles',
        'created_by',
    ];

    protected $casts = [
        'assigned_roles' => 'array',
        'id' => 'string',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('manual_book_file')
            ->singleFile()
            ->acceptsMimeTypes(['application/pdf']);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeVisibleForRole($query, string $role)
    {
        return $query->where('status', 'active')
            ->whereJsonContains('assigned_roles', $role);
    }
}
