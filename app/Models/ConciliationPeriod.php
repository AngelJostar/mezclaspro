<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConciliationPeriod extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['period_from' => 'date', 'period_to' => 'date'];

    public function items()
    {
        return $this->hasMany(ConciliationPeriodItem::class);
    }
}
