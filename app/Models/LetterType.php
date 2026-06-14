<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

// Vetted by AI - Manual Review Required by Senior Engineer/Manager
class LetterType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'category',
        'numbering_format',
        'template_view',
        'is_uploadable',
        'is_active',
        'template_file_path',
        'template_file_original_name',
        'template_file_size',
        'template_uploaded_at',
        'template_uploaded_by',
    ];

    protected $casts = [
        'is_uploadable' => 'boolean',
        'is_active' => 'boolean',
        'template_file_size' => 'integer',
        'template_uploaded_at' => 'datetime',
    ];

    public function letters(): HasMany
    {
        return $this->hasMany(Letter::class);
    }

    public function letterCategory(): BelongsTo
    {
        return $this->belongsTo(LetterCategory::class, 'category', 'slug');
    }
}
