<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class ConsumableCatalogPresentation extends Model {
    protected $fillable = ['consumable_item_id', 'presentation', 'commercial_name', 'manufacturer', 'is_active'];
    public function item(): BelongsTo { return $this->belongsTo(ConsumableItem::class, 'consumable_item_id'); }
    public function lots(): HasMany { return $this->hasMany(ConsumableLot::class, 'catalog_presentation_id'); }
}
