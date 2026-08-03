<?php

namespace App\Models;

use App\Models\Nutricionales\NutriMedicineList;
use App\Models\Oncologicos\Laboratory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hospital extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'adress',
        'laboratory_id',
        'is_active',
        'nutri_medicine_list_id',
        'onco_medicine_list_id',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function clientes()
    {
        return $this->belongsToMany(\App\Models\Cliente::class, 'cliente_hospital');
    }

    public function instituciones()
    {
        return $this->belongsToMany(\App\Models\Institucion::class, 'cliente_hospital', 'hospital_id', 'cliente_id');
    }

    public function oncoMedicineList()
    {
        return $this->belongsTo(\App\Models\Oncologicos\MedicineList::class, 'onco_medicine_list_id');
    }

    public function laboratory()
    {
        return $this->belongsTo(Laboratory::class);
    }

    public function nutriMedicineList()
    {
        return $this->belongsTo(NutriMedicineList::class, 'nutri_medicine_list_id');
    }
}
