<?php

namespace App\Models\Oncologicos;

use App\Models\InstitutionBilling;
use App\Services\SolicitudOperativeStatusService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mezcla extends Model
{
    use HasFactory;

    private const TERMINAL_REQUEST_STATUSES = [
        'cancelada',
        'no_aprobada',
        'no-aprobada',
    ];

    protected static function booted(): void
    {
        static::saved(function (self $mezcla) {
            if ($mezcla->wasRecentlyCreated || $mezcla->wasChanged('estado')) {
                app(SolicitudOperativeStatusService::class)->sync((int) $mezcla->solicitud_id);
            }
        });

        static::deleted(function (self $mezcla) {
            app(SolicitudOperativeStatusService::class)->sync((int) $mezcla->solicitud_id);
        });
    }

    protected $table = 'mezclas';

    protected $fillable = [
        'solicitud_id',
        'volumen_dilucion',
        'tiempo_infusion',
        'fecha_entrega',
        'estado',
        'remision',
        'lote',
        'infusor_id',
        'set_infusion',
        'diluent_presentation_id',
    ];

    protected $casts = [
        'set_infusion' => 'boolean',
        'volumen_dilucion' => 'decimal:2',
        'fecha_entrega' => 'datetime',
        'diluent_presentation_id' => 'integer',
    ];

    public function solicitud()
    {
        return $this->belongsTo(SolicitudOnco::class, 'solicitud_id');
    }

    public function getOperationalStatusAttribute(): string
    {
        $requestStatus = mb_strtolower(trim((string) $this->solicitud?->estado));

        if (in_array($requestStatus, self::TERMINAL_REQUEST_STATUSES, true)) {
            return $requestStatus;
        }

        $mixtureStatus = mb_strtolower(trim((string) $this->estado));

        return $mixtureStatus !== '' ? $mixtureStatus : 'pendiente';
    }

    public function medicamentos()
    {
        return $this->hasMany(MezclaMedicamento::class, 'mezcla_id');
    }

    public function inspeccion()
    {
        return $this->hasOne(InspeccionMezcla::class, 'mezcla_id');
    }

    // ✅ Relación con infusor
    public function infusor()
    {
        return $this->belongsTo(Infusor::class, 'infusor_id');
    }

    // ✅ Relación con presentación de diluyente elegida
    public function diluentPresentation()
    {
        return $this->belongsTo(DiluentPresentation::class, 'diluent_presentation_id');
    }

    public function billing()
    {
        return $this->hasOne(InstitutionBilling::class, 'origen_id')
            ->where('origen_tipo', 'oncologica_mezcla');
    }
}
