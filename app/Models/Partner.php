<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property string $id
 * @property string $name
 * @property string|null $email
 * @property string|null $institution
 * @property string|null $country
 * @property string|null $address
 * @property string|null $type
 * @property float|null $total_budget
 * @property-read Collection|Proposal[] $proposals
 */
class Partner extends Model implements HasMedia
{
    use HasFactory;
    use HasUuids;
    use InteractsWithMedia;

    protected $fillable = [
        'name',
        'email',
        'institution',
        'country',
        'address',
        'type',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'count_activity' => 'integer',
            'total_budget' => 'decimal:2',
        ];
    }

    /**
     * Get the proposals associated with the partner.
     */
    public function proposals(): BelongsToMany
    {
        return $this->belongsToMany(Proposal::class, 'proposal_partner');
    }

    /**
     * Register the media collections.
     */
    public function registerMediaCollections(): void
    {
        // Dokumen MOU / PKS — dikelola oleh Admin LPPM (level institusi)
        $this->addMediaCollection('mou_pks')
            ->singleFile()
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg']);

        // Surat Kesediaan Mitra — diupload Dosen saat mengajukan proposal
        // Disimpan banyak (multiple) karena satu mitra bisa ikut banyak proposal
        // Dibedakan via custom_properties['proposal_id']
        $this->addMediaCollection('commitment_letter')
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg']);
    }

    /**
     * Check if partner has commitment letter for specific proposal.
     */
    public function hasCommitmentForProposal(string $proposalId): bool
    {
        return $this->getMedia('commitment_letter')
            ->filter(function ($media) use ($proposalId) {
                return $media->getCustomProperty('proposal_id') === $proposalId;
            })
            ->isNotEmpty();
    }

    /**
     * Get commitment letter URL for specific proposal.
     */
    public function getCommitmentUrlForProposal(string $proposalId): ?string
    {
        return $this->getMedia('commitment_letter')
            ->filter(function ($media) use ($proposalId) {
                return $media->getCustomProperty('proposal_id') === $proposalId;
            })
            ->first()?->getUrl();
    }

    /**
     * Get commitment letter media model for specific proposal.
     * Vetted by AI - Manual Review Required by Senior Engineer/Manager
     */
    public function getCommitmentMediaForProposal(string $proposalId)
    {
        return $this->getMedia('commitment_letter')
            ->filter(function ($media) use ($proposalId) {
                return $media->getCustomProperty('proposal_id') === $proposalId;
            })
            ->first();
    }
}
