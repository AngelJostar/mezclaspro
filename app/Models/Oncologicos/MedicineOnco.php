<?php

namespace App\Models\Oncologicos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicineOnco extends Model {
use HasFactory;

    protected $table = 'medicine_oncos';

    protected $fillable = [
        'catalog_id',
        'precio',      // si lo sigues usando
        'precio_mg',   // NUEVO si aplicará
    ];

    protected $casts = [
        'precio'    => 'decimal:2',
        'precio_mg' => 'decimal:4',
    ];

    public function mezclaMedicamentos()
    {
        return $this->hasMany(MezclaMedicamento::class, 'medicamento_id');
    }

    public function medicineLists()
    {
        return $this->belongsToMany(MedicineList::class, 'medicine_medicine_lists', 'medicine_id', 'medicine_list_id')
            ->withPivot(['precio', 'precio_mg_override', 'charge_by'])
            ->withTimestamps();
    }

    public function catalog()
    {
        return $this->belongsTo(MedicinesCatalog::class, 'catalog_id');
    }
}
