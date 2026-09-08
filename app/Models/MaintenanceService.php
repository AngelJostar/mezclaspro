<?php

namespace App\Models;

use App\Models\Oncologicos\Laboratory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceService extends Model
{
    protected $fillable = [
        'laboratory_id',
        'source_key',
        'service',
        'qualification_stages',
        'type',
        'areas',
        'frequency',
        'identification',
        'providers',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function laboratory(): BelongsTo
    {
        return $this->belongsTo(Laboratory::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(MaintenanceServiceQuote::class)
            ->orderBy('price')
            ->orderBy('supplier_name');
    }
}
