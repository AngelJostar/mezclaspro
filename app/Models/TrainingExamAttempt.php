<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingExamAttempt extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['exam_snapshot' => 'array', 'answers' => 'array', 'passed' => 'boolean', 'score' => 'integer',
        'expires_at' => 'datetime', 'submitted_at' => 'datetime'];
    protected $hidden = ['exam_snapshot', 'answers'];
}
