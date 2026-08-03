<?php
// app/Models/Oncologicos/DiluentPresentation.php
namespace App\Models\Oncologicos;

use Illuminate\Database\Eloquent\Model;

class DiluentPresentation extends Model
{
    protected $fillable = [
        'diluent_id',
        'laboratory_id',
        'presentacion',             // ej. "Bolsa 500 mL"
        'volume_ml',                // ej. 500.00
        'denominacion_comercial',   // ej. "NaCl 0.9% B. Braun"
        'fabricante',
        'lote',
        'caducidad',
        'fecha_ingreso',
        'stock_inicial',
        'stock_actual',
        'stock_reservado',
        'is_active',

    ];

    protected $casts = [
        'volume_ml' => 'float',
        'stock_inicial' => 'float',
        'stock_actual' => 'float',
        'stock_reservado' => 'float',
        'is_active' => 'boolean',
        'caducidad' => 'date',
        'fecha_ingreso' => 'date',
    ];

    public function diluent()
    {
        return $this->belongsTo(Diluent::class);
    }

    public function laboratory()
    {
        return $this->belongsTo(Laboratory::class);
    }
}
