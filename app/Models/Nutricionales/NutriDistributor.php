<?php

namespace App\Models\Nutricionales;

use Illuminate\Database\Eloquent\Model;

class NutriDistributor extends Model
{
    protected $fillable = [
        'nutri_medicine_list_id',
        'nombre',
        'rfc',
        'direccion',
        'contacto',
        'informacion_adicional',
        'logo_path',
    ];

    public function medicineList()
    {
        return $this->belongsTo(NutriMedicineList::class, 'nutri_medicine_list_id');
    }
}
