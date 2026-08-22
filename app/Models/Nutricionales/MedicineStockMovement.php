<?php

namespace App\Models\Nutricionales;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class MedicineStockMovement extends Model
{
    protected $fillable = [
        'medicine_laboratory_stock_id',
        'warehouse_id',
        'user_id',
        'tipo',
        'cantidad_ml',
        'stock_antes',
        'stock_despues',
        'cantidad_frascos',
        'frascos_antes',
        'frascos_despues',
        'reference_type',
        'reference_id',
        'notes',
    ];

    protected $casts = [
        'cantidad_ml' => 'decimal:2',
        'stock_antes' => 'decimal:2',
        'stock_despues' => 'decimal:2',
        'cantidad_frascos' => 'decimal:2',
        'frascos_antes' => 'decimal:2',
        'frascos_despues' => 'decimal:2',
    ];

    public function stock()
    {
        return $this->belongsTo(MedicineLaboratoryStock::class, 'medicine_laboratory_stock_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
