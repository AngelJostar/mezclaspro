<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicalSource extends Model
{
    protected $fillable = [
        'title', 'reference', 'category', 'content', 'sha256', 'is_manual',
        'resolves_manual_ambiguities', 'allows_medical_authorization', 'allows_chemical_medical_authorization',
        'approved_by', 'clinical_reviewer', 'approved_at', 'valid_until', 'superseded_at',
        'manual_version', 'resolved_manual_sha256', 'manual_type', 'file_path', 'file_name',
        'file_sha256', 'uploaded_by', 'manual_analysis',
    ];
    protected $casts = ['is_manual' => 'boolean', 'resolves_manual_ambiguities' => 'boolean', 'allows_medical_authorization' => 'boolean',
        'allows_chemical_medical_authorization' => 'boolean', 'approved_at' => 'datetime', 'valid_until' => 'date', 'superseded_at' => 'datetime', 'manual_analysis' => 'array'];

    public function scopeCurrent($query)
    {
        return $query->whereNull('superseded_at');
    }

    public function isReviewed(): bool
    {
        return !$this->superseded_at && $this->approved_by && $this->approved_at && $this->clinical_reviewer
            && ($this->is_manual || ($this->valid_until && $this->valid_until->endOfDay()->isFuture()));
    }
}
