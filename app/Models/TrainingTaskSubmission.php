<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingTaskSubmission extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['reviewed_at' => 'datetime'];
}
