<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceServiceQuote extends Model
{
    protected $fillable = [
        'maintenance_service_id',
        'supplier_name',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(MaintenanceService::class, 'maintenance_service_id');
    }
}
