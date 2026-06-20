<?php

namespace App\Models;

use Database\Factories\InstitutionFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $id
 * @property string $name
 * @property string|null $short_name
 * @property string|null $code
 * @property string|null $type
 * @property string|null $address
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $website
 * @property string|null $lppm_head_name
 * @property string|null $lppm_head_id
 * @property string|null $lppm_head_user_id
 * @property bool $is_verified
 * @property-read User|null $lppmHeadUser
 * @property-read Collection|Faculty[] $faculties
 * @property-read Collection|StudyProgram[] $studyPrograms
 * @property-read Collection|Identity[] $identities
 * @property-read Collection|ReviewerProfile[] $reviewerProfiles
 */
class Institution extends Model
{
    /** @use HasFactory<InstitutionFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'short_name',
        'code',
        'type',
        'address',
        'phone',
        'email',
        'website',
        'lppm_head_name',
        'lppm_head_id',
        'lppm_head_user_id',
        'is_verified',
        'is_default',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_default' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(function () {
            Cache::forget('institution.default');
        });
    }

    /**
     * Get the user who is the head of LPPM for this institution.
     */
    public function lppmHeadUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lppm_head_user_id');
    }

    /**
     * Get all faculties for the institution.
     */
    public function faculties(): HasMany
    {
        return $this->hasMany(Faculty::class);
    }

    /**
     * Get all study programs for the institution (through faculties).
     */
    public function studyPrograms(): HasManyThrough
    {
        return $this->hasManyThrough(StudyProgram::class, Faculty::class);
    }

    /**
     * Get all identities associated with the institution.
     */
    public function identities(): HasMany
    {
        return $this->hasMany(Identity::class);
    }

    /**
     * Get all reviewer profiles associated with the institution.
     */
    public function reviewerProfiles(): HasMany
    {
        return $this->hasMany(ReviewerProfile::class);
    }
}
