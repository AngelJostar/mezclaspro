<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalMixtureWebhookDelivery extends Model
{
    protected $fillable = [
        'external_mixture_request_id',
        'event_id',
        'event_type',
        'status',
        'payload',
        'payload_hash',
        'attempts',
        'http_status',
        'last_error',
        'last_attempt_at',
        'next_attempt_at',
        'delivered_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'last_attempt_at' => 'datetime',
        'next_attempt_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function mixtureRequest(): BelongsTo
    {
        return $this->belongsTo(ExternalMixtureRequest::class, 'external_mixture_request_id');
    }
}
