<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicineRemainderMovement extends Model
{
    protected $fillable = [
        'medicine_remainder_id',
        'user_id',
        'movement_type',
        'quantity_ml',
        'stock_before_ml',
        'stock_after_ml',
        'reference_type',
        'reference_id',
        'notes',
    ];

    protected $casts = [
        'quantity_ml' => 'decimal:4',
        'stock_before_ml' => 'decimal:4',
        'stock_after_ml' => 'decimal:4',
    ];

    public function remainder()
    {
        return $this->belongsTo(MedicineRemainder::class, 'medicine_remainder_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
