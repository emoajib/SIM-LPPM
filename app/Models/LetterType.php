<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LetterType extends Model
{
    protected $fillable = [
        'code',
        'name',
        'category',
        'numbering_format',
        'template_view',
        'is_uploadable',
    ];

    public function letters(): HasMany
    {
        return $this->hasMany(Letter::class);
    }
}
