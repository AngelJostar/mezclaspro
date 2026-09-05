<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class ConsumableItem extends Model { protected $fillable = ['name','unit','is_active']; public function lots(): HasMany { return $this->hasMany(ConsumableLot::class); } public function catalogPresentations(): HasMany { return $this->hasMany(ConsumableCatalogPresentation::class); } }
