<?php

namespace App\Models;

use App\Enums\SignatureMode;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DocumentSignature extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'document_type',
        'document_id',
        'variant',
        'action',
        'signed_role',
        'signed_by',
        'signed_at',
        'hash_alg',
        'document_hash',
        'kid',
        'signature',
        'payload',
        'mode',
    ];

    protected $casts = [
        'payload' => 'array',
        'signed_at' => 'datetime',
        'mode' => SignatureMode::class,
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    public function document(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'document_type', 'document_id');
    }
}
