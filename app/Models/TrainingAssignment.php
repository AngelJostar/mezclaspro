<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingAssignment extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['module_ids' => 'array', 'start_date' => 'date', 'started_at' => 'datetime', 'completed_at' => 'datetime'];

    public function setStartDateAttribute($value): void
    {
        $this->attributes['start_date'] = \Illuminate\Support\Carbon::parse($value)->format('Y-m-d');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class, 'program_id');
    }

    public function modules(): array
    {
        return array_values(array_filter($this->program?->module_definitions ?? [],
            fn ($module) => in_array($module['id'], $this->module_ids, true)));
    }
}
