<?php

namespace App\Models;

use App\Enums\LetterStatus;
use App\Enums\SignatureMode;
use App\Enums\TeamSource;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Letter extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public const STATUS_IMMUTABLE = [LetterStatus::PUBLISHED, LetterStatus::READY_TO_PRINT];

    protected $fillable = [
        'letter_number',
        'letter_type_id',
        'user_id',
        'reference_type',
        'reference_id',
        'source',
        'team_source',
        'signature_mode',
        'status',
        'rejection_reason',
        'metadata',
        'team_snapshot',
        'file_path',
        'is_stamped',
        'published_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'team_snapshot' => 'array',
        'is_stamped' => 'boolean',
        'published_at' => 'datetime',
        'team_source' => TeamSource::class,
        'status' => LetterStatus::class,
        'signature_mode' => SignatureMode::class,
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

    public static function statusLabel(string|LetterStatus $status): string
    {
        $value = $status instanceof LetterStatus ? $status->value : $status;

        return LetterStatus::tryFrom($value)?->label() ?? ucfirst($value);
    }

    public static function statusColor(string|LetterStatus $status): string
    {
        $value = $status instanceof LetterStatus ? $status->value : $status;

        return LetterStatus::tryFrom($value)?->color() ?? 'gray';
    }

    protected static function boot(): void
    {
        parent::boot();

        static::updating(function (Letter $letter) {
            if ($letter->isDirty() && ! $letter->isDirty('deleted_at')) {
                $immutableFields = ['letter_number', 'status', 'team_snapshot', 'file_path', 'published_at'];

                foreach ($immutableFields as $field) {
                    if ($letter->isDirty($field)
                        && ! is_null($letter->getOriginal($field))
                        && in_array($letter->getOriginal('status'), self::STATUS_IMMUTABLE)) {
                        return false;
                    }
                }
            }
        });

        static::deleting(function (Letter $letter) {
            if (in_array($letter->status, self::STATUS_IMMUTABLE)) {
                return false;
            }
        });
    }
}
