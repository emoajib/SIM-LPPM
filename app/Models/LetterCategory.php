<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Vetted by AI - Manual Review Required by Senior Engineer/Manager
class LetterCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    public function letterTypes(): HasMany
    {
        return $this->hasMany(LetterType::class, 'category', 'slug');
    }
}
