<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingCompletion extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['student_snapshot' => 'array', 'start_date' => 'date', 'completed_at' => 'datetime'];

    public function setStartDateAttribute($value): void
    {
        $this->attributes['start_date'] = \Illuminate\Support\Carbon::parse($value)->format('Y-m-d');
    }
}
