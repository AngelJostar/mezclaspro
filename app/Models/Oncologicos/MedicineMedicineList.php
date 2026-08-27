<?php

namespace App\Models\Oncologicos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicineMedicineList extends Model
{
        use HasFactory;

    protected $table = 'medicine_medicine_lists';

    public $timestamps = true; // tu migración los tiene

    protected $fillable = [
        'medicine_list_id',
        'medicine_id',
        'precio',
        'precio_mg_override',
        'precio_ml_override',
        'charge_by', // preferencia por lista (nullable)
    ];

    protected $casts = [
        'precio'             => 'decimal:2',
        'precio_mg_override' => 'decimal:4',
        'precio_ml_override' => 'decimal:4',
        'charge_by'          => 'string',
    ];

    public function medicine()
    {
        return $this->belongsTo(MedicineOnco::class, 'medicine_id');
    }

    public function list()
    {
        return $this->belongsTo(MedicineList::class, 'medicine_list_id');
    }
}
