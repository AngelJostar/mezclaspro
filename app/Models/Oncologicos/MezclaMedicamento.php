<?php

namespace App\Models\Oncologicos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MezclaMedicamento extends Model
{

    use HasFactory;

    protected $table = 'mezcla_medicamentos';

    protected $fillable = [
        'mezcla_id',
        'medicamento_id',
        'nombre_medicamento',

        // ✅ snapshots
        'denominacion_snapshot',
        'marca_snapshot',
        'requires_infusor_snapshot',
        'conc_min_snapshot',
        'conc_max_snapshot',

        'dosis',
        'dosis_ml',
        'diluyente_id',
        'via_administracion_id',

        'charge_by',
        'precio_mg_snapshot',
    ];

    protected $casts = [
        'dosis'              => 'decimal:2',
        'dosis_ml'           => 'decimal:2',
        'precio_unitario'    => 'decimal:2',
        'precio_mg_snapshot' => 'decimal:4',
        'charge_by'          => 'string', // 'mg' | 'frasco'
    ];

    public function mezcla()
    {
        return $this->belongsTo(Mezcla::class, 'mezcla_id');
    }

    public function medicamentoOnco()
    {
        return $this->belongsTo(MedicineOnco::class, 'medicamento_id');
    }

    public function diluyente()
    {
        return $this->belongsTo(Diluent::class, 'diluyente_id');
    }

    public function viaAdministracion()
    {
        return $this->belongsTo(AdministrationRoute::class, 'via_administracion_id');
    }

    // NUEVO: detalle 1:N (frasco/presentación)
    public function presentacionesUsadas()
    {
        return $this->hasMany(MezclaMedicamentoPresentacion::class, 'mezcla_medicamento_id');
    }

    // Conveniencia: total del renglón según charge_by
    public function total(): float
    {
        if ($this->charge_by === 'mg') {
            $dosis = (float) ($this->dosis ?? 0);
            $precioMg = (float) ($this->precio_mg_snapshot ?? 0);
            return round($dosis * $precioMg, 4);
        }

        // 'frasco': suma subtotales del detalle
        return round(
            (float) $this->presentacionesUsadas()->sum('subtotal'),
            4
        );
    }
}
