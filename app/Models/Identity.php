<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $identity_id
 * @property string $user_id
 * @property string|null $sinta_id
 * @property string|null $scopus_id
 * @property string|null $google_scholar_id
 * @property string|null $wos_id
 * @property string|null $type
 * @property string|null $address
 * @property Carbon|null $birthdate
 * @property string|null $birthplace
 * @property int|null $institution_id
 * @property string|null $institution_name
 * @property int|null $study_program_id
 * @property int|null $faculty_id
 * @property string|null $profile_picture
 * @property string|null $last_education
 * @property string|null $functional_position
 * @property string|null $title_prefix
 * @property string|null $title_suffix
 * @property float|null $sinta_score_v2_overall
 * @property float|null $sinta_score_v2_3yr
 * @property float|null $sinta_score_v3_overall
 * @property float|null $sinta_score_v3_3yr
 * @property float|null $affil_score_v3_overall
 * @property float|null $affil_score_v3_3yr
 * @property int|null $scopus_documents
 * @property int|null $scopus_citations
 * @property int|null $scopus_cited_documents
 * @property int|null $scopus_h_index
 * @property int|null $scopus_g_index
 * @property int|null $scopus_i10_index
 * @property int|null $gs_documents
 * @property int|null $gs_citations
 * @property int|null $gs_cited_documents
 * @property int|null $gs_h_index
 * @property int|null $gs_g_index
 * @property int|null $gs_i10_index
 * @property int|null $wos_documents
 * @property int|null $wos_citations
 * @property int|null $wos_cited_documents
 * @property int|null $wos_h_index
 * @property int|null $wos_g_index
 * @property int|null $wos_i10_index
 * @property int|null $garuda_documents
 * @property int|null $garuda_citations
 * @property int|null $garuda_cited_documents
 * @property bool $is_active
 * @property-read User $user
 * @property-read Institution|null $institution
 * @property-read StudyProgram|null $studyProgram
 * @property-read Faculty|null $faculty
 */
class Identity extends Model
{
    use HasFactory;

    protected $fillable = [
        'identity_id',
        'user_id',
        'sinta_id',
        'scopus_id',
        'google_scholar_id',
        'wos_id',
        'type',
        'address',
        'birthdate',
        'birthplace',
        'institution_id',
        'institution_name',
        'study_program_id',
        'faculty_id',
        'science_cluster_id',
        'profile_picture',
        // Academic Profile
        'last_education',
        'functional_position',
        'title_prefix',
        'title_suffix',
        // SINTA Scores
        'sinta_score_v2_overall',
        'sinta_score_v2_3yr',
        'sinta_score_v3_overall',
        'sinta_score_v3_3yr',
        'affil_score_v3_overall',
        'affil_score_v3_3yr',
        // Scopus
        'scopus_documents',
        'scopus_citations',
        'scopus_cited_documents',
        'scopus_h_index',
        'scopus_g_index',
        'scopus_i10_index',
        // Google Scholar
        'gs_documents',
        'gs_citations',
        'gs_cited_documents',
        'gs_h_index',
        'gs_g_index',
        'gs_i10_index',
        // WoS
        'wos_documents',
        'wos_citations',
        'wos_cited_documents',
        'wos_h_index',
        'wos_g_index',
        'wos_i10_index',
        // Garuda
        'garuda_documents',
        'garuda_citations',
        'garuda_cited_documents',
        // Status
        'is_active',
        'is_external',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birthdate' => 'date',
            'is_active' => 'boolean',
            'sinta_score_v2_overall' => 'float',
            'sinta_score_v2_3yr' => 'float',
            'sinta_score_v3_overall' => 'float',
            'sinta_score_v3_3yr' => 'float',
            'affil_score_v3_overall' => 'float',
            'affil_score_v3_3yr' => 'float',
            'scopus_documents' => 'integer',
            'scopus_citations' => 'integer',
            'scopus_cited_documents' => 'integer',
            'scopus_g_index' => 'integer',
            'scopus_i10_index' => 'integer',
            'gs_documents' => 'integer',
            'gs_citations' => 'integer',
            'gs_cited_documents' => 'integer',
            'gs_g_index' => 'integer',
            'gs_i10_index' => 'integer',
            'wos_documents' => 'integer',
            'wos_citations' => 'integer',
            'wos_cited_documents' => 'integer',
            'wos_g_index' => 'integer',
            'wos_i10_index' => 'integer',
            'garuda_documents' => 'integer',
            'garuda_citations' => 'integer',
            'garuda_cited_documents' => 'integer',
            'is_external' => 'boolean',
        ];
    }

    /**
     * Get the user that owns the identity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the institution that the identity belongs to.
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Get the study program that the identity belongs to.
     */
    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class);
    }

    /**
     * Get the faculty that the identity belongs to.
     */
    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    /**
     * Get the science cluster (rumpun ilmu) that the identity belongs to.
     */
    public function scienceCluster(): BelongsTo
    {
        return $this->belongsTo(ScienceCluster::class);
    }

    /**
     * Get the official SINTA profile URL.
     */
    public function getSintaUrl(): ?string
    {
        if (! $this->sinta_id) {
            return null;
        }

        return "https://sinta.kemdiktisaintek.go.id/authors/profile/{$this->sinta_id}";
    }

    /**
     * Get the official Scopus profile URL.
     */
    public function getScopusUrl(): ?string
    {
        if (! $this->scopus_id) {
            return null;
        }

        return "https://www.scopus.com/authid/detail.uri?authorId={$this->scopus_id}";
    }

    /**
     * Get the official Google Scholar profile URL.
     */
    public function getGoogleScholarUrl(): ?string
    {
        if (! $this->google_scholar_id) {
            return null;
        }

        return "https://scholar.google.com/citations?user={$this->google_scholar_id}";
    }
}
