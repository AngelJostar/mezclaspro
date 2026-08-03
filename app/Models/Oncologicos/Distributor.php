<?php

namespace App\Models\Oncologicos;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Distributor extends Model
{
    protected $fillable = [
        'medicine_list_id',
        'nombre',
        'direccion',
        'logo_path',
    ];

    public function medicineList()
    {
        return $this->belongsTo(MedicineList::class, 'medicine_list_id');
    }
}