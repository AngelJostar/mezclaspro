<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MinimumStockSetting extends Model
{
    protected $fillable = [
        'laboratory_id', 'product_type', 'presentation_id', 'minimum_stock',
        'maximum_stock', 'supplier_id', 'updated_by',
    ];

    protected $casts = ['minimum_stock' => 'integer', 'maximum_stock' => 'integer'];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
