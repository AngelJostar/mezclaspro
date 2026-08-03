<?php

namespace App\Models\Nutricionales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SolicitudPatient extends Model
{
    use HasFactory;
    protected $fillable = [
        'nombre_paciente',
        'apellidos_paciente',
        'servicio',
        'cama',
        'piso',
        'registro',
        'diagnostico',
        'fecha_nacimiento',
        'edad',
        'peso',
        'sexo',
    ];

    //Relacion uno a uno
    public function solicitud()
    {
        return $this->hasOne(Solicitud::class);
    }
}
