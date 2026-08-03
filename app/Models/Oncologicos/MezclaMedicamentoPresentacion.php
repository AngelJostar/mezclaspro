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
        'lote_usado',
        'caducidad_usada',

        // ✅ NUEVOS snapshots
        'presentacion_snapshot',
        'cantidad_medicamento_snapshot',
        'volumen_diluyente_snapshot',
        'legend_snapshot',

        'precio_frasco_snapshot',
        'subtotal',
    ];

    protected $casts = [
        'unidades_usadas'        => 'integer',
        'caducidad_usada'        => 'date',
        'precio_frasco_snapshot' => 'decimal:4',
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
