<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'endpoint',
        'fcm_token',
        'p256dh_key',
        'auth_key',
        'user_agent',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isFcm(): bool
    {
        return $this->type === 'fcm';
    }

    public function isWebPush(): bool
    {
        return $this->type === 'webpush';
    }
}
