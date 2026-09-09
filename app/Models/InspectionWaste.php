<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InspectionWaste extends Model
{
    protected $fillable = ['mezcla_id', 'production_attempt', 'user_id', 'reason', 'snapshot'];

    protected $casts = ['snapshot' => 'array', 'production_attempt' => 'integer'];
}
