<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalMixtureDocument extends Model
{
    protected $fillable = ['external_mixture_request_id', 'type', 'original_name', 'mime_type', 'size', 'sha256', 'disk', 'path', 'uploaded_at'];

    protected $casts = ['uploaded_at' => 'datetime'];

    public function request(): BelongsTo
    {
        return $this->belongsTo(ExternalMixtureRequest::class, 'external_mixture_request_id');
    }
}
