<?php

namespace App\Models;

use App\Models\Nutricionales\MedicineLaboratoryStock;
use App\Models\Nutricionales\NutritionMedicinePresentation;
use App\Models\Oncologicos\MedicineBatch;
use App\Models\Oncologicos\MedicinePresentation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicineRemainder extends Model
{
    protected $fillable = [
        'domain',
        'laboratory_id',
        'warehouse_id',
        'nutrition_medicine_presentation_id',
        'medicine_presentation_id',
        'medicine_laboratory_stock_id',
        'medicine_batch_id',
        'lote',
        'caducidad',
        'opened_at',
        'usable_until',
        'initial_ml',
        'current_ml',
        'is_active',
        'opened_for_type',
        'opened_for_id',
        'discarded_at',
        'discard_reason',
        'notes',
    ];

    protected $casts = [
        'caducidad' => 'date',
        'opened_at' => 'datetime',
        'usable_until' => 'datetime',
        'initial_ml' => 'decimal:4',
        'current_ml' => 'decimal:4',
        'is_active' => 'boolean',
        'discarded_at' => 'datetime',
    ];

    public function nutritionPresentation(): BelongsTo
    {
        return $this->belongsTo(
            NutritionMedicinePresentation::class,
            'nutrition_medicine_presentation_id'
        );
    }

    public function oncologicPresentation(): BelongsTo
    {
        return $this->belongsTo(
            MedicinePresentation::class,
            'medicine_presentation_id'
        );
    }

    public function nutritionStock(): BelongsTo
    {
        return $this->belongsTo(
            MedicineLaboratoryStock::class,
            'medicine_laboratory_stock_id'
        );
    }

    public function oncologicBatch(): BelongsTo
    {
        return $this->belongsTo(
            MedicineBatch::class,
            'medicine_batch_id'
        );
    }

    public function movements()
    {
        return $this->hasMany(MedicineRemainderMovement::class, 'medicine_remainder_id');
    }
}
