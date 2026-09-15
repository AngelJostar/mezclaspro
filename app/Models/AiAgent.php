<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiAgent extends Model
{
    protected $fillable = ['name', 'description', 'instructions', 'is_active'];

    protected $attributes = ['is_active' => false];

    protected $casts = ['is_active' => 'boolean', 'configuration' => 'array', 'next_run_at' => 'datetime'];

    public function runs()
    {
        return $this->hasMany(AiAgentRun::class);
    }

    public function findings()
    {
        return $this->hasMany(AiAgentFinding::class);
    }
}
