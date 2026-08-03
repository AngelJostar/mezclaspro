<?php

namespace App\Models\Nutricionales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SolicitudDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'via_administracion',
        'tiempo_infusion_min',
        'sobrellenado_ml',
        'volumen_total',
        'suma_volumen',
        'volumen_total_final',
        'suma_volumen_final',
        'npt',
        'nombre_medico',
        'cedula',
        'fecha_hora_entrega',
        'observaciones',
        'observaciones',
        'suma_volumen_sobrellenado',
        'velocidad_infusion',
        'hospital_destino'

    ];


    //Relacion uno a uno
    public function solicitud()
    {
        return $this->hasOne(Solicitud::class);
    }
}
