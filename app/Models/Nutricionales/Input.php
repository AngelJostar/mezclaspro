<?php

namespace App\Models\Nutricionales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Input extends Model
{
    use HasFactory;

    protected $fillable = [
        'description',
        'unidad',
        'is_active',
        'tipo_input',
        'orden_enum',
        'category_id',
        'mult',
        'div',
    ];

    public function nutritionMedicineCatalog()
    {
        return $this->hasOne(NutritionMedicineCatalog::class, 'input_id');
    }

    public function solicitudInputs()
    {
        return $this->hasMany(SolicitudInput::class, 'input_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
