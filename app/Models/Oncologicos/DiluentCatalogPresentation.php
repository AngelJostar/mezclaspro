<?php
namespace App\Models\Oncologicos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class DiluentCatalogPresentation extends Model { protected $fillable=['diluent_id','presentation','volume_ml','commercial_name','manufacturer','is_active']; public function diluent(): BelongsTo { return $this->belongsTo(Diluent::class); } public function lots(): HasMany { return $this->hasMany(DiluentPresentation::class,'catalog_presentation_id'); } }
