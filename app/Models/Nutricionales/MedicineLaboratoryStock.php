<?php

namespace App\Models\Nutricionales;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use App\Models\Oncologicos\Laboratory;

class MedicineLaboratoryStock extends Model
{
    protected $fillable = [
        'nutrition_medicine_presentation_id',
        'laboratory_id',
        'warehouse_id',
        'stock_ml_inicial',
        'stock_ml_actual',
        'frascos_iniciales',
        'frascos_actuales',
        'lote',
        'caducidad',
        'fecha_ingreso',
        'numero_factura',
        'is_active',
    ];

    protected $casts = [
        'caducidad' => 'date',
        'fecha_ingreso' => 'date',
        'is_active' => 'boolean',
        'stock_ml_inicial' => 'decimal:2',
        'stock_ml_actual' => 'decimal:2',
        'frascos_iniciales' => 'decimal:2',
        'frascos_actuales' => 'decimal:2',
    ];

    public function presentation()
    {
        return $this->belongsTo(NutritionMedicinePresentation::class, 'nutrition_medicine_presentation_id');
    }

    public function laboratory()
    {
        return $this->belongsTo(Laboratory::class, 'laboratory_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function movements()
    {
        return $this->hasMany(MedicineStockMovement::class, 'medicine_laboratory_stock_id');
    }
}
