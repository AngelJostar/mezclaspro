<?php

namespace App\Models\Oncologicos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicinesCatalog extends Model
{
    use HasFactory;

    protected $table = 'medicines_catalog';

    protected $fillable = [
        'external_code',
        'denominacion',
        'catalog_category',
        'presentacion',
        'requires_infusor',          // 👈 nuevo
        'state',
        'conc_min',
        'conc_max',
        'legend',
        'lote',
        'caducidad',
    ];

    protected $casts = [
        'requires_infusor' => 'boolean', // 👈 importante
        'state'            => 'boolean',
        'caducidad'        => 'date',
    ];

    public function scopeForCategory($query, string $category)
    {
        return $query->where('catalog_category', $category);
    }

    public function medicineOncos()
    {
        return $this->hasMany(MedicineOnco::class, 'catalog_id');
    }

    public function diluents()
    {
        return $this->belongsToMany(Diluent::class, 'diluent_medicine_catalog', 'medicine_catalog_id', 'diluent_id');
    }

    public function administrationRoutes()
    {
        return $this->belongsToMany(AdministrationRoute::class, 'administration_route_medicine_catalog', 'medicine_catalog_id', 'administration_route_id');
    }

    public function presentations()
    {
        // FK: catalog_id en medicine_presentations
        return $this->hasMany(MedicinePresentation::class, 'catalog_id');
    }
}
