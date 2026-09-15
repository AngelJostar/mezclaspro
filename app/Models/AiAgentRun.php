<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiAgentRun extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['configuration' => 'array', 'result' => 'array', 'started_at' => 'datetime', 'finished_at' => 'datetime'];

    public static function statusLabels(): array
    {
        return ['running' => 'En ejecución', 'completed' => 'Completada', 'partial' => 'Parcial', 'failed' => 'Error'];
    }
}
