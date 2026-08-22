<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstitutionBillingMovement extends Model
{
    protected $fillable = [
        'institution_billing_id',
        'user_id',
        'user_name',
        'origen_tipo',
        'origen_id',
        'remision',
        'from_stage',
        'to_stage',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function billing()
    {
        return $this->belongsTo(InstitutionBilling::class, 'institution_billing_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
