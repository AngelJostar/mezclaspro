<?php

namespace App\Models\Concerns;

use App\Models\MixtureAdjustment;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

trait HasMixtureAdjustment
{
    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(MixtureAdjustment::class, 'adjustment_id');
    }

    public function currentAdjustment(): ?MixtureAdjustment
    {
        return $this->adjustment_id ? $this->adjustment : null;
    }

    protected static function bootHasMixtureAdjustment(): void
    {
        static::saving(function ($model) {
            if ($model->isDirty('estado') && in_array($model->estado, [
                'aprobada', 'dispensada', 'preparada', 'revisada', 'entregada',
            ], true) && $model->currentAdjustment()?->isPending()) {
                throw ValidationException::withMessages([
                    'ajuste' => 'El ajuste requiere autorizacion del hospital y aprobacion final de la central.',
                ]);
            }
        });
    }
}
