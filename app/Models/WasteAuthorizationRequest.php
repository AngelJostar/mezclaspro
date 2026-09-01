<?php

namespace App\Models;

use App\Models\Nutricionales\MedicineLaboratoryStock;
use App\Models\Oncologicos\MedicineBatch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WasteAuthorizationRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'domain',
        'medicine_batch_id',
        'medicine_laboratory_stock_id',
        'requested_by',
        'reviewed_by',
        'quantity_containers',
        'quantity_ml',
        'reason',
        'status',
        'review_notes',
        'reviewed_at',
        'snapshot_product',
        'snapshot_presentation',
        'snapshot_brand',
        'snapshot_lot',
        'snapshot_laboratory',
        'snapshot_warehouse',
        'snapshot_cost_per_ml',
        'snapshot_cost_per_container',
    ];

    protected $casts = [
        'quantity_containers' => 'integer',
        'quantity_ml' => 'decimal:4',
        'snapshot_cost_per_ml' => 'decimal:4',
        'snapshot_cost_per_container' => 'decimal:4',
        'reviewed_at' => 'datetime',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(MedicineBatch::class, 'medicine_batch_id');
    }

    public function nutritionStock(): BelongsTo
    {
        return $this->belongsTo(MedicineLaboratoryStock::class, 'medicine_laboratory_stock_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }
}
