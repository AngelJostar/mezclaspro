<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionSupplyRequest extends Model
{
    public const STATUS_REQUESTED = 'requested';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PARTIALLY_APPROVED = 'partially_approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_SUPPLIED = 'supplied';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = ['folio', 'warehouse_id', 'area', 'status', 'observations', 'resolution_notes', 'requested_by', 'approved_by', 'supplied_by', 'received_by', 'requested_at', 'approved_at', 'supplied_at', 'received_at'];
    protected $casts = ['requested_at' => 'datetime', 'approved_at' => 'datetime', 'supplied_at' => 'datetime', 'received_at' => 'datetime'];

    public static function statusLabels(): array
    {
        return [
            self::STATUS_REQUESTED => 'Solicitada',
            self::STATUS_APPROVED => 'Aprobada',
            self::STATUS_PARTIALLY_APPROVED => 'Aprobada parcialmente',
            self::STATUS_REJECTED => 'Rechazada',
            self::STATUS_SUPPLIED => 'Surtida',
            self::STATUS_RECEIVED => 'Recibida',
            self::STATUS_CANCELLED => 'Cancelada',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusLabels()[$this->status] ?? 'Pendiente';
    }

    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function requester(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function supplier(): BelongsTo { return $this->belongsTo(User::class, 'supplied_by'); }
    public function receiver(): BelongsTo { return $this->belongsTo(User::class, 'received_by'); }
    public function lines(): HasMany { return $this->hasMany(ProductionSupplyRequestLine::class); }
}
