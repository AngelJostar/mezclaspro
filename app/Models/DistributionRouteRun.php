<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DistributionRouteRun extends Model
{
    protected $fillable = [
        'distribution_route_id',
        'messenger_id',
        'started_at',
        'ended_at',
        'start_latitude',
        'start_longitude',
        'start_accuracy',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'start_latitude' => 'float',
        'start_longitude' => 'float',
        'start_accuracy' => 'float',
    ];

    public function route(): BelongsTo
    {
        return $this->belongsTo(DistributionRoute::class, 'distribution_route_id');
    }

    public function messenger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'messenger_id');
    }

    public function locations(): HasMany
    {
        return $this->hasMany(DistributionLocationUpdate::class);
    }
}
