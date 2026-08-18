<?php

namespace App\Models\Nutricionales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NutritionMedicineCatalog extends Model
{
    use HasFactory;

    protected $table = 'nutrition_medicines_catalog';

    protected $fillable = [
        'denominacion_generica',
        'category_id',
        'input_id',
        'osmolaridad',
        'conc_min',
        'conc_max',
        'diluent_ids',
        'administration_route_ids',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'osmolaridad' => 'decimal:4',
        'conc_min' => 'decimal:4',
        'conc_max' => 'decimal:4',
        'diluent_ids' => 'array',
        'administration_route_ids' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function input()
    {
        return $this->belongsTo(Input::class, 'input_id');
    }

    public function presentations()
    {
        return $this->hasMany(NutritionMedicinePresentation::class, 'nutrition_medicine_catalog_id');
    }

    public function activePresentations()
    {
        return $this->hasMany(NutritionLaboratoryActivePresentation::class, 'nutrition_medicine_catalog_id');
    }
}
