<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DistributionDeliveryConfirmation extends Model
{
    protected $fillable = [
        'distribution_delivery_schedule_id',
        'distribution_route_run_id',
        'messenger_id',
        'delivered_at',
        'latitude',
        'longitude',
        'accuracy',
        'notes',
    ];

    protected $casts = [
        'delivered_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'accuracy' => 'float',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(DistributionDeliverySchedule::class, 'distribution_delivery_schedule_id');
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(DistributionRouteRun::class, 'distribution_route_run_id');
    }

    public function messenger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'messenger_id');
    }
}
