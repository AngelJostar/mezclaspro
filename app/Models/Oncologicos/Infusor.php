<?php

namespace App\Models\Oncologicos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Infusor extends Model
{
    protected $fillable = [
        'nombre_generico',
        'nombre_comercial',
        'lote',
        'precio',
        'caducidad',
        'is_active'
    ];

    // 👇 Esto convierte 'caducidad' a Carbon automáticamente
    protected $casts = [
        'caducidad' => 'date',
        'is_active' => 'boolean',
    ];
}
