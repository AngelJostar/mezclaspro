<?php

namespace App\Models\Nutricionales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medicine extends Model
{
    use HasFactory;

    protected $fillable = [
        'denominacion_generica',
        'denominacion_comercial',
        'precio_ml',
        'presentacion',
        'presentacion_ml',
        'is_active',
        'osmolaridad',
        'category_id',
        'input_id',
        'lote', // Agregar lote
        'caducidad', // Agregar caducidad

    ];

    //Relacion uno a muchos inversa
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    //Relacion uno a muchos inversa
    public function input()
    {
        return $this->belongsTo(Input::class);
    }

    public function nutriListItems()
    {
        return $this->hasMany(NutriMedicineListItem::class, 'medicine_id');
    }

    public function laboratoryStocks()
    {
        return $this->hasMany(MedicineLaboratoryStock::class, 'medicine_id');
    }
}
