<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property string $id
 * @property string $name
 * @property string $username
 * @property string $email
 * @property string $password
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $last_active_at
 * @property-read Identity|null $identity
 * @property-read \Illuminate\Database\Eloquent\Collection|Proposal[] $submittedProposals
 * @property-read \Illuminate\Database\Eloquent\Collection|Proposal[] $proposals
 * @property-read \Illuminate\Database\Eloquent\Collection|ResearchStage[] $researchStages
 * @property-read \Illuminate\Database\Eloquent\Collection|ProposalReviewer[] $reviews
 * @property-read \Illuminate\Database\Eloquent\Collection|MonevReview[] $monevReviews
 * @property-read \Illuminate\Database\Eloquent\Collection|PolicyInvolvement[] $policyInvolvements
 * @property-read Pivot $pivot
 */
class User extends Authenticatable implements HasMedia
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, HasUuids, InteractsWithMedia, Notifiable, TwoFactorAuthenticatable;

    /**
     * The type of the auto-incrementing ID's primary key.
     */
    protected $keyType = 'string';

    /**
     * Indicates if the ID is auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'email_verified_at',
        'last_active_at',
    ];

    /**
     * Get the identity associated with the user.
     */
    public function identity(): HasOne
    {
        return $this->hasOne(Identity::class, 'user_id');
    }

    /**
     * Get all proposals submitted by the user.
     */
    public function submittedProposals(): HasMany
    {
        return $this->hasMany(Proposal::class, 'submitter_id');
    }

    /**
     * Get all proposals where the user is a team member.
     */
    public function proposals(): BelongsToMany
    {
        return $this->belongsToMany(Proposal::class, 'proposal_user')
            ->withPivot('role', 'tasks')
            ->withTimestamps();
    }

    /**
     * Get all research stages where the user is the person in charge.
     */
    public function researchStages(): HasMany
    {
        return $this->hasMany(ResearchStage::class, 'person_in_charge_id');
    }

    /**
     * Get all review assignments for the user.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(ProposalReviewer::class, 'user_id');
    }

    /**
     * Get all policy involvements for the user.
     */
    public function policyInvolvements(): HasMany
    {
        return $this->hasMany(PolicyInvolvement::class, 'user_id');
    }

    /**
     * Get all monev reviews (post-completion audits) assigned to the user.
     */
    public function monevReviews(): HasMany
    {
        return $this->hasMany(MonevReview::class, 'reviewer_id');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'original_password',
        'two_factor_secret',
        'two_factory_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_active_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Check if the user is female based on gender column or common Indonesian female names.
     * Vetted by AI - Manual Review Required by Senior Engineer/Manager
     */
    public function isFemale(): bool
    {
        $gender = $this->identity?->gender;
        if ($gender === 'P') {
            return true;
        }
        if ($gender === 'L') {
            return false;
        }

        // Fallback: Smart Indonesian name-based gender guesser
        $name = strtolower($this->name);
        $femaleIndicators = [
            'siti', 'sri', 'dewi', 'putri', 'fitria', 'fitri', 'ani', 'rina', 'nurul',
            'diah', 'retno', 'indah', 'kartika', 'mega', 'wulan', 'lilis', 'endang',
            'yuni', 'tri', 'ria', 'ike', 'ita', 'lia', 'anisa', 'annisa', 'rara',
            'ulfa', 'restu', 'irma', 'ningsih', 'sulastri', 'atika', 'estu', 'ida',
            'mira', 'novi', 'novita', 'wahyu', 'luluk', 'triana', 'nita', 'dina',
            'dian', 'sartika', 'puji', 'tutik', 'lia', 'erni', 'hartati', 'ratna',
        ];

        $identity = $this->identity;
        $prefix = strtolower($identity ? ($identity->title_prefix ?? '') : '');
        if (str_contains($prefix, 'ny.') || str_contains($prefix, 'ibu')) {
            return true;
        }

        $firstWord = explode(' ', $name)[0] ?? '';
        foreach ($femaleIndicators as $indicator) {
            if ($firstWord === $indicator || Str::startsWith($firstWord, $indicator)) {
                return true;
            }
        }

        $words = array_slice(explode(' ', $name), 0, 2);
        foreach ($words as $word) {
            if (in_array($word, $femaleIndicators, true)) {
                return true;
            }
        }

        return false;
    }

    // attributes
    public function name(): Attribute
    {
        return new Attribute(
            get: fn (?string $value) => $value ? ucwords(strtolower(trim($value))) : null,
            set: fn (?string $value) => $value ? ucwords(strtolower(trim($value))) : null,
        );
    }

    public function profilePicture(): Attribute
    {
        return new Attribute(
            get: function ($value) {
                $mediaUrl = $this->getFirstMediaUrl('avatar');

                return $mediaUrl ?: ($this->identity->profile_picture
                    ?? 'https://www.gravatar.com/avatar/'.md5(strtolower(trim($this->email))).'?s=128&d=identicon');
            },
        );
    }

    /**
     * Register the media collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile();
    }

    /**
     * Get the active role for this user from the session.
     */
    public function activeRole(): ?string
    {
        return session('active_role');
    }

    /**
     * Check if the active role matches the given role.
     */
    public function activeHasRole(string $role): bool
    {
        return $this->activeRole() === $role;
    }

    /**
     * Check if the active role matches any of the given roles.
     */
    public function activeHasAnyRole(array $roles): bool
    {
        $activeRole = $this->activeRole();

        return in_array($activeRole, $roles, true);
    }

    /**
     * Check if the active role matches all of the given roles.
     */
    public function activeHasAllRoles(array $roles): bool
    {
        $activeRole = $this->activeRole();

        foreach ($roles as $role) {
            if ($activeRole !== $role) {
                return false;
            }
        }

        return true;
    }

    /**
     * Helper to check active status aliases
     */
    public function currentActiveRoleMatches($role): bool
    {
        if (is_array($role) || $role instanceof Collection) {
            return $this->activeHasAnyRole(is_array($role) ? $role : $role->toArray());
        }

        return $this->activeHasRole($role);
    }
}
