<?php

namespace App\Models\Oncologicos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicinePresentation extends Model
{
    use HasFactory;

    protected $table = 'medicine_presentations';

    protected $fillable = [
        'catalog_id',
        'presentacion',
        'contenido_valor',
        'contenido_unidad',
        'marca',
        'fabricante',
        'cantidad_medicamento',
        'volumen_diluyente',
        'is_available',
        'virtual_stock',
        'precio_frasco',

        // ✅ NUEVOS
        'legend',
        'forma_reconstitucion',
        'temp_min_c',
        'temp_max_c',
        'stability_hours',
    ];

    protected $casts = [
        'contenido_valor'   => 'decimal:2',
        'is_available'      => 'boolean',
        'virtual_stock'     => 'integer',
        'precio_frasco'     => 'decimal:4',

        // ✅ NUEVOS
        'temp_min_c'        => 'integer',
        'temp_max_c'        => 'integer',
        'stability_hours'   => 'integer',
    ];

    public function catalog()
    {
        return $this->belongsTo(MedicinesCatalog::class, 'catalog_id');
    }

    public function batches()
    {
        return $this->hasMany(MedicineBatch::class, 'medicine_presentation_id');
    }

    // Lote vigente (is_current=1)
    public function currentBatch()
    {
        return $this->hasOne(MedicineBatch::class, 'medicine_presentation_id')
            ->where('is_current', true);
    }

    public function lists()
    {
        return $this->belongsToMany(
            MedicineList::class,
            'medicine_list_presentation',
            'medicine_presentation_id',
            'medicine_list_id'
        )->withPivot([
            'charge_by',
            'precio',
            'precio_mg_override',
        ])->withTimestamps();
    }
}
