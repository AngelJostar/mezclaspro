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
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function hospitals()
    {
        return $this->belongsToMany(Hospital::class, 'cliente_hospital', 'cliente_id', 'hospital_id');
    }
}
