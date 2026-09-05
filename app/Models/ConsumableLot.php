<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ConsumableLot extends Model { protected $fillable = ['consumable_item_id','catalog_presentation_id','warehouse_id','presentation','brand','manufacturer','lot','expires_at','received_at','stock_actual','is_active']; protected $casts = ['expires_at'=>'date','received_at'=>'date','is_active'=>'boolean']; public function item(): BelongsTo { return $this->belongsTo(ConsumableItem::class,'consumable_item_id'); } public function catalogPresentation(): BelongsTo { return $this->belongsTo(ConsumableCatalogPresentation::class,'catalog_presentation_id'); } public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); } }
