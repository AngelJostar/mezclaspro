<?php

namespace App\Models\Oncologicos;

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicineBatchMovement extends Model
{
    use HasFactory;

    protected $table = 'medicine_batch_movements';

    protected $fillable = [
        'medicine_batch_id',
        'laboratory_id',
        'warehouse_id',
        'user_id',
        'movement_type',
        'quantity',
        'quantity_ml',
        'stock_actual_before',
        'stock_actual_after',
        'stock_ml_before',
        'stock_ml_after',
        'stock_reservado_before',
        'stock_reservado_after',
        'reference_type',
        'reference_id',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'quantity_ml' => 'decimal:4',
        'stock_actual_before' => 'integer',
        'stock_actual_after' => 'integer',
        'stock_ml_before' => 'decimal:4',
        'stock_ml_after' => 'decimal:4',
        'stock_reservado_before' => 'integer',
        'stock_reservado_after' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    public function batch(): BelongsTo
    {
        return $this->belongsTo(
            MedicineBatch::class,
            'medicine_batch_id'
        );
    }

    public function laboratory(): BelongsTo
    {
        return $this->belongsTo(Laboratory::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
