<?php

namespace App\Models\Nutricionales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NutritionMedicineCatalog extends Model
{
    use HasFactory;

    protected $table = 'nutrition_medicines_catalog';

    protected $fillable = [
        'external_code',
        'denominacion_generica',
        'category_id',
        'input_id',
        'osmolaridad',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'osmolaridad' => 'decimal:4',
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
