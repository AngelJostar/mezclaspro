<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstitutionBilling extends Model
{
    public const STAGE_PENDING = 'pending';

    public const STAGE_RECEIVABLE = 'receivable';

    public const STAGE_HISTORY = 'history';

    public const RECEIVABLE_FIELDS = [
        'folio_interno',
        'fecha_facturacion',
        'numero_carta_factura',
        'fecha_carta_factura',
    ];

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
        'fecha_compensacion',
        'observaciones',
    ];

    public function institucion()
    {
        return $this->belongsTo(Institucion::class, 'institucion_id');
    }

    public function hospital()
    {
        return $this->belongsTo(Hospital::class);
    }

    public function movements()
    {
        return $this->hasMany(InstitutionBillingMovement::class);
    }

    public function workflowStage(): string
    {
        if (mb_strtolower(trim((string) $this->estatus_facturacion)) === 'completado') {
            return self::STAGE_HISTORY;
        }

        return $this->hasReceivableInvoiceData()
            ? self::STAGE_RECEIVABLE
            : self::STAGE_PENDING;
    }

    public function hasReceivableInvoiceData(): bool
    {
        foreach (self::RECEIVABLE_FIELDS as $field) {
            if (trim((string) $this->{$field}) === '') {
                return false;
            }
        }

        return true;
    }
}
