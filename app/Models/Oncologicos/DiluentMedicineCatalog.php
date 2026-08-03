<?php
// app/Models/Oncologicos/DiluentMedicineCatalog.php
namespace App\Models\Oncologicos;

use Illuminate\Database\Eloquent\Relations\Pivot;

class DiluentMedicineCatalog extends Pivot
{
    protected $table = 'diluent_medicine_catalog';

    protected $fillable = [
        'diluent_id',
        'medicine_catalog_id',
    ];

    // Si tu pivot NO usa timestamps, déjalo en false.
    // (Tu migración original sí los crea; si decides usarlos, elimina esta línea)
    public $timestamps = false;
}
