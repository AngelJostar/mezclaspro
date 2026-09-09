<?php
// app/Models/Oncologicos/DiluentPresentation.php
namespace App\Models\Oncologicos;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;

class DiluentPresentation extends Model
{
    protected $fillable = [
        'diluent_id',
        'catalog_presentation_id',
        'laboratory_id',
        'warehouse_id',
        'presentacion',             // ej. "Bolsa 500 mL"
        'volume_ml',                // ej. 500.00
        'denominacion_comercial',   // ej. "NaCl 0.9% B. Braun"
        'fabricante',
        'stability_hours',
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
        'stability_hours' => 'integer',
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
    public function catalogPresentation()
    {
        return $this->belongsTo(DiluentCatalogPresentation::class, 'catalog_presentation_id');
    }

    public function laboratory()
    {
        return $this->belongsTo(Laboratory::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
