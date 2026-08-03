<?php

namespace App\Models\Nutricionales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NutritionMedicinePresentation extends Model
{
    use HasFactory;

    protected $table = 'nutrition_medicine_presentations';

    protected $fillable = [
        'nutrition_medicine_catalog_id',
        'denominacion_comercial',
        'fabricante',
        'presentacion',
        'presentacion_ml',
        'is_available',
    ];

    protected $casts = [
        'presentacion_ml' => 'decimal:4',
        'is_available' => 'boolean',
    ];

    public function catalog()
    {
        return $this->belongsTo(NutritionMedicineCatalog::class, 'nutrition_medicine_catalog_id');
    }

    public function stocks()
    {
        return $this->hasMany(
            MedicineLaboratoryStock::class,
            'nutrition_medicine_presentation_id'
        );
    }

    public function listItems()
    {
        return $this->hasMany(NutriMedicineListItem::class, 'nutrition_medicine_presentation_id');
    }

    public function solicitudInputs()
    {
        return $this->hasMany(SolicitudInput::class, 'nutrition_medicine_presentation_id');
    }

    public function activeSelections()
    {
        return $this->hasMany(NutritionLaboratoryActivePresentation::class, 'nutrition_medicine_presentation_id');
    }
}
