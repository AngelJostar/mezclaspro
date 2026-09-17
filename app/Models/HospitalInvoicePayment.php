<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HospitalInvoicePayment extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['amount' => 'decimal:2', 'paid_at' => 'date', 'reviewed_at' => 'datetime'];

    public function account()
    {
        return $this->belongsTo(HospitalInvoiceAccount::class, 'account_id');
    }
}
