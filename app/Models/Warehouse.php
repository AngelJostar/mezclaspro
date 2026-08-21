<?php

namespace App\Models;

use App\Models\Oncologicos\Laboratory;
use App\Models\Oncologicos\DiluentPresentation;
use App\Models\Oncologicos\MedicineBatch;
use App\Models\Nutricionales\MedicineLaboratoryStock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    protected $fillable = [
        'laboratory_id',
        'name',
        'state',
        'address',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function laboratory(): BelongsTo
    {
        return $this->belongsTo(Laboratory::class);
    }

    public function medicineBatches(): HasMany
    {
        return $this->hasMany(MedicineBatch::class);
    }

    public function nutritionStocks(): HasMany
    {
        return $this->hasMany(MedicineLaboratoryStock::class);
    }

    public function supplyPresentations(): HasMany
    {
        return $this->hasMany(DiluentPresentation::class);
    }
}
