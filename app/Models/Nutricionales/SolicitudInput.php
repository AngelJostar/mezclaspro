<?php

namespace App\Models\Nutricionales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SolicitudInput extends Model
{
    use HasFactory;

    protected $fillable = [
        'input_id',
        'nutrition_medicine_presentation_id',
        'lote',
        'caducidad',
        'valor',
        'valor_sobrellenado',
        'valor_ml',
        'solicitud_id',
        'precio_ml',
    ];

    protected $casts = [
        'caducidad' => 'date',
    ];

    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class);
    }

    public function input()
    {
        return $this->belongsTo(Input::class);
    }

    public function presentation()
    {
        return $this->belongsTo(NutritionMedicinePresentation::class, 'nutrition_medicine_presentation_id');
    }
}
