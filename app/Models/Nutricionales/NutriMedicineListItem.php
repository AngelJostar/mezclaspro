<?php

namespace App\Models\Nutricionales;

use Illuminate\Database\Eloquent\Model;

class NutriMedicineListItem extends Model
{
    protected $fillable = [
        'nutri_medicine_list_id',
        'nutrition_medicine_presentation_id',
        'precio_ml',
    ];

    public function list()
    {
        return $this->belongsTo(NutriMedicineList::class, 'nutri_medicine_list_id');
    }

    public function presentation()
    {
        return $this->belongsTo(NutritionMedicinePresentation::class, 'nutrition_medicine_presentation_id');
    }
}
