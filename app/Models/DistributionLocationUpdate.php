<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DistributionLocationUpdate extends Model
{
    protected $fillable = [
        'distribution_route_run_id',
        'latitude',
        'longitude',
        'accuracy',
        'recorded_at',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'accuracy' => 'float',
        'recorded_at' => 'datetime',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(DistributionRouteRun::class, 'distribution_route_run_id');
    }
}
