<?php

namespace App\Support;

use App\Models\Hospital;

class MixtureWorkflowContext
{
    public static function destination(?Hospital $hospital): string
    {
        $institutions = $hospital?->instituciones->pluck('nombre')->filter()->unique()->implode(', ');

        return 'Institución: '.($institutions ?: 'Sin institución').' | Hospital: '.($hospital?->name ?: 'Sin hospital');
    }

    public static function label(int|string|null $mixtureId, ?Hospital $hospital): string
    {
        return 'Mezcla #'.($mixtureId ?? 'Sin asignar').' | '.self::destination($hospital);
    }
}
