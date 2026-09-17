<?php

namespace App\Models;

use App\Models\Nutricionales\Solicitud;
use App\Models\Oncologicos\Mezcla;
use Illuminate\Database\Eloquent\Model;

class MixtureAdjustment extends Model
{
    public const PENDING_STATUSES = ['requested', 'authorized', 'declined'];

    public const AWAITING_APPROVAL_STATUSES = ['requested', 'authorized'];

    protected $guarded = ['id'];

    protected $casts = [
        'proposal' => 'array', 'review' => 'array',
        'authorized_at' => 'datetime', 'approved_at' => 'datetime', 'cancelled_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $adjustment) {
            if ($adjustment->isDirty(['kind', 'target_id', 'hospital_id', 'description', 'proposal', 'review', 'baseline_hash', 'requested_by'])) {
                throw new \LogicException('Una version de ajuste guardada no se puede modificar.');
            }
        });
    }

    public function target(): Model
    {
        return ($this->kind === 'nutricionales' ? Solicitud::query() : Mezcla::query())
            ->findOrFail($this->target_id);
    }

    public function getLabelAttribute(): string
    {
        return match ($this->status) {
            'requested' => 'Ajuste Solicitado',
            'authorized' => 'Ajuste autorizado',
            'approved' => 'Aprobada con Ajuste',
            'declined' => 'Ajuste no autorizado',
            default => 'Sin Ajustes',
        };
    }

    public function isPending(): bool
    {
        return in_array($this->status, self::PENDING_STATUSES, true);
    }

    public function getLogLabelAttribute(): string
    {
        return match ($this->status) {
            'declined', 'rejected' => 'Rechazado',
            'cancelled' => 'Cancelado',
            default => $this->label,
        };
    }
}
