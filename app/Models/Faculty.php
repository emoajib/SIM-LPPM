<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $institution_id
 * @property string $name
 * @property string|null $code
 * @property string|null $dean_name
 * @property string|null $dean_id
 * @property string|null $dean_user_id
 * @property-read User|null $deanUser
 * @property-read Institution $institution
 * @property-read Collection|StudyProgram[] $studyPrograms
 * @property-read Collection|Identity[] $identities
 */
class Faculty extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'name',
        'code',
        'dean_name',
        'dean_id',
        'dean_user_id',
        'research_roadmap',
    ];

    protected function casts(): array
    {
        return [
            'research_roadmap' => 'array',
        ];
    }

    protected function deanName(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => normalize_person_name($value),
            set: fn (?string $value) => normalize_person_name($value),
        );
    }

    /**
     * Get the user who is the dean of this faculty.
     */
    public function deanUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dean_user_id');
    }

    /**
     * Get the institution that owns the faculty.
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Get all study programs for the faculty.
     */
    public function studyPrograms(): HasMany
    {
        return $this->hasMany(StudyProgram::class);
    }

    /**
     * Get all identities associated with the faculty.
     */
    public function identities(): HasMany
    {
        return $this->hasMany(Identity::class);
    }
}
