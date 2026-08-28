<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceListAdditionalCharge extends Model
{
    protected $fillable = ['price_list_type', 'price_list_id', 'name', 'concept_type', 'amount', 'iva_included', 'is_active'];

    protected $casts = [
        'amount' => 'decimal:4',
        'iva_included' => 'boolean',
        'is_active' => 'boolean',
    ];
}
