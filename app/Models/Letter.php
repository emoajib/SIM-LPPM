<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Letter extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'letter_number',
        'letter_type_id',
        'user_id',
        'reference_type',
        'reference_id',
        'signature_mode',
        'status',
        'metadata',
        'team_snapshot',
        'file_path',
        'is_stamped',
        'published_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'team_snapshot' => 'array',
        'published_at' => 'datetime',
    ];

    public function letterType(): BelongsTo
    {
        return $this->belongsTo(LetterType::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function logs(): HasMany
    {
        return $this->hasMany(LetterLog::class);
    }
}
