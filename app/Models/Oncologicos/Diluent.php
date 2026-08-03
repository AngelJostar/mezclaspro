<?php
// app/Models/Oncologicos/Diluent.php
namespace App\Models\Oncologicos;

use Illuminate\Database\Eloquent\Model;

class Diluent extends Model
{
    protected $fillable = [
        'denominacion_generica',   // nuevo campo
    ];

    public function medicines()
    {
        return $this->belongsToMany(
            MedicinesCatalog::class,
            'diluent_medicine_catalog',
            'diluent_id',
            'medicine_catalog_id'
        );
    }

    public function presentations()
    {
        return $this->hasMany(DiluentPresentation::class);
    }

}
