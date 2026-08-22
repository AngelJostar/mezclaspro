<?php

namespace App\Models\Oncologicos;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicineBatch extends Model
{
    use HasFactory;

    protected $table = 'medicine_batches';

    protected $fillable = [
        'laboratory_id',
        'warehouse_id',
        'medicine_presentation_id',
        'lote',
        'caducidad',
        'fecha_ingreso',
        'stock_inicial',
        'stock_actual',
        'stock_reservado',
        'costo_unitario',
        'is_current',
        'is_active',
    ];

    protected $casts = [
        'caducidad' => 'date',
        'fecha_ingreso' => 'date',
        'is_current' => 'boolean',
        'is_active' => 'boolean',
        'stock_inicial' => 'integer',
        'stock_actual' => 'integer',
        'stock_reservado' => 'integer',
        'costo_unitario' => 'decimal:4',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    public function laboratory(): BelongsTo
    {
        return $this->belongsTo(Laboratory::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function presentation(): BelongsTo
    {
        return $this->belongsTo(
            MedicinePresentation::class,
            'medicine_presentation_id'
        );
    }

    public function movements(): HasMany
    {
        return $this->hasMany(
            MedicineBatchMovement::class,
            'medicine_batch_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current', true);
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeDisponibles(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->whereDate('caducidad', '>=', now());
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    public function getStockDisponibleAttribute(): int
    {
        return max($this->stock_actual - $this->stock_reservado, 0);
    }
}
