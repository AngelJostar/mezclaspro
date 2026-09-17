<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HospitalInvoiceAccount extends Model
{
    protected $guarded = ['id'];

    public function payments()
    {
        return $this->hasMany(HospitalInvoicePayment::class, 'account_id');
    }
}
