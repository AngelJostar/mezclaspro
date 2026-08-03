<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Institucion extends Model
{
    protected $table = 'clientes';

    protected $fillable = [
        'nombre',
        'razon_social',
        'rfc',
        'telefono',
    ];

    public function hospitals()
    {
        return $this->belongsToMany(Hospital::class, 'cliente_hospital', 'cliente_id', 'hospital_id');
    }
}
