<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConciliationPeriodDecision extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['snapshot' => 'array', 'period_from' => 'date', 'period_to' => 'date'];
}
