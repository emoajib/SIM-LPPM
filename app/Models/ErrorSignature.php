<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Satu baris = satu kelas error (fingerprint), bukan satu kejadian.
 *
 * @property string $id
 * @property string $fingerprint
 * @property string $exception_class
 * @property string|null $message
 * @property string|null $file
 * @property int|null $line
 * @property string|null $module
 * @property string|null $bc_code
 * @property string $status
 * @property int $occurrences
 * @property Carbon|null $first_seen_at
 * @property Carbon|null $last_seen_at
 * @property string|null $commit_fix
 * @property string|null $notes
 */
class ErrorSignature extends Model
{
    use HasUuids;

    public const STATUS_OPEN = 'open';

    public const STATUS_INVESTIGATING = 'investigating';

    public const STATUS_FIXED = 'fixed';

    public const STATUS_IGNORED = 'ignored';

    protected $fillable = [
        'fingerprint',
        'exception_class',
        'message',
        'file',
        'line',
        'module',
        'bc_code',
        'status',
        'occurrences',
        'first_seen_at',
        'last_seen_at',
        'commit_fix',
        'notes',
    ];

    protected $casts = [
        'line' => 'integer',
        'occurrences' => 'integer',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    /**
     * Error yang masih butuh perhatian (belum fixed/ignored).
     *
     * @param  Builder<ErrorSignature>  $query
     * @return Builder<ErrorSignature>
     */
    public function scopeNeedsAttention(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_OPEN, self::STATUS_INVESTIGATING]);
    }

    /**
     * @param  Builder<ErrorSignature>  $query
     * @return Builder<ErrorSignature>
     */
    public function scopeForModule(Builder $query, string $module): Builder
    {
        return $query->where('module', $module);
    }
}
