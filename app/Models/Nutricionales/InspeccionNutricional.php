<?php

namespace App\Models\Nutricionales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InspeccionNutricional extends Model
{
    use HasFactory;

    protected $table = 'inspeccion_nutricionales';

    protected $fillable = [
        'solicitud_id',
        'es_limpia',
        'es_libre',
        'fecha_inspeccion',
        'hora_inspeccion',
        'tipo_contenedor',
        'tipo_contenedor_otro',
        'esta_rotulado',
        'numero_lote',
        'medicamento',
        'dosis_volumen_total',
        'volumen_medicamento',
        'rubrica_preparador',
        'sello_seguridad',
        'presenta_grietas',
        'presenta_fugas',
        'esta_roto',
        'coloracion_apropiada',
        'contenido_homogeneo',
        'presenta_particulas',
        'presenta_turbidez',
        'volumen_correcto',
        'aprueba_contenido',
        'aprueba_contenedor',
        'dosis_volumen',
        'peso_mezcla',
        'mezcla_aprobada',
        'observaciones',
        'reviso_nombre',
        'aprobo_nombre',
        'preparo_nombre',
        'libero_nombre',
    ];

    protected $casts = [
        'es_limpia' => 'boolean',
        'es_libre' => 'boolean',
        'esta_rotulado' => 'boolean',
        'numero_lote' => 'boolean',
        'medicamento' => 'boolean',
        'dosis_volumen_total' => 'boolean',
        'volumen_medicamento' => 'boolean',
        'rubrica_preparador' => 'boolean',
        'sello_seguridad' => 'boolean',
        'presenta_grietas' => 'boolean',
        'presenta_fugas' => 'boolean',
        'esta_roto' => 'boolean',
        'coloracion_apropiada' => 'boolean',
        'contenido_homogeneo' => 'boolean',
        'presenta_particulas' => 'boolean',
        'presenta_turbidez' => 'boolean',
        'volumen_correcto' => 'boolean',
        'aprueba_contenido' => 'boolean',
        'aprueba_contenedor' => 'boolean',
        'mezcla_aprobada' => 'boolean',
        'fecha_inspeccion' => 'date',
    ];

    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class, 'solicitud_id');
    }
}
