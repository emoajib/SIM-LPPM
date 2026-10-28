<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class NotificationLog extends Model
{
    /** @use HasFactory<NotificationLogFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'channel',
        'recipient',
        'notification_type',
        'payload',
        'status',
        'attempts',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'sent_at' => 'datetime',
        'attempts' => 'integer',
    ];

    /**
     * The relationship to the user that received the notification.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
