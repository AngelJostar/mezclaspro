<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExternalMixtureRequest extends Model
{
    protected $fillable = [
        'remote_request_id',
        'local_external_id',
        'hospital_id',
        'catalog_type',
        'catalog_version',
        'status',
        'status_details',
        'materialized_type',
        'materialized_id',
        'payload_hash',
        'payload',
        'last_error',
        'received_at',
        'materialized_at',
        'status_checked_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'status_details' => 'array',
        'received_at' => 'datetime',
        'materialized_at' => 'datetime',
        'status_checked_at' => 'datetime',
    ];

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ExternalMixtureDocument::class);
    }

    public function webhookDeliveries(): HasMany
    {
        return $this->hasMany(ExternalMixtureWebhookDelivery::class);
    }
}
