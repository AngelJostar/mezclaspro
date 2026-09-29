<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicalReview extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
    protected $hidden = ['payload_hash', 'context_hash', 'session_hash', 'clinical_context', 'medical_authorization'];
    protected $casts = ['result' => 'encrypted:array', 'clinical_context' => 'encrypted:array', 'medical_authorization' => 'encrypted:array', 'can_submit' => 'boolean',
        'expires_at' => 'datetime', 'used_at' => 'datetime'];
}
