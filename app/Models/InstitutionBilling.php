<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstitutionBilling extends Model
{
    protected $fillable = [
        'institucion_id',
        'hospital_id',
        'origen_tipo',
        'origen_id',
        'precio_total',
        'conciliable',
        'folio_factura_uuid',
        'folio_interno',
        'fecha_facturacion',
        'estatus_facturacion',
        'numero_carta_factura',
        'fecha_carta_factura',
    ];

    public function institucion()
    {
        return $this->belongsTo(Institucion::class, 'institucion_id');
    }

    public function hospital()
    {
        return $this->belongsTo(Hospital::class);
    }
}
