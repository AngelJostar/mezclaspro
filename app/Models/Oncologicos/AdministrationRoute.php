<?php

namespace App\Models\Oncologicos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdministrationRoute extends Model {
    public function medicines()
    {
        return $this->belongsToMany(MedicinesCatalog::class, 'administration_route_medicine_catalog', 'administration_route_id', 'medicine_catalog_id');
    }
}
