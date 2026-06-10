<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudyProgramRoadmap extends Model
{
    use HasFactory;

    protected $fillable = [
        'study_program_id',
        'faculty_roadmap_id',
        'title',
        'period_start',
        'period_end',
        'vision',
        'research_tree',
        'cpl_alignment',
        'tkt_target_min',
        'tkt_target_max',
        'is_active',
    ];

    protected $casts = [
        'research_tree' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the study program that owns the roadmap.
     *
     * @return BelongsTo<StudyProgram, $this>
     */
    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class);
    }

    /**
     * Get the faculty roadmap this roadmap belongs to.
     *
     * @return BelongsTo<FacultyRoadmap, $this>
     */
    public function facultyRoadmap(): BelongsTo
    {
        return $this->belongsTo(FacultyRoadmap::class);
    }

    /**
     * Get the proposals associated with the roadmap.
     *
     * @return HasMany<Proposal, $this>
     */
    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class);
    }
}
