<?php

namespace App\Models\Oncologicos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MezclaMedicamentoPresentacion extends Model
{
    use HasFactory;

    protected $table = 'mezcla_medicamento_presentaciones';

    protected $fillable = [
        'mezcla_medicamento_id',
        'medicine_batch_id',
        'unidades_usadas',
        'unidades_abiertas',
        'volumen_usado_ml',
        'charge_by_snapshot',
        'lote_usado',
        'caducidad_usada',

        // ✅ NUEVOS snapshots
        'presentacion_snapshot',
        'cantidad_medicamento_snapshot',
        'volumen_diluyente_snapshot',
        'legend_snapshot',

        'precio_frasco_snapshot',
        'precio_unitario_snapshot',
        'subtotal',
    ];

    protected $casts = [
        'unidades_usadas'        => 'integer',
        'unidades_abiertas'      => 'integer',
        'volumen_usado_ml'       => 'decimal:4',
        'charge_by_snapshot'     => 'string',
        'caducidad_usada'        => 'date',
        'precio_frasco_snapshot' => 'decimal:4',
        'precio_unitario_snapshot' => 'decimal:4',
        'subtotal'               => 'decimal:4',

        // (opcionales)
        'cantidad_medicamento_snapshot' => 'decimal:4',
        'volumen_diluyente_snapshot'    => 'decimal:4',
    ];

    public function mezclaMedicamento()
    {
        return $this->belongsTo(MezclaMedicamento::class, 'mezcla_medicamento_id');
    }

    public function batch()
    {
        return $this->belongsTo(MedicineBatch::class, 'medicine_batch_id');
    }

    // Acceso rápido a la presentación (vía batch)
    public function presentation()
    {
        return $this->hasOneThrough(
            MedicinePresentation::class,
            MedicineBatch::class,
            'id',
            'id',
            'medicine_batch_id',
            'medicine_presentation_id'
        );
    }
}
