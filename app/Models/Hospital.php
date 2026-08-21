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
        'short_name',
        'internal_key',
        'unit_type',
        'care_level',
        'rfc',
        'clues',
        'free_text',
        'google_maps_url',
        'country',
        'state',
        'municipality',
        'postal_code',
        'neighborhood',
        'street_number',
        'contact_name',
        'contact_position',
        'phone',
        'email',
        'reception_hours',
        'operation_days',
        'service_oncology',
        'service_antibiotics',
        'service_nutrition',
        'laboratory_id',
        'is_active',
        'access_is_active',
        'nutri_medicine_list_id',
        'onco_medicine_list_id',
        'antibiotic_medicine_list_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'access_is_active' => 'boolean',
        'operation_days' => 'array',
        'service_oncology' => 'boolean',
        'service_antibiotics' => 'boolean',
        'service_nutrition' => 'boolean',
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

    public function antibioticMedicineList()
    {
        return $this->belongsTo(\App\Models\Oncologicos\MedicineList::class, 'antibiotic_medicine_list_id');
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
