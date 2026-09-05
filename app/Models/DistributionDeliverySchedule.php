<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DistributionDeliverySchedule extends Model
{
    protected $fillable = [
        'warehouse_id',
        'hospital_id',
        'distribution_route_id',
        'scheduled_date',
        'status',
        'sent_at',
        'created_by',
        'sent_by',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'sent_at' => 'datetime',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(DistributionRoute::class, 'distribution_route_id');
    }

    public function confirmation(): HasOne
    {
        return $this->hasOne(DistributionDeliveryConfirmation::class, 'distribution_delivery_schedule_id');
    }
}
