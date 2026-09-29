<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicalSource extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['is_manual' => 'boolean', 'resolves_manual_ambiguities' => 'boolean', 'allows_medical_authorization' => 'boolean',
        'allows_chemical_medical_authorization' => 'boolean', 'approved_at' => 'datetime', 'valid_until' => 'date'];

    public function isReviewed(): bool
    {
        return $this->approved_by && $this->approved_at && $this->clinical_reviewer
            && $this->valid_until && $this->valid_until->endOfDay()->isFuture();
    }
}
