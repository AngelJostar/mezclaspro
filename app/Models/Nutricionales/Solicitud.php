<?php

namespace App\Models\Nutricionales;

use App\Models\InstitutionBilling;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Solicitud extends Model
{
    use \App\Models\Concerns\HasMixtureAdjustment;
    use HasFactory;
    use \App\Models\Concerns\HasSourceQuotation;

    protected $fillable = [
        'hospital_id',
        'user_id',
        'solicitud_detail_id',
        'solicitud_patient_id',
        'is_active',
        'fecha_hora_preparacion',
        'fecha_hora_limite_uso',
        'validated_by',
        'validated_at',
        'estado',
        'lote',
        'remision',
    ];


    protected $casts = [
        'quotation_pricing_snapshot' => 'array',

        'fecha_hora_preparacion' => 'datetime',
        'validated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function hospital()
    {
        return $this->belongsTo(\App\Models\Hospital::class);
    }

    public function getHospitalIdAttribute($value)
    {
        return $value ?? $this->user?->hospital_id;
    }

    public function preparationHospital()
    {
        if (!$this->hospital || !$this->request_quotation_id) return $this->hospital;
        $hospital = clone $this->hospital;
        $hospital->nutri_medicine_list_id = $this->quotation->price_list_id;
        return $hospital;
    }

    public function scopeForRequestUser($query, User $user)
    {
        return $query->where(fn ($visible) => $visible->where('solicituds.user_id', $user->id)
            ->orWhere(fn ($quoted) => $quoted->whereNotNull('solicituds.request_quotation_id')
                ->where('solicituds.hospital_id', $user->hospital_id ?: 0)));
    }

    //Relacion uno a uno inversa
    public function solicitud_detail()
    {
        return $this->belongsTo(SolicitudDetail::class);
    }

    //Relacion uno a uno inversa
    public function solicitud_patient()
    {
        return $this->belongsTo(SolicitudPatient::class);
    }

    public function input()
    {
        return $this->hasMany(SolicitudInput::class);
    }


    public function inspeccionNutricional()
    {
        return $this->hasOne(InspeccionNutricional::class, 'solicitud_id');
    }

    public function billing()
    {
        return $this->hasOne(InstitutionBilling::class, 'origen_id')
            ->where('origen_tipo', 'nutricional_solicitud');
    }
}
