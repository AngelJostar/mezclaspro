<?php

namespace App\Models\Nutricionales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Oncologicos\Laboratory;

class NutritionLaboratoryActivePresentation extends Model
{
    use HasFactory;

    protected $table = 'nutrition_laboratory_active_presentations';

    protected $fillable = [
        'laboratory_id',
        'nutrition_medicine_catalog_id',
        'nutrition_medicine_presentation_id',
        'selected_date',
    ];

    protected $casts = [
        'selected_date' => 'date',
    ];

    public function laboratory()
    {
        return $this->belongsTo(Laboratory::class, 'laboratory_id');
    }

    public function catalog()
    {
        return $this->belongsTo(NutritionMedicineCatalog::class, 'nutrition_medicine_catalog_id');
    }

    public function presentation()
    {
        return $this->belongsTo(NutritionMedicinePresentation::class, 'nutrition_medicine_presentation_id');
    }
}
