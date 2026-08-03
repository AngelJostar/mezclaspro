<?php

namespace App\Models\Nutricionales;

use App\Models\InstitutionBilling;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Solicitud extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'solicitud_detail_id',
        'solicitud_patient_id',
        'is_active',
        'fecha_hora_preparacion',
        'fecha_hora_limite_uso',
        'estado',
        'lote',
        'remision',
    ];


    protected $casts = [

        'fecha_hora_preparacion' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
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

