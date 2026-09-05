<?php

namespace App\Models;

use App\Models\Oncologicos\DiluentPresentation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionSupplyRequestLine extends Model
{
    protected $fillable = ['production_supply_request_id', 'diluent_presentation_id', 'consumable_lot_id', 'requested_quantity', 'approved_quantity', 'supplied_quantity', 'received_quantity', 'notes'];
    protected $casts = ['requested_quantity' => 'float', 'approved_quantity' => 'float', 'supplied_quantity' => 'float', 'received_quantity' => 'float'];
    public function request(): BelongsTo { return $this->belongsTo(ProductionSupplyRequest::class, 'production_supply_request_id'); }
    public function supply(): BelongsTo { return $this->belongsTo(DiluentPresentation::class, 'diluent_presentation_id'); }
    public function consumableLot(): BelongsTo { return $this->belongsTo(ConsumableLot::class); }
}
