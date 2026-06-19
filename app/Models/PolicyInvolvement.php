<?php

namespace App\Models;

use App\Enums\PolicyInvolvementStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property string $id
 * @property string $user_id
 * @property string $title
 * @property string $organization
 * @property string|null $level
 * @property string|null $role
 * @property Carbon|null $date
 * @property string|null $status
 * @property string|null $description
 * @property Carbon|null $verified_at
 * @property string|null $verified_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read User|null $verifier
 *
 * Virtual properties used in IKU Verification
 * @property string $model_type
 * @property bool $is_verified_status
 * @property string|null $display_title
 * @property string $submitter_name
 * @property string|null $document_url
 */
class PolicyInvolvement extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'user_id',
        'title',
        'organization',
        'level',
        'role',
        'date',
        'status',
        'description',
        'verified_at',
        'verified_by',
    ];

    protected $casts = [
        'date' => 'date',
        'verified_at' => 'datetime',
        'status' => PolicyInvolvementStatus::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('supporting_document')
            ->singleFile()
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png']);
    }
}
