<?php

namespace App\Models\Oncologicos;

use Illuminate\Database\Eloquent\Relations\Pivot;

class AdministrationRouteMedicineCatalog extends Pivot
{
    protected $table = 'administration_route_medicine_catalog';

    protected $fillable = [
        'administration_route_id',
        'medicine_catalog_id',
    ];

    public $timestamps = false;
}
